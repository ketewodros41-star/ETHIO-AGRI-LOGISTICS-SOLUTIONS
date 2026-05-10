# TERA HARVEST — Phase 2 Backend Extensions
## 12 New Features — Full Backend Engineering Prompt
### Extends: packages/tera-harvest-core (Phase 1 — 116 files already built)

---

## CONTEXT

You are continuing backend development on **Tera Harvest**, the Ethiopian
agricultural supply chain platform built on Fleetbase (Laravel + MySQL + Redis).
Phase 1 is complete — 116 files, 23 migrations, 8 controllers, 11 jobs, 8 AI
agents, and full payment escrow are already in place.

This prompt adds **12 new extensions** to the existing
`packages/tera-harvest-core` Laravel package and the `ai-service` FastAPI
microservice.

All Phase 1 conventions apply:
- ULIDs for primary keys, `company_id` for tenant isolation
- `HasTenant` + `HasUlid` traits on all new models
- `auth:sanctum` + `EnsureTeraHarvestTenant` on all `/api/v1/` routes
- All monetary values: `DECIMAL(12,2)` ETB, calculated with `bcmath`
- All new jobs: `ShouldQueue` + idempotent
- All new AI agents: LangGraph `StateGraph` + Pydantic v2 schemas + Langfuse tracing
- PHPUnit feature tests for every new controller

---

## EXTENSION 11: Farmer Credit Scoring System

### Purpose
Build a dynamic credit score for every farmer on the platform based purely
on their behaviour — no bank account or formal credit history required.
Expose scores to microfinance partners via a secure API.

### Database Migrations

```sql
-- farmer_credit_scores
id, uuid, farmer_id (FK → contacts), company_id,
score (tinyint 0-100),
score_band (enum: unrated|bronze|silver|gold|platinum),
delivery_reliability_score (tinyint 0-25),   -- on-time deliveries / total
quality_consistency_score (tinyint 0-25),    -- avg quality grade trend
volume_history_score (tinyint 0-25),         -- kg traded, seasons active
payment_behaviour_score (tinyint 0-25),      -- disputes, chargebacks, refunds
total_orders_completed (int),
total_kg_traded (decimal 12,3),
avg_quality_grade (enum: A|B|C|ungraded),
seasons_active (tinyint),
last_dispute_at (timestamp nullable),
last_calculated_at (timestamp),
score_version (tinyint default 1),
created_at, updated_at

-- credit_score_history
id, uuid, farmer_id (FK), company_id,
score, score_band, delta (tinyint signed),
reason, triggered_by_event,
calculated_at, created_at

-- microfinance_credit_requests
id, uuid, farmer_id (FK), company_id,
partner_name, partner_reference,
requested_amount_etb (decimal 12,2),
score_at_request (tinyint),
score_band_at_request,
data_package (JSON — anonymised score breakdown shared with partner),
status (enum: pending|approved|declined|disbursed|repaid|defaulted),
approved_amount_etb (decimal 12,2 nullable),
interest_rate_pct (decimal 5,2 nullable),
disbursed_at, due_date, repaid_at,
created_at, updated_at
```

### Scoring Algorithm (Laravel Service)

**File:** `src/Services/CreditScoring/FarmerCreditScoringService.php`

```php
// Scoring weights (configurable per tenant in tenant_configurations)
// delivery_reliability:  35% — completed orders / total accepted orders
// quality_consistency:   25% — % of grades A or B over last 12 months
// volume_history:        25% — kg traded (logarithmic scale) + seasons active
// payment_behaviour:     15% — inverse of disputes + refunds + chargebacks

public function calculateScore(string $farmerId): CreditScore {}
public function getScoreBand(int $score): string {}
public function generateCreditPackage(string $farmerId): array {}
    // Returns anonymised data package safe to share with microfinance partners
public function bulkRecalculate(string $companyId): void {}
    // Called by scheduled job — recalculates all farmers in tenant
```

### Score Bands
```
0–20:   Unrated   (new, < 2 completed orders)
21–40:  Bronze    (limited history or poor reliability)
41–60:  Silver    (reliable, moderate volume)
61–80:  Gold      (highly reliable, consistent quality)
81–100: Platinum  (top performer — eligible for largest credit lines)
```

### API Endpoints
```
GET  /api/v1/credit/score/me                  (farmer views own score)
GET  /api/v1/credit/score/{farmerId}          (admin/partner views farmer score)
GET  /api/v1/credit/score/{farmerId}/history  (score change timeline)
GET  /api/v1/credit/score/{farmerId}/package  (anonymised package for partners)
POST /api/v1/credit/requests                  (microfinance partner submits request)
PATCH /api/v1/credit/requests/{id}            (partner updates status: approved/declined)
GET  /api/v1/credit/requests?farmer_id=&status=

-- Partner API (separate auth — API key, not sanctum)
GET  /api/v1/partner/credit/{farmer_uuid}     (read-only, anonymised, rate-limited)
```

### Jobs & Scheduling
- `RecalculateFarmerCreditScore` — triggered on: OrderCompleted, QualityGradeIssued,
  PaymentDisputed, DeliveryConfirmed events
- `BulkRecalculateCreditScores` — scheduled every Sunday 2am EAT,
  recalculates all farmers in all tenants
- `NotifyFarmerScoreImproved` — SMS via Africa's Talking when farmer moves
  up a band: "ውድ [name], ምድቦት ወደ ወርቅ ደረጃ ከፍ ብሏል!" (Your rating has risen to Gold!)

### AI Agent (Python FastAPI)
```
POST /agents/credit-score-explain
Input:  { farmer_id, score, score_breakdown, language: am|en }
Output: { explanation_am, explanation_en, improvement_tips[], next_band_requirements }
```
Agent explains the score in plain Amharic to the farmer and gives 3 concrete
actions they can take to improve it.

---

## EXTENSION 12: Cooperative Bulk Aggregation Engine

### Purpose
When multiple farmers in the same woreda have small quantities of the same
crop, the system auto-combines them into a single buyer-facing lot. Handles
pro-rata payment splits automatically back to each contributing farmer.

