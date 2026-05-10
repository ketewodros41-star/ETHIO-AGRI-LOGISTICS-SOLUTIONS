"""All LangGraph agent endpoints."""

from __future__ import annotations

from typing import Annotated, Any, TypedDict

from fastapi import APIRouter, HTTPException
from langchain_core.messages import HumanMessage, SystemMessage
from langgraph.graph import END, StateGraph
from langgraph.prebuilt import ToolNode, tools_condition

import llm as llm_module
import schemas
import tools as tool_module

router = APIRouter()


# ─── Shared graph builder ─────────────────────────────────────────────────────

class AgentState(TypedDict):
    messages: list[Any]


def _build_agent(agent_tools: list) -> Any:
    """Build a simple ReAct LangGraph agent with the given tools."""
    model = llm_module.get_llm().bind_tools(agent_tools)
    tool_node = ToolNode(agent_tools)

    def call_model(state: AgentState) -> AgentState:
        response = model.invoke(state["messages"])
        return {"messages": state["messages"] + [response]}

    graph = StateGraph(AgentState)
    graph.add_node("agent", call_model)
    graph.add_node("tools", tool_node)
    graph.set_entry_point("agent")
    graph.add_conditional_edges("agent", tools_condition)
    graph.add_edge("tools", "agent")

    return graph.compile()


# ─── Price Intelligence Agent ─────────────────────────────────────────────────

