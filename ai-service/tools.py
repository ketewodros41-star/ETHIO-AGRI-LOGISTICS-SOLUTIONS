"""MCP tool server — exposes Fleetbase MySQL API as LangChain tools."""

import httpx
import config
from langchain_core.tools import tool


def _api(method: str, path: str, **kwargs) -> dict:
    """Helper: call the Laravel API with bearer token."""
    headers = {"Authorization": f"Bearer {config.LARAVEL_API_TOKEN}"}
    url = f"{config.LARAVEL_API_URL}{path}"
    resp = httpx.request(method, url, headers=headers, timeout=10, **kwargs)
    resp.raise_for_status()
    return resp.json()


@tool
def get_harvest_listings(crop_type: str, region_id: str = "", date_range: str = "") -> dict:
    """Fetch active harvest listings from Fleetbase API."""
    params: dict = {"crop_type": crop_type}
    if region_id:
        params["region_id"] = region_id
    return _api("GET", "/harvest/listings", params=params)


@tool
def get_ecx_prices(crop_type: str, date_range: str = "") -> dict:
    """Fetch Ethiopia Commodity Exchange prices for a crop type."""
    # ECX scraping/API — returns mock data until ECX API access is obtained
    ecx_mock = {
        "teff":       {"price_etb": 55.00, "unit": "kg", "date": "2026-05-10"},
        "coffee":     {"price_etb": 320.00, "unit": "kg", "date": "2026-05-10"},
        "sesame":     {"price_etb": 85.00, "unit": "kg", "date": "2026-05-10"},
        "chickpeas":  {"price_etb": 42.00, "unit": "kg", "date": "2026-05-10"},
        "wheat":      {"price_etb": 38.00, "unit": "kg", "date": "2026-05-10"},
    }
    return ecx_mock.get(crop_type, {"price_etb": None, "note": "No ECX data"})


@tool
def get_order_details(order_id: str) -> dict:
    """Fetch order details from Fleetbase."""
    return _api("GET", f"/orders/{order_id}")


@tool
def get_driver_availability(region_id: str) -> dict:
    """Fetch available drivers in a region."""
    return _api("GET", "/drivers", params={"region_id": region_id, "status": "available"})


@tool
def get_road_conditions(origin_woreda: str, dest_woreda: str) -> dict:
    """Fetch road condition data between two woredas."""
    return _api("GET", "/routes/segments", params={
        "origin_woreda": origin_woreda,
        "dest_woreda": dest_woreda,
    })


@tool
def get_weather_forecast(lat: float, lng: float, days: int = 7) -> dict:
    """Fetch weather forecast from Open-Meteo API."""
    resp = httpx.get(config.OPEN_METEO_URL, params={
        "latitude":  lat,
        "longitude": lng,
        "daily":     "temperature_2m_max,precipitation_sum",
        "forecast_days": days,
        "timezone": "Africa/Addis_Ababa",
    }, timeout=10)
    resp.raise_for_status()
    return resp.json()


@tool
def get_quality_grades(shipment_id: str) -> dict:
    """Fetch quality grades for a shipment."""
    return _api("GET", "/quality/grades", params={"shipment_id": shipment_id})


@tool
def get_payment_status(order_id: str) -> dict:
    """Fetch escrow payment status for an order."""
    return _api("GET", f"/payments/escrow/{order_id}")


@tool
def query_landmark_db(description: str, lat: float = 0.0, lng: float = 0.0) -> dict:
    """Search landmark database by description and optional coordinates."""
    params: dict = {}
    if lat and lng:
        params.update({"lat": lat, "lng": lng, "radius": 10})
    return _api("GET", "/ethiopia/landmarks", params=params)


@tool
def get_regional_demand_history(crop_type: str, region_id: str) -> dict:
    """Fetch historical supply/demand data for a crop in a region."""
    return _api("GET", "/analytics/supply/by-crop", params={
        "crop_type": crop_type,
        "region_id": region_id,
    })


@tool
def get_farmer_credit_score(farmer_id: str) -> dict:
    """Fetch a farmer's credit score profile from the Tera Harvest API."""
    import httpx, os
    base = os.getenv("TERA_HARVEST_API_URL", "http://api:8000")
    try:
        resp = httpx.get(f"{base}/api/v1/credit/{farmer_id}", timeout=5)
        if resp.status_code == 200:
            return resp.json().get("data", {})
    except Exception:
        pass
    return {"score": 0, "score_band": "unrated", "farmer_id": farmer_id}