### Database Migrations

```sql
-- aggregation_lots
id, uuid, company_id,
cooperative_id (FK → contacts),
crop_type, quality_grade (enum: A|B|C|mixed),
woreda_id (FK), kebele_id (FK nullable),
target_quantity_kg (decimal 10,3),
aggregated_quantity_kg (decimal 10,3 default 0),
min_farmers (tinyint default 2),
status (enum: open|locked|matched|dispatched|completed|cancelled),
locked_at, matched_at, buyer_order_id (FK → orders nullable),
agreed_price_per_kg_etb (decimal 10,2 nullable),
collection_date (date nullable),
collection_landmark_id (FK nullable),
notes, created_at, updated_at, deleted_at

-- aggregation_lot_contributions
id, uuid, lot_id (FK), farmer_id (FK → contacts), company_id,
listing_id (FK → harvest_listings),
contributed_quantity_kg (decimal 10,3),
confirmed_quantity_kg (decimal 10,3 nullable),  -- actual weight at pickup
pro_rata_pct (decimal 8,5),                     -- recalculated on lock
payment_amount_etb (decimal 12,2 nullable),
payment_status (enum: pending|released|failed),
payment_transaction_id (FK nullable),
joined_at, confirmed_at, paid_at, created_at
```

### Aggregation Engine Service

**File:** `src/Services/Aggregation/BulkAggregationService.php`

```php
public function findOrCreateLot(string $cropType, string $woredaId,
                                  string $grade, string $companyId): AggregationLot {}
    // Finds an open lot for the crop+woreda+grade combination
    // Creates a new lot if none exists or existing lot is full

public function addContribution(string $listingId,
                                  string $lotId, float $quantityKg): Contribution {}
    // Adds farmer's listing as a contribution to the lot
    // Validates quantity available on listing
    // Updates aggregated_quantity_kg on lot

public function lockLot(string $lotId): void {}
    // Called when lot reaches target_quantity_kg or cooperative triggers manually
    // Recalculates pro_rata_pct for all contributions (bcmath)
    // Fires AggregationLotLocked event → notifies all contributing farmers via SMS

public function splitPayment(string $lotId,
                               float $totalAmountEtb): array {}
    // Called after buyer pays for the lot
    // Splits payment pro-rata to each farmer's wallet
    // Uses bcmath — no floating point
    // Handles rounding residual (goes to cooperative wallet)
    // Returns array of PaymentTransaction records created

public function reconcileWeights(string $lotId,
                                   array $confirmedWeights): void {}
    // Called after physical weighing at collection point
    // Updates confirmed_quantity_kg per contribution
    // Recalculates pro_rata_pct based on actual weights
    // Adjusts payment_amount_etb accordingly before release
```

### API Endpoints
```
GET  /api/v1/aggregation/lots?crop_type=&woreda_id=&status=
POST /api/v1/aggregation/lots                    (cooperative creates lot)
GET  /api/v1/aggregation/lots/{id}
POST /api/v1/aggregation/lots/{id}/join          (farmer joins lot with listing)
POST /api/v1/aggregation/lots/{id}/lock          (cooperative locks lot)
POST /api/v1/aggregation/lots/{id}/reconcile     (submit actual weights)
GET  /api/v1/aggregation/lots/{id}/contributions
GET  /api/v1/aggregation/my-contributions        (farmer sees own contributions)
```

### Jobs
- `AutoMatchAggregationLot` — when lot locks, triggers buyer matching via
  Smart Dispatch Agent — finds buyers with saved searches matching crop+grade+region
- `SplitLotPayment` — triggered on EscrowReleased for a lot order,
  calls `splitPayment()` service, creates individual farmer payment transactions
- `NotifyLotContributors` — SMS to all contributing farmers when lot is
  locked, matched, collected, or paid

---

## EXTENSION 13: Seasonal Input Supply (Reverse Logistics)

### Purpose
Use the existing driver network and route knowledge base to deliver
agricultural inputs (fertiliser, seeds, pesticides) from suppliers in
Addis Ababa to farmers at the farm gate. Reverse flow of the same logistics
infrastructure.

### Database Migrations

```sql
-- input_suppliers
id, uuid, company_id, name, phone, email,
region_id (FK), woreda_id (FK), landmark_id (FK nullable),
verified (bool default false), verification_documents (JSON),
rating (decimal 3,2 default 0), total_orders_fulfilled (int default 0),
created_at, updated_at, deleted_at

-- input_products
id, uuid, company_id, supplier_id (FK),
name_en, name_am, category
  (enum: fertiliser|seeds|pesticide|herbicide|fungicide|equipment|other),
unit (enum: kg|litre|bag_50kg|bag_100kg|unit),
price_per_unit_etb (decimal 10,2),
stock_quantity (decimal 10,3),
min_order_quantity (decimal 10,3 default 1),
description, photo_url,
is_active (bool default true),
created_at, updated_at

-- input_orders
id, uuid, company_id,
buyer_id (FK → contacts),            -- farmer or cooperative
buyer_type (enum: farmer|cooperative),
delivery_woreda_id (FK),
delivery_landmark_id (FK nullable),
delivery_address_description,
status (enum: draft|confirmed|processing|dispatched|delivered|cancelled),
total_amount_etb (decimal 12,2),
payment_status (enum: pending|paid|refunded),
payment_transaction_id (FK nullable),
driver_id (FK nullable), vehicle_id (FK nullable),
estimated_delivery_date (date nullable),
delivered_at, notes, created_at, updated_at, deleted_at

-- input_order_items
id, uuid, order_id (FK), product_id (FK), company_id,
quantity (decimal 10,3),
unit_price_etb (decimal 10,2),
line_total_etb (decimal 12,2),
created_at
```

