"""Pydantic request/response schemas — strict typed outputs."""

from typing import Any, Optional
from pydantic import BaseModel, Field


# ─── Request schemas ──────────────────────────────────────────────────────────

class PriceIntelligenceRequest(BaseModel):
    crop_type: str
    quantity_kg: float
    woreda_id: str
    listing_id: Optional[str] = None


class AddressResolverRequest(BaseModel):
    description: str
    lat: Optional[float] = None
    lng: Optional[float] = None
    language: str = "am"


class SmartDispatchRequest(BaseModel):
    order_id: str
    available_drivers: list[dict[str, Any]] = []
    road_conditions: list[dict[str, Any]] = []


class DemandForecastRequest(BaseModel):
    crop_type: str
    region_id: str
    days_ahead: int = Field(ge=1, le=90)
    historical_data: list[dict[str, Any]] = []


class ComplianceCheckRequest(BaseModel):
    order_id: str
    documents: list[str] = []
    crop_type: str = ""
    destination_country: Optional[str] = None


class ShelfLifeRequest(BaseModel):
    crop_type: str
    harvest_date: str
    temp_log: list[dict[str, Any]] = []
    transit_hours: float = 0.0


class SmsDraftRequest(BaseModel):
    event_type: str
    context: dict[str, Any] = {}
    language: str = "am"


class InsightReportRequest(BaseModel):
    tenant_id: str
    period: str = "weekly"
    data_summary: dict[str, Any] = {}


# ─── Response schemas ─────────────────────────────────────────────────────────

class PriceIntelligenceResponse(BaseModel):
    suggested_price_etb: float
    ecx_price: Optional[float] = None
    mercato_price: Optional[float] = None
    trend_direction: str = "stable"
    confidence: float = 0.0
    reasoning: str = ""


class AddressResolverResponse(BaseModel):
    landmark_id: Optional[str] = None
    resolved_address: str
    lat: Optional[float] = None
    lng: Optional[float] = None
    confidence: float = 0.0
    suggested_name_am: str = ""
    suggested_name_en: str = ""


class SmartDispatchResponse(BaseModel):
    recommended_driver_id: Optional[str] = None
    estimated_pickup_minutes: Optional[int] = None
    reasoning: str = ""
    fallback_driver_id: Optional[str] = None


class DemandForecastResponse(BaseModel):
    forecast: list[dict[str, Any]] = []
    confidence: float = 0.0
    seasonal_factors: list[str] = []
    weather_impact: str = ""


class ComplianceCheckResponse(BaseModel):
    is_compliant: bool
    missing_documents: list[str] = []
    warnings: list[str] = []
    checklist: list[dict[str, Any]] = []


class ShelfLifeResponse(BaseModel):
    predicted_shelf_life_days: int
    risk_level: str = "low"
    recommendation: str = ""


class SmsDraftResponse(BaseModel):
    message_am: str = ""
    message_en: str = ""
    character_count: int = 0


class InsightReportResponse(BaseModel):
    report_am: str = ""
    report_en: str = ""
    key_findings: list[str] = []
    anomalies: list[str] = []


# ─── Phase 2 schemas ──────────────────────────────────────────────────────────

class CreditScoreExplainRequest(BaseModel):
    farmer_id: str
    score: dict[str, Any]

class CreditScoreExplainResponse(BaseModel):
    explanation_en: str = ""
    explanation_am: str = ""
    improvement_tips: list[str] = []
    eligible_for_credit: bool = False


class DiseaseDiagnosisRequest(BaseModel):
    crop_type: str
    symptoms: str
    region: Optional[str] = None
    photo_urls: list[str] = []

class DiseaseDiagnosisResponse(BaseModel):
    disease_name: str = "Unknown"
    confidence: float = 0.0
    severity: str = "medium"
    treatment: list[str] = []
    prevention: list[str] = []


class WeatherAlertAgentRequest(BaseModel):
    forecast: dict[str, Any]
    region_id: Optional[str] = None
    crops_at_risk: list[str] = []

class WeatherAlertAgentResponse(BaseModel):
    advisory_en: str = ""
    advisory_am: str = ""
    recommended_actions: list[str] = []
    severity_override: str = "watch"


class NegotiationSuggestionRequest(BaseModel):
    commodity: str
    initial_ask_etb: float
    current_offer_etb: float
    quantity_kg: float
    turns: list[dict[str, Any]] = []

class NegotiationSuggestionResponse(BaseModel):
    suggested_price_etb: float = 0.0
    reasoning: str = ""
    confidence: float = 0.5


class YieldPredictionRequest(BaseModel):
    crop_type: str
    season: str
    planted_area_ha: float
    soil_type: Optional[str] = None
    irrigation_type: Optional[str] = None
    elevation_m: Optional[float] = None
    region_id: Optional[str] = None
    farmer_id: Optional[str] = None

class YieldPredictionResponse(BaseModel):
    predicted_yield_kg: float = 0.0
    min_yield_kg: float = 0.0
    max_yield_kg: float = 0.0
    confidence: float = 0.5
    harvest_start: Optional[str] = None
    harvest_end: Optional[str] = None