@tool
def get_disease_knowledge_base(crop_type: str, symptom_keywords: str) -> dict:
    """Query the disease knowledge base for matching diseases and treatments."""
    knowledge = {
        "coffee": [
            {"disease": "Coffee Berry Disease", "symptoms": ["brown spots", "berry rot"], "treatment": "Copper fungicide application"},
            {"disease": "Coffee Leaf Rust", "symptoms": ["yellow patches", "orange powder"], "treatment": "Fungicide spray, remove infected leaves"},
        ],
        "teff": [
            {"disease": "Teff Head Smut", "symptoms": ["black spores", "smut balls"], "treatment": "Seed dressing, crop rotation"},
        ],
        "maize": [
            {"disease": "Fall Armyworm", "symptoms": ["leaf holes", "frass", "caterpillars"], "treatment": "Neem-based pesticide, manual removal"},
            {"disease": "Maize Streak Virus", "symptoms": ["yellow streaks", "stunting"], "treatment": "No cure; remove infected plants, control vectors"},
        ],
        "wheat": [
            {"disease": "Stem Rust", "symptoms": ["reddish-brown pustules on stem"], "treatment": "Triazole fungicide, resistant varieties"},
        ],
    }
    crop_diseases = knowledge.get(crop_type.lower(), [])
    keywords = [kw.strip().lower() for kw in symptom_keywords.split(",")]
    matches = [d for d in crop_diseases if any(kw in " ".join(d["symptoms"]) for kw in keywords)]
    return {"matches": matches or crop_diseases, "crop_type": crop_type}


@tool
def get_regional_yield_history(region_id: str, crop_type: str, seasons: int = 5) -> dict:
    """Get historical yield averages for a region and crop type."""
    mock_yields = {
        "ET-OR": {"coffee": 850, "maize": 2200, "teff": 1100, "wheat": 1900},
        "ET-AM": {"wheat": 2100, "teff": 1300, "maize": 2000, "barley": 1800},
        "ET-SI": {"sorghum": 1600, "maize": 1900, "teff": 1050},
        "default": {"teff": 1200, "maize": 2000, "wheat": 1800, "coffee": 800},
    }
    region_data = mock_yields.get(region_id, mock_yields["default"])
    avg_yield = region_data.get(crop_type.lower(), 1500)
    return {
        "region_id": region_id,
        "crop_type": crop_type,
        "avg_yield_kg_per_ha": avg_yield,
        "seasons_analyzed": seasons,
        "yield_trend": "stable",
        "best_season_yield": round(avg_yield * 1.3, 1),
        "worst_season_yield": round(avg_yield * 0.7, 1),
    }


@tool
def get_negotiation_history(listing_id: str) -> dict:
    """Retrieve past negotiation outcomes for a listing's commodity to inform pricing."""
    import httpx, os
    base = os.getenv("TERA_HARVEST_API_URL", "http://api:8000")
    try:
        resp = httpx.get(f"{base}/api/v1/negotiations?listing_id={listing_id}&status=accepted", timeout=5)
        if resp.status_code == 200:
            return resp.json()
    except Exception:
        pass
    return {"data": [], "avg_accepted_price_etb": None}


@tool
def get_driver_performance_history(driver_id: str, periods: int = 4) -> dict:
    """Get driver performance history for leaderboard and badge calculations."""
    import httpx, os
    base = os.getenv("TERA_HARVEST_API_URL", "http://api:8000")
    try:
        resp = httpx.get(f"{base}/api/v1/driver-earnings/{driver_id}?per_page={periods}", timeout=5)
        if resp.status_code == 200:
            return resp.json()
    except Exception:
        pass
    return {"data": [], "driver_id": driver_id}


ALL_TOOLS = [
    get_harvest_listings,
    get_ecx_prices,
    get_order_details,
    get_driver_availability,
    get_road_conditions,
    get_weather_forecast,
    get_quality_grades,
    get_payment_status,
    query_landmark_db,
    get_regional_demand_history,
    # Phase 2 tools
    get_farmer_credit_score,
    get_disease_knowledge_base,
    get_regional_yield_history,
    get_negotiation_history,
    get_driver_performance_history,
]