### API Endpoints
```
GET  /api/v1/inputs/suppliers?region_id=&category=
GET  /api/v1/inputs/products?supplier_id=&category=&woreda_id=
POST /api/v1/inputs/orders               (farmer/coop places input order)
GET  /api/v1/inputs/orders/{id}
GET  /api/v1/inputs/orders/my-orders
PATCH /api/v1/inputs/orders/{id}/cancel
POST /api/v1/inputs/orders/{id}/dispatch (admin assigns driver)
POST /api/v1/inputs/orders/{id}/confirm-delivery

-- Supplier portal
GET  /api/v1/inputs/supplier/products    (supplier manages own products)
POST /api/v1/inputs/supplier/products
PATCH /api/v1/inputs/supplier/products/{id}
GET  /api/v1/inputs/supplier/orders      (incoming orders to fulfil)
PATCH /api/v1/inputs/supplier/orders/{id}/process
```

### Jobs
- `DispatchInputOrder` — calls Smart Dispatch Agent to assign best driver
  for reverse-route delivery (supplier → farm gate)
- `NotifyInputOrderStatus` — SMS to farmer at each status change in Amharic
- `AlertLowStockSupplier` — when product stock falls below reorder threshold,
  SMS supplier to restock

### Route Integration
Input orders reuse Phase 1's `RouteSegment` knowledge base and
`GenerateDispatchRecommendation` AI agent. The dispatch agent receives
`direction: reverse` flag and prioritises drivers who are already returning
empty from a harvest delivery in the same region (backhaul optimisation —
reduces cost for both farmer and driver).

---

## EXTENSION 14: Buyer Subscription & Forward Contracts

### Purpose
Let buyers lock in future produce supply at agreed prices before harvest,
giving farmers price certainty and buyers supply certainty. Platform holds
a deposit in escrow.

### Database Migrations

```sql
-- forward_contracts
id, uuid, company_id,
buyer_id (FK → contacts),
cooperative_id (FK → contacts nullable),
crop_type, quality_grade (enum: A|B|C|any),
quantity_kg (decimal 12,3),
price_per_kg_etb (decimal 10,2),
total_value_etb (decimal 12,2),          -- computed: quantity * price
deposit_pct (decimal 5,2 default 20.00), -- % held in escrow upfront
deposit_amount_etb (decimal 12,2),
balance_amount_etb (decimal 12,2),
delivery_woreda_id (FK nullable),
delivery_from_date (date),
delivery_to_date (date),
status (enum: draft|proposed|accepted|active|
              partially_fulfilled|fulfilled|cancelled|disputed|expired),
fulfilment_pct (decimal 5,2 default 0),
matched_lot_ids (JSON),                  -- aggregation lots fulfilling this contract
deposit_escrow_id (FK → escrow_holds nullable),
cancellation_reason, cancelled_by (FK → users nullable),
expires_at, accepted_at, created_at, updated_at

-- contract_fulfilments
id, uuid, contract_id (FK), lot_id (FK → aggregation_lots nullable),
order_id (FK → orders nullable), company_id,
quantity_kg (decimal 12,3),
price_per_kg_etb (decimal 10,2),
amount_etb (decimal 12,2),
quality_grade_achieved (enum: A|B|C),
fulfilment_date (date),
notes, created_at

-- buyer_subscriptions
id, uuid, company_id, buyer_id (FK → contacts),
crop_type, quality_grade (enum: A|B|C|any),
region_id (FK nullable), woreda_id (FK nullable),
min_quantity_kg (decimal 10,3),
max_quantity_kg (decimal 10,3),
max_price_per_kg_etb (decimal 10,2),
frequency (enum: weekly|biweekly|monthly|seasonal|one_time),
auto_match (bool default true),
is_active (bool default true),
notification_channel (enum: sms|email|both),
created_at, updated_at
```

### Service

**File:** `src/Services/Contracts/ForwardContractService.php`

```php
public function propose(array $data): ForwardContract {}
    // Buyer proposes contract → status: proposed
    // Notifies matching cooperatives via SMS

public function accept(string $contractId,
                        string $cooperativeId): ForwardContract {}
    // Cooperative accepts → status: active
    // Triggers deposit escrow hold on buyer wallet

public function matchToLots(string $contractId): array {}
    // Finds open/locked aggregation lots matching contract criteria
    // Auto-assigns lots to fulfil contract quantity
    // Updates fulfilment_pct

public function recordFulfilment(string $contractId,
                                   string $lotId, float $quantityKg): void {}
    // Records a partial or full fulfilment
    // Updates fulfilment_pct on contract
    // Triggers balance payment release for fulfilled portion

public function cancelContract(string $contractId,
                                 string $reason, string $cancelledBy): void {}
    // Handles deposit refund rules:
    // - Buyer cancels: forfeit 50% of deposit to cooperative
    // - Cooperative cancels: full deposit refund to buyer
    // - Mutual: full refund
```

### API Endpoints
```
POST /api/v1/contracts/forward           (buyer proposes contract)
GET  /api/v1/contracts/forward           (list — buyer sees own, coop sees matching)
GET  /api/v1/contracts/forward/{id}
POST /api/v1/contracts/forward/{id}/accept    (cooperative accepts)
POST /api/v1/contracts/forward/{id}/cancel
GET  /api/v1/contracts/forward/{id}/fulfilments

POST /api/v1/subscriptions               (buyer creates standing order alert)
GET  /api/v1/subscriptions
PATCH /api/v1/subscriptions/{id}
DELETE /api/v1/subscriptions/{id}
```

### Jobs
- `MatchSubscriptionToListings` — runs on every new HarvestListing::created,
  checks all active buyer subscriptions for matches, notifies matching buyers
- `MatchContractToLots` — runs when AggregationLotLocked, checks open
  contracts for crop+grade+region match, auto-fulfils if `auto_match=true`
- `AlertContractExpiry` — 7 days before `expires_at`, SMS both parties
- `ProcessContractDeposit` — on cooperative acceptance, holds deposit in
  escrow via EscrowHoldJob

---

## EXTENSION 15: Crop Disease Early Warning Agent

### Database Migrations