@router.post("/price-intelligence", response_model=schemas.PriceIntelligenceResponse)
async def price_intelligence(req: schemas.PriceIntelligenceRequest) -> schemas.PriceIntelligenceResponse:
    """Suggest a fair price for a harvest listing using ECX data and market trends."""
    agent = _build_agent([tool_module.get_ecx_prices, tool_module.get_harvest_listings])

    system = SystemMessage(content=(
        "You are a market intelligence agent for Ethiopian agriculture. "
        "Use get_ecx_prices and get_harvest_listings to determine a fair price. "
        "Return ONLY valid JSON matching the PriceIntelligenceResponse schema."
    ))
    human = HumanMessage(content=(
        f"Crop: {req.crop_type}, Quantity: {req.quantity_kg}kg, Woreda: {req.woreda_id}. "
        "Provide suggested_price_etb, ecx_price, mercato_price, trend_direction (up/down/stable), confidence (0-1), reasoning."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content

        import json, re
        json_match = re.search(r'\{.*\}', last, re.DOTALL)
        if json_match:
            data = json.loads(json_match.group())
            return schemas.PriceIntelligenceResponse(**data)
    except Exception as e:
        pass

    # Fallback: use ECX price directly
    ecx = tool_module.get_ecx_prices.invoke({"crop_type": req.crop_type, "date_range": ""})
    ecx_price = ecx.get("price_etb") or 0.0
    return schemas.PriceIntelligenceResponse(
        suggested_price_etb=ecx_price * 0.95,
        ecx_price=ecx_price,
        confidence=0.5,
        reasoning="Fallback: 5% below ECX spot price",
        trend_direction="stable",
    )


# ─── Address Resolver Agent ───────────────────────────────────────────────────

@router.post("/address-resolver", response_model=schemas.AddressResolverResponse)
async def address_resolver(req: schemas.AddressResolverRequest) -> schemas.AddressResolverResponse:
    """Resolve a natural-language address description to coordinates + landmark."""
    agent = _build_agent([tool_module.query_landmark_db])

    system = SystemMessage(content=(
        "You are an address resolution agent for Ethiopia. "
        "Use query_landmark_db to find matching landmarks. "
        "Return ONLY valid JSON matching AddressResolverResponse schema. "
        "Always include suggested_name_am and suggested_name_en in Amharic and English."
    ))
    human = HumanMessage(content=(
        f"Description: '{req.description}'. "
        f"Coordinates hint: lat={req.lat}, lng={req.lng}. Language preference: {req.language}."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        json_match = re.search(r'\{.*\}', last, re.DOTALL)
        if json_match:
            return schemas.AddressResolverResponse(**json.loads(json_match.group()))
    except Exception:
        pass

    return schemas.AddressResolverResponse(
        resolved_address=req.description,
        lat=req.lat,
        lng=req.lng,
        confidence=0.1,
        suggested_name_en=req.description,
        suggested_name_am=req.description,
    )


# ─── Smart Dispatch Agent ─────────────────────────────────────────────────────

@router.post("/smart-dispatch", response_model=schemas.SmartDispatchResponse)
async def smart_dispatch(req: schemas.SmartDispatchRequest) -> schemas.SmartDispatchResponse:
    """Recommend the best driver for an order given road conditions."""
    agent = _build_agent([
        tool_module.get_order_details,
        tool_module.get_driver_availability,
        tool_module.get_road_conditions,
    ])

    system = SystemMessage(content=(
        "You are a logistics dispatch agent. Choose the best available driver "
        "considering proximity, road conditions, and vehicle type. "
        "Return ONLY valid JSON matching SmartDispatchResponse schema."
    ))
    human = HumanMessage(content=f"Order ID: {req.order_id}. Available drivers: {req.available_drivers}.")

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        json_match = re.search(r'\{.*\}', last, re.DOTALL)
        if json_match:
            return schemas.SmartDispatchResponse(**json.loads(json_match.group()))
    except Exception:
        pass

    drivers = req.available_drivers
    return schemas.SmartDispatchResponse(
        recommended_driver_id=drivers[0].get("id") if drivers else None,
        reasoning="Fallback: first available driver selected.",
    )


# ─── Demand Forecast Agent ────────────────────────────────────────────────────

@router.post("/demand-forecast", response_model=schemas.DemandForecastResponse)
async def demand_forecast(req: schemas.DemandForecastRequest) -> schemas.DemandForecastResponse:
    """Forecast demand for a crop in a region."""
    agent = _build_agent([
        tool_module.get_regional_demand_history,
        tool_module.get_weather_forecast,
    ])

    system = SystemMessage(content=(
        "You are a demand forecasting agent for Ethiopian agriculture. "
        "Use historical data and weather forecasts. "
        "Return ONLY valid JSON matching DemandForecastResponse schema."
    ))
    human = HumanMessage(content=(
        f"Crop: {req.crop_type}, Region: {req.region_id}, Days ahead: {req.days_ahead}."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        json_match = re.search(r'\{.*\}', last, re.DOTALL)
        if json_match:
            return schemas.DemandForecastResponse(**json.loads(json_match.group()))
    except Exception:
        pass

    return schemas.DemandForecastResponse(confidence=0.3, forecast=[], seasonal_factors=[], weather_impact="unknown")


# ─── Compliance Check Agent ───────────────────────────────────────────────────

@router.post("/compliance-check", response_model=schemas.ComplianceCheckResponse)
async def compliance_check(req: schemas.ComplianceCheckRequest) -> schemas.ComplianceCheckResponse:
    """Check export compliance documents for an order."""
    required_docs = {
        "phytosanitary", "certificate_of_origin", "packing_list",
    }
    present = set(req.documents)
    missing = list(required_docs - present)

    checklist = [
        {"doc_type": d, "required": True, "present": d in present, "status": "ok" if d in present else "missing"}
        for d in required_docs
    ]

    return schemas.ComplianceCheckResponse(
        is_compliant=len(missing) == 0,
        missing_documents=missing,
        warnings=[],
        checklist=checklist,
    )


# ─── Shelf Life Prediction Agent ──────────────────────────────────────────────

@router.post("/shelf-life", response_model=schemas.ShelfLifeResponse)
async def shelf_life(req: schemas.ShelfLifeRequest) -> schemas.ShelfLifeResponse:
    """Predict shelf life given crop type, harvest date, and cold-chain history."""
    base_days = {
        "teff": 365, "coffee": 730, "sesame": 180, "chickpeas": 365,
        "wheat": 365, "maize": 180, "vegetables": 7, "fruits": 14,
    }
    days = base_days.get(req.crop_type, 90)

    avg_temp = 0.0
    if req.temp_log:
        temps = [r.get("temperature_celsius", 20) for r in req.temp_log]
        avg_temp = sum(temps) / len(temps)
        if avg_temp > 25:
            days = int(days * 0.7)

    risk = "low" if days > 60 else ("medium" if days > 14 else "high")
    return schemas.ShelfLifeResponse(
        predicted_shelf_life_days=days,
        risk_level=risk,
        recommendation=f"Store below 25°C. Estimated shelf life: {days} days.",
    )


# ─── SMS Draft Agent ──────────────────────────────────────────────────────────

@router.post("/sms-draft", response_model=schemas.SmsDraftResponse)
async def sms_draft(req: schemas.SmsDraftRequest) -> schemas.SmsDraftResponse:
    """Draft an SMS notification in Amharic and English."""
    llm = llm_module.get_llm()

    prompt = (
        f"Draft a concise SMS (max 160 chars) for event: '{req.event_type}'. "
        f"Context: {req.context}. "
        "Return JSON: {{\"message_am\": \"...\", \"message_en\": \"...\"}}"
    )

    try:
        response = llm.invoke([HumanMessage(content=prompt)])
        import json, re
        json_match = re.search(r'\{.*\}', response.content, re.DOTALL)
        if json_match:
            data = json.loads(json_match.group())
            msg_en = data.get("message_en", "")
            msg_am = data.get("message_am", "")
            return schemas.SmsDraftResponse(
                message_am=msg_am,
                message_en=msg_en,
                character_count=max(len(msg_en), len(msg_am)),
            )
    except Exception:
        pass

    msg = f"Tera Harvest: {req.event_type} notification."
    return schemas.SmsDraftResponse(message_am=msg, message_en=msg, character_count=len(msg))


# ─── Insight Report Agent ─────────────────────────────────────────────────────

@router.post("/insight-report", response_model=schemas.InsightReportResponse)
async def insight_report(req: schemas.InsightReportRequest) -> schemas.InsightReportResponse:
    """Generate a weekly/monthly insight report in Amharic and English."""
    llm = llm_module.get_llm()

    prompt = (
        f"Generate a {req.period} agricultural supply chain insight report for tenant {req.tenant_id}. "
        f"Data summary: {req.data_summary}. "
        "Return JSON: {{\"report_am\": \"...\", \"report_en\": \"...\", "
        "\"key_findings\": [...], \"anomalies\": [...]}}"
    )

    try:
        response = llm.invoke([
            SystemMessage(content="You are an agricultural analyst reporting to Ethiopian cooperatives and NGOs."),
            HumanMessage(content=prompt),
        ])
        import json, re
        json_match = re.search(r'\{.*\}', response.content, re.DOTALL)
        if json_match:
            data = json.loads(json_match.group())
            return schemas.InsightReportResponse(**data)
    except Exception:
        pass

    return schemas.InsightReportResponse(
        report_en="Insight report generation failed. Please retry.",
        report_am="ሪፖርቱን ማዘጋጀት አልተቻለም። እባክዎ እንደገና ይሞክሩ።",
        key_findings=[],
        anomalies=[],
    )


# ─── Credit Score Explainer Agent ────────────────────────────────────────────

@router.post("/credit-score-explain", response_model=schemas.CreditScoreExplainResponse)
async def credit_score_explain(req: schemas.CreditScoreExplainRequest) -> schemas.CreditScoreExplainResponse:
    """Explain a farmer's credit score in plain Amharic and English."""
    agent = _build_agent([tool_module.get_farmer_credit_score])
    llm = llm_module.get_llm()

    prompt = (
        f"Explain the following credit score for farmer {req.farmer_id} in simple language. "
        f"Score data: {req.score}. "
        "Be encouraging where possible. Return JSON: "
        '{"explanation_en": "...", "explanation_am": "...", "improvement_tips": ["..."], "eligible_for_credit": true/false}'
    )

    try:
        response = llm.invoke([
            SystemMessage(content="You are a financial literacy agent helping Ethiopian smallholder farmers understand their credit profiles."),
            HumanMessage(content=prompt),
        ])
        import json, re
        m = re.search(r'\{.*\}', response.content, re.DOTALL)
        if m:
            data = json.loads(m.group())
            return schemas.CreditScoreExplainResponse(**data)
    except Exception:
        pass

    score_band = req.score.get("score_band", "unrated")
    return schemas.CreditScoreExplainResponse(
        explanation_en=f"Your credit band is {score_band}. Keep trading consistently to improve.",
        explanation_am=f"የብድር ደረጃዎ {score_band} ነው። ለማሻሻል ያለሁኔታ ንግዱን ይቀጥሉ።",
        improvement_tips=["Complete more orders", "Maintain quality grades"],
        eligible_for_credit=score_band in ["silver", "gold", "platinum"],
    )


# ─── Disease Diagnosis Agent (vision-capable) ─────────────────────────────────

@router.post("/disease-diagnosis", response_model=schemas.DiseaseDiagnosisResponse)
async def disease_diagnosis(req: schemas.DiseaseDiagnosisRequest) -> schemas.DiseaseDiagnosisResponse:
    """Diagnose crop disease from symptoms and optionally image URLs."""
    agent = _build_agent([tool_module.get_disease_knowledge_base])

    system = SystemMessage(content=(
        "You are an agronomist AI specializing in Ethiopian crop diseases. "
        "Use the disease knowledge base to diagnose and recommend treatment. "
        "Return ONLY valid JSON matching DiseaseDiagnosisResponse schema."
    ))
    human = HumanMessage(content=(
        f"Crop: {req.crop_type}. Symptoms: {req.symptoms}. "
        f"Region: {req.region}. Photos: {req.photo_urls}. "
        "Provide disease_name, confidence (0-1), severity, treatment, prevention tips."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        m = re.search(r'\{.*\}', last, re.DOTALL)
        if m:
            return schemas.DiseaseDiagnosisResponse(**json.loads(m.group()))
    except Exception:
        pass

    return schemas.DiseaseDiagnosisResponse(
        disease_name="Unknown",
        confidence=0.1,
        severity="medium",
        treatment=["Consult local extension officer"],
        prevention=["Monitor regularly"],
    )


# ─── Weather Alert Agent ──────────────────────────────────────────────────────

@router.post("/weather-alert", response_model=schemas.WeatherAlertAgentResponse)
async def weather_alert_agent(req: schemas.WeatherAlertAgentRequest) -> schemas.WeatherAlertAgentResponse:
    """Interpret weather forecast and generate agricultural impact advisory."""
    llm = llm_module.get_llm()

    prompt = (
        f"Analyze this weather forecast for Ethiopian agricultural region: {req.forecast}. "
        f"Region: {req.region_id}. Crops at risk: {req.crops_at_risk}. "
        "Return JSON: {\"advisory_en\": \"...\", \"advisory_am\": \"...\", "
        "\"recommended_actions\": [...], \"severity_override\": \"watch|warning|emergency\"}"
    )

    try:
        response = llm.invoke([
            SystemMessage(content="You are a meteorological advisory agent for Ethiopian farmers."),
            HumanMessage(content=prompt),
        ])
        import json, re
        m = re.search(r'\{.*\}', response.content, re.DOTALL)
        if m:
            return schemas.WeatherAlertAgentResponse(**json.loads(m.group()))
    except Exception:
        pass

    return schemas.WeatherAlertAgentResponse(
        advisory_en="Severe weather expected. Secure your harvest.",
        advisory_am="ከባድ የአየር ሁኔታ ይጠበቃል። ምርትዎን ያስጠብቁ።",
        recommended_actions=["Secure storage", "Delay transport"],
        severity_override="warning",
    )


# ─── Price Negotiation Agent ──────────────────────────────────────────────────

@router.post("/price-negotiation", response_model=schemas.NegotiationSuggestionResponse)
async def price_negotiation_agent(req: schemas.NegotiationSuggestionRequest) -> schemas.NegotiationSuggestionResponse:
    """Suggest an optimal negotiation price based on market data and turn history."""
    agent = _build_agent([tool_module.get_ecx_prices, tool_module.get_negotiation_history])

    system = SystemMessage(content=(
        "You are a neutral price negotiation mediator for Ethiopian agricultural commodities. "
        "Suggest a fair price that balances seller profit with buyer affordability. "
        "Return ONLY valid JSON matching NegotiationSuggestionResponse schema."
    ))
    human = HumanMessage(content=(
        f"Commodity: {req.commodity}. Initial ask: {req.initial_ask_etb} ETB. "
        f"Current offer: {req.current_offer_etb} ETB. Quantity: {req.quantity_kg}kg. "
        f"Turns so far: {req.turns}. Suggest a fair price."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        m = re.search(r'\{.*\}', last, re.DOTALL)
        if m:
            return schemas.NegotiationSuggestionResponse(**json.loads(m.group()))
    except Exception:
        pass

    midpoint = (float(req.initial_ask_etb) + float(req.current_offer_etb)) / 2
    return schemas.NegotiationSuggestionResponse(
        suggested_price_etb=round(midpoint, 2),
        reasoning="Midpoint between ask and offer.",
        confidence=0.5,
    )


# ─── Yield Prediction Agent ───────────────────────────────────────────────────

@router.post("/yield-prediction", response_model=schemas.YieldPredictionResponse)
async def yield_prediction_agent(req: schemas.YieldPredictionRequest) -> schemas.YieldPredictionResponse:
    """Predict crop yield based on plot data, weather, and historical yields."""
    agent = _build_agent([
        tool_module.get_regional_yield_history,
        tool_module.get_weather_forecast,
    ])

    system = SystemMessage(content=(
        "You are an agronomic yield prediction AI for Ethiopian smallholder farmers. "
        "Use historical yield data and weather forecasts. "
        "Return ONLY valid JSON matching YieldPredictionResponse schema."
    ))
    human = HumanMessage(content=(
        f"Crop: {req.crop_type}. Season: {req.season}. Area: {req.planted_area_ha} ha. "
        f"Soil: {req.soil_type}. Irrigation: {req.irrigation_type}. "
        f"Elevation: {req.elevation_m}m. Region: {req.region_id}."
    ))

    try:
        result = agent.invoke({"messages": [system, human]})
        last = result["messages"][-1].content
        import json, re
        m = re.search(r'\{.*\}', last, re.DOTALL)
        if m:
            return schemas.YieldPredictionResponse(**json.loads(m.group()))
    except Exception:
        pass

    base_yield_per_ha = {"teff": 1200, "maize": 2500, "wheat": 2000, "coffee": 800}
    per_ha = base_yield_per_ha.get(req.crop_type.lower(), 1500)
    predicted = round(float(req.planted_area_ha) * per_ha, 3)
    return schemas.YieldPredictionResponse(
        predicted_yield_kg=predicted,
        min_yield_kg=round(predicted * 0.75, 3),
        max_yield_kg=round(predicted * 1.25, 3),
        confidence=0.4,
        harvest_start=None,
        harvest_end=None,
    )