```sql
-- disease_reports
id, uuid, company_id,
reported_by (FK → contacts), farmer_id (FK → contacts),
woreda_id (FK), kebele_id (FK nullable),
crop_type, growth_stage
  (enum: seedling|vegetative|flowering|grain_fill|maturity),
symptoms_description (text),             -- Amharic or English freetext
symptoms_language (enum: am|en),
photos (JSON — array of S3 URLs),
ai_diagnosis (JSON — agent output),
disease_name_en, disease_name_am,
severity (enum: low|medium|high|critical nullable),
treatment_recommended (text nullable),
is_verified (bool default false),
verified_by (FK → users nullable),
verified_disease_name_en, verified_disease_name_am,
alert_broadcast (bool default false),    -- true = regional alert sent
alert_broadcast_at, created_at, updated_at

-- disease_alerts
id, uuid, company_id,
disease_report_id (FK),
alert_type (enum: local|woreda|zone|regional),
affected_woreda_ids (JSON),
crop_type, disease_name_en, disease_name_am,
severity, message_am (text), message_en (text),
recipients_count (int), sent_at, created_at
```

### API Endpoints
```
POST /api/v1/diseases/reports            (farmer/extension worker submits report)
GET  /api/v1/diseases/reports?woreda_id=&crop_type=&severity=
GET  /api/v1/diseases/reports/{id}
POST /api/v1/diseases/reports/{id}/verify    (agronomist verifies AI diagnosis)
POST /api/v1/diseases/reports/{id}/broadcast (admin broadcasts alert to region)
GET  /api/v1/diseases/alerts?region_id=&crop_type=
```

### AI Agent (Python FastAPI)
```
POST /agents/disease-diagnosis
Input:  {
  symptoms_description: str,
  language: "am" | "en",
  crop_type: str,
  growth_stage: str,
  woreda_id: str,
  photos: list[str]  -- S3 URLs, agent uses vision
}
Output: {
  disease_name_en: str,
  disease_name_am: str,
  confidence: float,
  severity: str,
  symptoms_matched: list[str],
  treatment_steps_am: list[str],
  treatment_steps_en: list[str],
  spread_risk: str,
  should_alert_region: bool,
  fao_reference: str | None
}
```
Agent uses Claude's vision capability on uploaded photos + FAO crop
disease database (loaded as knowledge base in context) + Ethiopian crop
calendar to assess severity and spread risk.

### Jobs
- `DiagnoseCropDisease` — triggered on DiseaseReport::created, calls
  AI agent, updates report with diagnosis
- `BroadcastDiseaseAlert` — when `should_alert_region=true` and admin
  confirms, sends SMS to all farmers in affected woredas with the same crop
- `EscalateHighSeverityDisease` — if severity=critical, auto-notifies
  platform admin + sends alert without waiting for verification

---

## EXTENSION 16: Weather-Triggered Smart Alerts

### Database Migrations

```sql
-- weather_alerts
id, uuid, company_id,
alert_type (enum: heavy_rain|drought|frost|hail|
                   flood_risk|extreme_heat|strong_wind),
severity (enum: watch|warning|emergency),
affected_woreda_ids (JSON),
affected_region_ids (JSON),
forecast_data (JSON — raw Open-Meteo response),
valid_from (timestamp), valid_until (timestamp),
active_shipment_ids (JSON),   -- shipments at risk
active_listing_ids (JSON),    -- listings at risk (crop spoilage)
ai_recommendations (JSON),    -- agent output
notification_sent (bool default false),
notification_sent_at, created_at

-- shipment_weather_impacts
id, uuid, company_id,
shipment_id (FK), weather_alert_id (FK),
impact_type (enum: delay_risk|route_closure|
                    spoilage_risk|cold_chain_risk),
recommended_action (text),
action_taken (text nullable),
action_taken_by (FK → users nullable),
created_at
```

### Weather Monitoring Service

**File:** `src/Services/Weather/WeatherMonitoringService.php`

```php
public function fetchForecast(float $lat, float $lng,
                               int $days = 7): array {}
    // Calls Open-Meteo API for Ethiopian region
    // Returns: precipitation_mm, temp_max/min, wind_speed, weather_code

public function checkActiveShipments(): void {}
    // For every active shipment, fetches weather along route
    // If threshold exceeded, fires WeatherAlertTriggered event

public function checkHarvestListings(): void {}
    // For open harvest listings, checks weather at storage location
    // Alerts cooperative if conditions risk crop spoilage

public function getEthiopianSeasonContext(string $woredaId): string {}
    // Returns: kiremt|belg|bega|small_rains
    // Contextualises weather severity for Ethiopian seasons
```

### Alert Thresholds (configurable in tenant_configurations)
```
heavy_rain:   precipitation > 40mm/day
flood_risk:   precipitation > 80mm/day OR 3 consecutive days > 30mm
frost:        temp_min < 4°C (affects highland crops: teff, barley, wheat)
extreme_heat: temp_max > 38°C (affects lowland produce storage)
strong_wind:  wind_speed > 60 km/h (hail + crop damage risk)
```

### AI Agent (Python FastAPI)
```
POST /agents/weather-alert
Input:  {
  weather_data: dict,
  affected_shipments: list[dict],
  affected_listings: list[dict],
  season_context: str,
  woreda_ids: list[str]
}
Output: {
  alert_type: str,
  severity: str,
  affected_shipment_recommendations: list[{
    shipment_id, action, urgency, alternative_route_id
  }],
  affected_listing_recommendations: list[{
    listing_id, action, urgency
  }],
  dispatcher_message_am: str,
  dispatcher_message_en: str,
  farmer_sms_am: str
}
```

### Jobs & Scheduling
- `CheckWeatherForecasts` — scheduled every 6 hours, checks Open-Meteo
  for all active woreda zones with open shipments or listings
- `ProcessWeatherAlert` — triggered on WeatherAlertTriggered event,
  calls AI agent, creates weather_alert record, sends notifications
- `RerouteAtRiskShipments` — if agent recommends rerouting, calls
  Smart Dispatch Agent with `avoid_woredas` parameter

### API Endpoints
```
GET /api/v1/weather/alerts?woreda_id=&active=true
GET /api/v1/weather/alerts/{id}
GET /api/v1/weather/forecast?woreda_id=&days=7
GET /api/v1/weather/shipment-impacts?shipment_id=
```

---

## EXTENSION 17: Price Negotiation Agent

### Database Migrations

```sql
-- price_negotiations
id, uuid, company_id,
listing_id (FK → harvest_listings),
buyer_id (FK → contacts),
seller_id (FK → contacts),    -- farmer or cooperative
status (enum: open|countered|accepted|rejected|expired|withdrawn),
initial_offer_etb (decimal 10,2),
final_agreed_price_etb (decimal 10,2 nullable),
turns (tinyint default 0),
max_turns (tinyint default 5),
ai_suggested_price_etb (decimal 10,2),
ecx_reference_price_etb (decimal 10,2),
expires_at,
created_at, updated_at

-- negotiation_turns
id, uuid, negotiation_id (FK), company_id,
turn_number (tinyint),
offered_by (FK → contacts),
offered_by_role (enum: buyer|seller),
price_etb (decimal 10,2),
message_am (text nullable),
message_en (text nullable),
ai_analysis (JSON),           -- agent commentary on this offer
responded_at (timestamp nullable),
created_at
```

### Negotiation Flow
```
1. Buyer sees listing → makes initial offer below asking price
2. NegotiationStarted event fired
3. PriceNegotiationAgent analyses:
   - ECX reference price, Mercato spot, listing quality grade
   - Days listing has been active (urgency for seller)
   - Buyer's purchase history and reliability score
   - Seasonal supply/demand context
4. Agent suggests fair midpoint + reasoning to both parties
5. Seller counters or accepts
6. Max 5 turns — if no agreement, negotiation expires
7. On acceptance → auto-creates Order from listing at agreed price
```

### AI Agent (Python FastAPI)
```
POST /agents/price-negotiation
Input:  {
  listing_id: str,
  crop_type: str,
  quality_grade: str,
  asking_price_etb: float,
  current_offer_etb: float,
  ecx_price_etb: float,
  days_listed: int,
  buyer_reliability_score: int,
  seller_credit_score: int,
  turn_number: int,
  negotiation_history: list[dict]
}
Output: {
  fair_price_etb: float,
  suggested_counter_etb: float,
  reasoning_am: str,
  reasoning_en: str,
  buyer_message_am: str,
  seller_message_am: str,
  recommend_accept: bool,
  urgency_signal: str   -- "seller should accept — 8 similar lots listed nearby"
}
```

### API Endpoints
```
POST /api/v1/negotiations                    (buyer initiates)
GET  /api/v1/negotiations/{id}
POST /api/v1/negotiations/{id}/counter       (seller counters)
POST /api/v1/negotiations/{id}/accept
POST /api/v1/negotiations/{id}/reject
POST /api/v1/negotiations/{id}/withdraw
GET  /api/v1/negotiations/my-negotiations?role=buyer|seller
GET  /api/v1/negotiations/{id}/ai-suggestion (both parties can ask agent)
```

### Jobs
- `ExpireStaleNegotiations` — scheduled hourly, expires negotiations
  past `expires_at`
- `NotifyNegotiationTurn` — SMS to the party whose turn it is to respond
- `CreateOrderFromAcceptedNegotiation` — on NegotiationAccepted,
  auto-creates Order at `final_agreed_price_etb`

---

## EXTENSION 18: Harvest Yield Prediction

### Database Migrations

```sql
-- yield_predictions
id, uuid, company_id,
farmer_id (FK → contacts), cooperative_id (FK nullable),
crop_type,
land_area_timad (decimal 8,3),  -- Ethiopian unit: 1 timad ≈ 0.25 hectare
land_area_hectares (decimal 8,4), -- computed
woreda_id (FK), kebele_id (FK nullable),
planting_date (date),
expected_harvest_date (date),
predicted_yield_kg (decimal 10,3),
predicted_yield_range_low_kg (decimal 10,3),
predicted_yield_range_high_kg (decimal 10,3),
confidence_pct (tinyint),
ai_factors (JSON),   -- which factors drove the prediction
actual_yield_kg (decimal 10,3 nullable),  -- filled in after harvest
prediction_accuracy_pct (decimal 5,2 nullable),  -- computed on harvest
weather_snapshot (JSON),   -- weather data at time of prediction
model_version (varchar 10),
created_at, updated_at

-- farm_plots
id, uuid, company_id,
farmer_id (FK → contacts),
name, woreda_id (FK), kebele_id (FK nullable),
landmark_id (FK nullable),
area_timad (decimal 8,3),
soil_type (enum: clay|sandy|loam|silt|mixed nullable),
irrigation (enum: rain_fed|irrigated|mixed),
elevation_m (int nullable),
typical_crops (JSON),
created_at, updated_at
```

### AI Agent (Python FastAPI)
```
POST /agents/yield-prediction
Input:  {
  crop_type: str,
  land_area_timad: float,
  woreda_id: str,
  planting_date: str,
  soil_type: str | None,
  irrigation_type: str,
  elevation_m: int | None,
  weather_forecast: dict,          -- Open-Meteo 90-day forecast
  historical_regional_yields: list[dict],  -- from platform history
  farmer_historical_yields: list[dict]     -- farmer's own past predictions vs actual
}
Output: {
  predicted_yield_kg: float,
  range_low_kg: float,
  range_high_kg: float,
  expected_harvest_date: str,
  confidence_pct: int,
  key_factors: list[str],
  risks: list[str],
  recommendations: list[str],
  comparable_farms_avg_kg: float
}
```

### API Endpoints
```
POST /api/v1/yield/predict               (farmer inputs planting data)
GET  /api/v1/yield/predictions?farmer_id=&season=
GET  /api/v1/yield/predictions/{id}
PATCH /api/v1/yield/predictions/{id}/actual  (record actual yield after harvest)
POST /api/v1/yield/farm-plots            (farmer registers a named plot)
GET  /api/v1/yield/farm-plots
GET  /api/v1/yield/regional-averages?crop_type=&woreda_id=&season=
```

### Jobs
- `GenerateYieldPrediction` — triggered on YieldPredictionRequested,
  fetches weather, historical data, calls AI agent
- `UpdatePredictionAccuracy` — when `actual_yield_kg` is submitted,
  computes accuracy %, stores for model improvement feedback loop
- `NotifyUpcomingHarvest` — 14 days before `expected_harvest_date`,
  SMS farmer and cooperative: "Your [crop] harvest is expected in 2 weeks.
  Start a listing now to attract buyers."

---

## EXTENSION 19: Government Data Export API

### Purpose
Read-only, API-key authenticated access for Ministry of Agriculture,
Ethiopian Commodity Exchange, and approved NGOs to pull aggregate
platform data without accessing individual farmer records.

### Database Migrations

```sql
-- government_api_keys
id, uuid, company_id (nullable — platform-level),
organisation_name, contact_name, contact_email,
api_key (hashed), api_key_prefix (first 8 chars — for display),
scope (JSON: [supply_data|price_data|transport_data|
               income_data|export_data|disease_data]),
rate_limit_per_hour (int default 100),
allowed_regions (JSON nullable),  -- null = all regions
allowed_crop_types (JSON nullable),
is_active (bool default true),
last_used_at, expires_at, created_at, updated_at

-- government_data_requests
id, uuid, api_key_id (FK),
endpoint, query_params (JSON),
response_rows (int), response_size_bytes (int),
duration_ms (int), created_at
```

### Middleware
`EnsureGovernmentApiKey` — validates `X-Gov-API-Key` header, checks scope,
enforces rate limit via Redis, logs every request to `government_data_requests`

### API Endpoints (all read-only, separate route group `/api/gov/v1/`)
```
-- Supply & Production
GET /api/gov/v1/supply/summary
    ?crop_type=&region_id=&date_from=&date_to=&group_by=week|month|season
    → { period, crop_type, region, total_kg, total_listings,
        avg_price_etb, cooperatives_active, farmers_active }

GET /api/gov/v1/supply/heatmap
    → GeoJSON FeatureCollection — regions with supply density metrics

GET /api/gov/v1/supply/crop-calendar
    ?region_id=&year=
    → crop availability by month, actual vs historical

-- Prices
GET /api/gov/v1/prices/history
    ?crop_type=&region_id=&date_from=&date_to=
    → weekly price time series, min/max/avg, ECX comparison

GET /api/gov/v1/prices/realtime
    → current avg platform price per crop per region (last 7 days)

-- Transport & Logistics
GET /api/gov/v1/transport/summary
    ?region_id=&date_from=&date_to=
    → total shipments, avg transit hours, on-time pct, incident rate

GET /api/gov/v1/transport/bottlenecks
    → route segments with highest delay, grouped by region

-- Farmer Income (fully anonymised — no individual farmer data)
GET /api/gov/v1/income/summary
    ?region_id=&season=&crop_type=
    → avg income per farmer, income distribution bands, top crop by income

-- Disease & Alerts
GET /api/gov/v1/disease/reports
    ?region_id=&crop_type=&severity=&date_from=&date_to=
    → aggregate disease reports — no farmer PII

-- Export
GET /api/gov/v1/export/pdf?report=supply_summary|price_history|...
GET /api/gov/v1/export/excel?report=farmer_income|transport_summary|...
GET /api/gov/v1/export/geojson?layer=supply_heatmap|disease_map|...
```

### Admin Endpoints (platform admin only)
```
POST /api/v1/admin/gov-api-keys          (issue new key)
GET  /api/v1/admin/gov-api-keys
PATCH /api/v1/admin/gov-api-keys/{id}    (change scope, revoke)
GET  /api/v1/admin/gov-api-keys/{id}/usage-log
```

---

## EXTENSION 20: NGO Programme Management

### Database Migrations

```sql
-- ngo_programmes
id, uuid, company_id,
ngo_name, programme_name, programme_code (unique),
description, target_crop_types (JSON),
target_region_ids (JSON), target_woreda_ids (JSON),
beneficiary_count_target (int),
start_date (date), end_date (date nullable),
status (enum: active|completed|suspended),
contact_name, contact_email, contact_phone,
government_api_key_id (FK nullable),
created_at, updated_at

-- ngo_beneficiaries
id, uuid, programme_id (FK), farmer_id (FK → contacts), company_id,
enrolment_date (date),
support_type (enum: seeds|fertiliser|training|finance|market_access|all),
status (enum: active|graduated|withdrawn|suspended),
notes, enrolled_by (FK → users), created_at, updated_at

-- ngo_impact_snapshots
id, uuid, programme_id (FK), company_id,
snapshot_date (date),
active_beneficiaries (int),
total_kg_sold (decimal 12,3),
total_income_etb (decimal 14,2),
avg_income_per_farmer_etb (decimal 12,2),
avg_quality_grade,
orders_completed (int),
credit_score_avg (decimal 5,2),
credit_score_improved_count (int),
data_period_from (date), data_period_to (date),
created_at
```

### API Endpoints
```
-- NGO admin (scoped to their programme)
POST /api/v1/ngo/programmes
GET  /api/v1/ngo/programmes/{id}
POST /api/v1/ngo/programmes/{id}/beneficiaries    (enrol farmer)
GET  /api/v1/ngo/programmes/{id}/beneficiaries
DELETE /api/v1/ngo/programmes/{id}/beneficiaries/{farmerId}
GET  /api/v1/ngo/programmes/{id}/impact           (latest snapshot)
GET  /api/v1/ngo/programmes/{id}/impact/history
GET  /api/v1/ngo/programmes/{id}/export/pdf       (impact report PDF)
GET  /api/v1/ngo/programmes/{id}/export/excel
```

### Jobs
- `GenerateNgoProgrammeSnapshot` — scheduled weekly (Monday 3am EAT),
  computes all impact metrics for all active programmes
- `GenerateNgoImpactReport` — on demand or scheduled monthly,
  generates formatted PDF impact report (DomPDF) with charts and key metrics
- `AlertNgoLowEngagement` — if a beneficiary has had no platform activity
  for 30 days, flag for NGO field officer follow-up

### Impact Report PDF Sections
- Programme summary (dates, targets, status)
- Beneficiary count and status breakdown
- Total income generated (ETB) + % above baseline
- Crop quality improvement trend
- Credit score distribution shift (before vs after enrolment)
- Top performing beneficiaries (anonymised — ranked by income)
- Map: geographic distribution of beneficiaries

---

## EXTENSION 21: Carbon Credit Tracking

### Database Migrations

```sql
-- carbon_emissions_log
id, uuid, company_id,
shipment_id (FK), order_id (FK),
vehicle_type (enum: motorcycle|pickup|truck_3t|truck_7t|truck_15t|refrigerated),
distance_km (decimal 8,2),
load_kg (decimal 10,3),
load_factor (decimal 4,3),          -- actual load / max capacity
fuel_type (enum: diesel|petrol|electric|hybrid),
emission_factor_kg_per_km (decimal 8,5), -- from IPCC emission factors
gross_emissions_kg_co2 (decimal 10,4),
load_adjusted_emissions_kg_co2 (decimal 10,4), -- attributed to this shipment
sequestration_credit_kg_co2 (decimal 10,4 default 0), -- future: reforestation offset
net_emissions_kg_co2 (decimal 10,4),
calculation_method (varchar 50 default 'IPCC_2006'),
created_at

-- carbon_credit_reports
id, uuid, company_id,
cooperative_id (FK nullable),
report_period_from (date), report_period_to (date),
total_shipments (int),
total_distance_km (decimal 12,2),
total_gross_emissions_kg_co2 (decimal 14,4),
total_net_emissions_kg_co2 (decimal 14,4),
emissions_per_kg_produce (decimal 8,6),  -- kg CO2 per kg of produce transported
vs_industry_benchmark_pct (decimal 6,2), -- % above/below industry avg
eligible_for_offset (bool),
offset_programme (varchar 100 nullable),
report_pdf_url, report_hash,
created_at
```

### Emission Factor Constants (configurable in config/tera_harvest.php)
```php
'emission_factors' => [
    'motorcycle'    => 0.103,  // kg CO2 per km
    'pickup'        => 0.171,
    'truck_3t'      => 0.245,
    'truck_7t'      => 0.320,
    'truck_15t'     => 0.498,
    'refrigerated'  => 0.612,
],
'industry_benchmark_kg_co2_per_kg_produce' => 0.045,
```

### Service

**File:** `src/Services/Carbon/CarbonTrackingService.php`

```php
public function logShipmentEmissions(string $shipmentId): CarbonEmissionsLog {}
    // Called on ShipmentCompleted event
    // Reads: vehicle type, actual distance from route_history, load weight
    // Computes load_factor = actual_kg / vehicle max capacity
    // Applies IPCC emission factor, adjusts for load factor
    // Stores result

public function generateCooperativeReport(string $cooperativeId,
                                            string $from, string $to): CarbonCreditReport {}
    // Aggregates all shipments for cooperative in period
    // Computes summary metrics
    // Compares vs industry benchmark
    // Generates PDF report (exportable to carbon offset programmes)
```

### API Endpoints
```
GET /api/v1/carbon/shipments/{shipmentId}/emissions
GET /api/v1/carbon/reports?cooperative_id=&period_from=&period_to=
POST /api/v1/carbon/reports/generate
GET /api/v1/carbon/reports/{id}/download
GET /api/v1/carbon/summary?company_id=&year=
    → { total_emissions_kg_co2, vs_benchmark_pct, top_emitting_routes,
        most_efficient_drivers, recommendations[] }
```

### Jobs
- `LogShipmentCarbon` — triggered on ShipmentCompleted event
- `GenerateMonthlyCarborReport` — scheduled 1st of month, generates
  reports for all cooperatives with > 10 shipments in prior month

---

## EXTENSION 22: Driver Earnings Dashboard (Backend API)

### Database Migrations

```sql
-- driver_earnings_summary
id, uuid, company_id, driver_id (FK → contacts),
period_type (enum: daily|weekly|monthly),
period_start (date), period_end (date),
trips_completed (int),
trips_cancelled (int),
total_distance_km (decimal 10,2),
gross_earnings_etb (decimal 12,2),
platform_fee_etb (decimal 10,2),
net_earnings_etb (decimal 12,2),
avg_rating (decimal 3,2),
on_time_pct (decimal 5,2),
incidents_reported (int),
is_top_driver (bool default false),   -- top 10% in region for the period
created_at, updated_at

-- driver_leaderboard
id, uuid, company_id,
region_id (FK), period_type, period_start, period_end,
rankings (JSON: [{driver_id, rank, trips, earnings_etb, rating, badge}]),
generated_at, created_at
```

### API Endpoints
```
GET /api/v1/driver/earnings/me
    ?period=today|this_week|this_month|last_month
    → { trips_completed, gross_earnings_etb, net_earnings_etb,
        avg_rating, on_time_pct, rank_in_region, badge }

GET /api/v1/driver/earnings/me/history
    ?period_type=weekly|monthly&limit=12
    → time series of earnings summaries

GET /api/v1/driver/earnings/me/trips
    ?date_from=&date_to=
    → individual trip breakdown with per-trip earnings

GET /api/v1/driver/leaderboard?region_id=&period=this_week
    → top 10 drivers in region (anonymised beyond top 3)

GET /api/v1/driver/earnings/me/badges
    → earned badges: first_trip, 10_trips, 50_trips, top_driver,
                      5_star_week, perfect_month, zero_incidents

-- Admin
GET /api/v1/admin/drivers/earnings?region_id=&period=
GET /api/v1/admin/drivers/top-performers?region_id=&limit=20
```

### Badge System
```
first_delivery:    First completed trip
reliable_10:       10 trips with on_time_pct > 90%
volume_50:         50 completed trips
top_regional:      Top 10% earner in region for the week
five_star_week:    avg_rating = 5.0 for a full week (min 5 trips)
perfect_month:     Zero incidents + on_time_pct > 95% for calendar month
cold_chain_pro:    10+ refrigerated shipments with zero temp exceedances
```

### Jobs
- `GenerateDriverEarningsSummary` — scheduled daily at midnight EAT,
  computes daily/weekly/monthly summaries for all active drivers
- `GenerateRegionalLeaderboard` — scheduled every Monday 5am EAT
- `AwardDriverBadges` — runs after earnings summary, checks badge
  criteria and awards new badges, notifies driver via SMS in Amharic:
  "እንኳን ደስ አለዎ! የ'ወር ምርጥ ሾፌር' ሽልማት አገኙ!" (Congratulations! You earned Top Driver of the Month!)
- `NotifyWeeklyEarningsSummary` — every Sunday evening, SMS each
  driver their weekly summary in Amharic

---

## NEW AI SERVICE ADDITIONS (ai-service/routers/agents.py)

Add these 5 new agent endpoints to the existing FastAPI routers:

```python
POST /agents/credit-score-explain      # Extension 11
POST /agents/disease-diagnosis         # Extension 15
POST /agents/weather-alert             # Extension 16
POST /agents/price-negotiation         # Extension 17
POST /agents/yield-prediction          # Extension 18
```

Add these 5 new tools to `tools.py`:
```python
get_farmer_credit_score(farmer_id)
get_disease_knowledge_base(crop_type)    # FAO pest/disease DB loaded as context
get_regional_yield_history(crop_type, woreda_id, seasons=5)
get_negotiation_history(listing_id)
get_driver_performance_history(driver_id)
```

---

## NEW LARAVEL EVENTS TO ADD

```php
// Extension 11
FarmerCreditScoreImproved
FarmerCreditScoreBandChanged

// Extension 12
AggregationLotLocked
AggregationLotMatched
AggregationLotPaymentSplit

// Extension 14
ForwardContractAccepted
ForwardContractFulfilled
ForwardContractCancelled

// Extension 15
DiseaseReportSubmitted
DiseaseDiagnosisComplete
DiseaseAlertBroadcast

// Extension 16
WeatherAlertTriggered
ShipmentAtWeatherRisk

// Extension 17
NegotiationStarted
NegotiationCountered
NegotiationAccepted
NegotiationExpired

// Extension 18
YieldPredictionGenerated
HarvestApproaching

// Extension 21
ShipmentCompleted   (if not already in Phase 1)
CarbonReportGenerated

// Extension 22
DriverBadgeAwarded
```

---

## NEW PHPUNIT TESTS TO WRITE

```
tests/Feature/CreditScoringTest.php
    - score calculated correctly from order history
    - score band changes trigger SMS
    - anonymised credit package contains no PII

tests/Feature/BulkAggregationTest.php
    - pro_rata split sums to 100% exactly (bcmath)
    - payment split handles rounding residual correctly
    - lot auto-locks at target quantity

tests/Feature/ForwardContractTest.php
    - deposit held on acceptance
    - buyer cancellation forfeits 50% of deposit
    - cooperative cancellation triggers full refund
    - contract auto-fulfils when matching lot locks

tests/Feature/PriceNegotiationTest.php
    - negotiation expires after max_turns
    - accepted negotiation auto-creates order
    - both parties can access AI suggestion

tests/Feature/CarbonTrackingTest.php
    - emission calculation matches IPCC formula
    - load factor correctly adjusts gross emissions
    - report PDF generates and hash stored

tests/Feature/GovernmentApiTest.php
    - api key scope enforced
    - rate limit enforced via Redis
    - no individual farmer PII in any response
    - all endpoints return anonymised aggregates only
```

---

## IMPLEMENTATION ORDER (4 Weeks)

### Week 1 — High-Impact Business Features
- Extension 11: Farmer Credit Scoring (models + scoring service + API + jobs)
- Extension 12: Bulk Aggregation Engine (models + service + pro-rata split)
- Extension 14: Forward Contracts (models + escrow deposit + fulfilment)
- Wire new events to existing SMS notification system

### Week 2 — Reverse Logistics + Negotiations
- Extension 13: Seasonal Input Supply (supplier portal + reverse dispatch)
- Extension 17: Price Negotiation (turns + AI agent + auto-order creation)
- Extension 18: Yield Prediction (farm plots + AI agent + harvest alerts)

### Week 3 — AI Agents + Institutional
- Extension 15: Crop Disease Agent (vision + FAO KB + broadcast alerts)
- Extension 16: Weather Alerts (Open-Meteo polling + rerouting)
- Extension 19: Government Data Export API (scoped keys + all endpoints)
- Extension 20: NGO Programme Management (beneficiaries + impact reports)

### Week 4 — Carbon + Driver + Testing
- Extension 21: Carbon Credit Tracking (IPCC calculations + reports)
- Extension 22: Driver Earnings Dashboard (summaries + leaderboard + badges)
- All PHPUnit tests
- End-to-end integration: full order lifecycle touching all 12 new extensions

---

## NON-NEGOTIABLES (same as Phase 1, plus)

11. Credit scores must never expose raw transaction data to microfinance partners
12. All negotiation AI suggestions must be clearly labelled as AI-generated
13. Government API must return zero individual farmer PII — enforce at query level
14. Carbon calculations must reference IPCC 2006 emission factors in all reports
15. Driver leaderboard beyond top 3 must be anonymised
16. Disease alerts must require admin confirmation before regional broadcast
    (exception: severity=critical → auto-broadcast after 30 min if unconfirmed)
17. Yield predictions must display confidence % prominently — never presented as certain
18. All bcmath usage: scale=2 for ETB amounts, scale=6 for CO2 calculations