TERA HARVEST — Backend Engineering Prompt
Ethiopian Agricultural Supply Chain Platform
Fleetbase Fork — Backend Additions & Customisations Only

CONTEXT & MISSION
You are a senior backend engineer working on Tera Harvest, an Ethiopian
agricultural supply chain platform forked from Fleetbase — an open-source
logistics OS built on Laravel (PHP), MySQL, and Redis.
Your task is backend only. Do not touch the Ember.js frontend. Do not
build mobile apps. Build only what lives in the /server directory — Laravel
packages, service providers, API endpoints, database migrations, queue jobs,
event sourcing, and AI microservices.
Every addition must be:

A proper Fleetbase extension (Laravel package under /server/src)
Registered via a ServiceProvider
Exposed via versioned RESTful API endpoints (/api/v1/...)
Covered by database migrations and Eloquent models
Protected by RBAC middleware (Fleetbase's built-in policy system)
Validated with Form Request classes
Tested with PHPUnit feature tests


BACKEND STACK
LayerTechnologyCore FrameworkLaravel 11 (PHP 8.2+)DatabaseMySQL 8 (primary) + Redis 7 (cache/queue)Queue DriverRedis via Laravel HorizonEvent BusLaravel Events + ListenersFile StorageS3-compatible (MinIO local / AWS S3 prod)SMS / USSDAfrica's Talking SDK (PHP)PaymentsChapa PHP SDK + Telebirr REST API + CBE Birr APIAI MicroservicePython FastAPI (separate service, called via HTTP)Agent OrchestrationLangGraph (Python microservice)Agent ProtocolMCP server exposing Laravel API as toolsObservabilityLaravel Telescope + Langfuse (AI calls)Task SchedulingLaravel Scheduler (cron jobs)WebSocketsLaravel Reverb (real-time order/driver updates)Document GenerationLaravel + DomPDF / Spatie Laravel PDFTestingPHPUnit + Laravel HTTP testsContainerisationDocker + Docker Compose

EXTENSION 1: Ethiopian Administrative Hierarchy
Purpose
Replace Fleetbase's generic address system with Ethiopia's official
administrative structure: Region → Zone → Woreda → Kebele.
Database Migrations
sql-- ethiopia_regions
id, name_en, name_am, code, geometry (POLYGON), created_at, updated_at

-- ethiopia_zones
id, region_id (FK), name_en, name_am, code, created_at, updated_at

-- ethiopia_woredas
id, zone_id (FK), name_en, name_am, code, created_at, updated_at

-- ethiopia_kebeles
id, woreda_id (FK), name_en, name_am, created_at, updated_at

-- ethiopia_landmarks
id, kebele_id (FK), name_en, name_am, description, latitude, longitude,
   photo_url, confirmed_count (driver confirmations), created_by,
   created_at, updated_at
Seeders

Seed all 12 regions, 68+ zones, 900+ woredas from official MoA data
Seed major landmarks in Addis Ababa, Dire Dawa, Jimma, Hawassa,
Mekelle, Gondar, Adama as starting dataset

API Endpoints
GET  /api/v1/ethiopia/regions
GET  /api/v1/ethiopia/regions/{id}/zones
GET  /api/v1/ethiopia/zones/{id}/woredas
GET  /api/v1/ethiopia/woredas/{id}/kebeles
GET  /api/v1/ethiopia/landmarks?kebele_id=&lat=&lng=&radius=
POST /api/v1/ethiopia/landmarks          (drivers submit new landmarks)
POST /api/v1/ethiopia/landmarks/{id}/confirm  (driver confirms landmark)
Eloquent Models
EthiopiaRegion, EthiopiaZone, EthiopiaWoreda, EthiopiaKebele,
EthiopiaLandmark
AI Landmark Resolver (calls Python microservice)
POST /api/v1/ethiopia/landmarks/resolve
Body: { "description": "near bole medhanealem, blue corrugated gate" }
Response: { landmark_id, confidence, lat, lng, suggested_name }
The Laravel controller calls the FastAPI AI microservice which uses Claude
to fuzzy-match the description against the landmark database + OSM Ethiopia.

EXTENSION 2: Harvest Listing & Produce Marketplace
Purpose
A structured produce marketplace where farmers and cooperatives post
available harvests for buyers to discover and order.
Database Migrations
sql-- harvest_listings
id, uuid, farmer_id (FK → contacts), cooperative_id (FK),
crop_type (enum: teff|coffee|sesame|chickpeas|wheat|sorghum|maize|
                  vegetables|fruits|pulses|spices|other),
quantity_kg (decimal 10,2), asking_price_etb (decimal 10,2),
ai_suggested_price_etb (decimal 10,2), quality_grade (enum: A|B|C|ungraded),
harvest_date (date), availability_from (date), availability_until (date),
woreda_id (FK), kebele_id (FK), landmark_id (FK),
storage_type (enum: open_air|warehouse|cold_storage),
status (enum: draft|active|matched|sold|expired|cancelled),
notes, created_by, created_at, updated_at, deleted_at

-- harvest_listing_photos
id, listing_id (FK), disk, path, url, order, created_at

-- harvest_listing_views
id, listing_id (FK), viewer_id (FK → contacts), ip_address, created_at
API Endpoints
GET    /api/v1/harvest/listings               (filter by crop, region, grade, price)
POST   /api/v1/harvest/listings               (farmer/coop creates listing)
GET    /api/v1/harvest/listings/{id}
PATCH  /api/v1/harvest/listings/{id}
DELETE /api/v1/harvest/listings/{id}
POST   /api/v1/harvest/listings/{id}/photos
DELETE /api/v1/harvest/listings/{id}/photos/{photoId}
GET    /api/v1/harvest/listings/{id}/price-intelligence
       → returns ECX price, Mercato price, suggested price, trend
POST   /api/v1/harvest/listings/{id}/match    (AI matches listing to buyers)
Jobs & Queues

SuggestHarvestPrice — on listing creation, calls AI microservice
to fetch ECX prices and suggest fair price, updates ai_suggested_price_etb
ExpireHarvestListings — scheduled daily, marks passed
availability_until listings as expired
NotifyMatchedBuyers — when a listing is created, notifies
buyers who have saved search alerts for that crop + region

Eloquent Models
HarvestListing, HarvestListingPhoto, HarvestListingView
Policies

Farmer: create, read own, update own, delete own
Cooperative: create on behalf of members, read all member listings
Buyer: read all active listings only
Admin: full CRUD


EXTENSION 3: Ethiopian Payments (Chapa + Telebirr + CBE Birr)
Purpose
Replace all non-local payment providers with Ethiopian payment gateways.
Implement escrow: buyer pays → funds held → released on confirmed delivery.
Database Migrations
sql-- payment_wallets
id, uuid, owner_id (FK → contacts), owner_type (polymorphic),
balance_etb (decimal 12,2), reserved_etb (decimal 12,2),
currency (default: ETB), status (enum: active|frozen|closed),
created_at, updated_at

-- payment_transactions
id, uuid, wallet_id (FK), order_id (FK → orders),
type (enum: deposit|withdrawal|escrow_hold|escrow_release|
            commission|refund|fee),
amount_etb (decimal 12,2), fee_etb (decimal 12,2),
provider (enum: chapa|telebirr|cbe_birr|internal),
provider_reference, provider_payload (JSON),
status (enum: pending|processing|completed|failed|reversed),
initiated_by (FK → users), notes,
created_at, updated_at

-- escrow_holds
id, uuid, order_id (FK), buyer_wallet_id (FK),
seller_wallet_id (FK), driver_commission_etb (decimal 10,2),
broker_commission_etb (decimal 10,2), amount_etb (decimal 12,2),
status (enum: held|released|refunded|disputed),
held_at, released_at, release_trigger
  (enum: delivery_confirmed|quality_approved|manual_admin),
released_by (FK → users), created_at, updated_at

-- payment_events (immutable event ledger — append only)
id, uuid, transaction_id (FK), event_type, payload (JSON),
hash (SHA-256 of previous_hash + payload — chain integrity),
previous_hash, created_at
-- NOTE: NO updated_at — this table is append-only, never updated
Payment Providers (Laravel Services)
ChapaPaymentService (app/Services/Payments/ChapaPaymentService.php)

initiate(amount, email, phone, reference, callback_url): array
verify(reference): PaymentStatus
webhook(Request): void — verifies Chapa HMAC signature, fires event

TelebirrPaymentService

initiate(amount, phone, reference): array
verify(reference): PaymentStatus
bulkDisbursement(array $payments): array — pay multiple farmers at once

CBEBirrPaymentService

initiate(amount, account_number, reference): array
verify(reference): PaymentStatus

Escrow Flow (Laravel Pipeline)
BuyerPlacesOrder
  → EscrowHoldJob (debit buyer wallet, create EscrowHold record)
  → DriverPicksUp (status update only)
  → ProofOfDeliverySubmitted
  → QualityApprovalReceived (optional, configurable per order)
  → EscrowReleaseJob
      → credit seller wallet
      → credit driver commission
      → credit broker commission (if applicable)
      → append PaymentEvent to ledger
      → SendPaymentConfirmationSMS (Africa's Talking)
API Endpoints
POST /api/v1/payments/initiate
     Body: { order_id, provider: chapa|telebirr|cbe_birr, phone, email }

POST /api/v1/payments/webhook/chapa     (public, HMAC verified)
POST /api/v1/payments/webhook/telebirr  (public, signature verified)

GET  /api/v1/payments/wallets/me
GET  /api/v1/payments/wallets/{id}/transactions
GET  /api/v1/payments/escrow/{order_id}

POST /api/v1/payments/escrow/{order_id}/release  (admin override)
POST /api/v1/payments/escrow/{order_id}/dispute

GET  /api/v1/payments/ledger/{order_id}  (immutable event chain + hash verification)
Jobs

ProcessEscrowRelease — triggered by delivery confirmation event
DisburseFarmerPayments — nightly batch for pending wallet → bank transfers
ReconcilePaymentProvider — daily, cross-checks provider records vs DB


EXTENSION 4: Quality Grading System
Purpose
Digital produce grading at collection points and markets, linked to orders
and shipments. Generates PDF quality certificates.
Database Migrations
sql-- quality_grades
id, uuid, shipment_id (FK), listing_id (FK), inspector_id (FK → contacts),
crop_type, grading_standard (enum: ecx|fao|custom),
overall_grade (enum: A|B|C|rejected),
weight_kg (decimal 10,2), moisture_pct (decimal 5,2),
foreign_matter_pct (decimal 5,2), defect_pct (decimal 5,2),
colour_score (tinyint 1-5), smell_score (tinyint 1-5),
custom_attributes (JSON),  -- extensible per crop type
notes, certificate_number (unique), certificate_pdf_url,
certificate_hash (SHA-256 — tamper-evident),
status (enum: draft|issued|disputed|revoked),
graded_at, created_at, updated_at

-- quality_grade_photos
id, grade_id (FK), disk, path, url, caption, created_at

-- cold_chain_logs
id, uuid, shipment_id (FK), sensor_id, temperature_celsius (decimal 5,2),
humidity_pct (decimal 5,2), recorded_at, lat, lng,
alert_triggered (bool), alert_acknowledged_at, created_at
API Endpoints
POST /api/v1/quality/grades              (inspector submits grade)
GET  /api/v1/quality/grades/{id}
GET  /api/v1/quality/grades/{id}/certificate  (PDF download)
POST /api/v1/quality/grades/{id}/dispute
GET  /api/v1/shipments/{id}/cold-chain   (temperature log)
POST /api/v1/cold-chain/reading          (IoT sensor POST endpoint)
Jobs

GenerateQualityCertificate — generates DomPDF certificate, uploads to S3,
stores hash, updates certificate_pdf_url
CheckColdChainThreshold — fired on every sensor reading, alerts if temp
exceeds configured threshold per crop type
PredictShelfLife — calls AI microservice with: crop type, harvest date,
storage temp history, transit time → returns predicted shelf life in days

Certificate PDF Structure

Certificate number (auto-generated: QC-YYYY-XXXXXX)
Farm/cooperative details
Crop type, grade, weight
All metric scores
Inspector name + digital signature placeholder
QR code linking to /api/v1/quality/grades/{id}/verify (public endpoint)
SHA-256 hash printed on document for tamper detection


EXTENSION 5: AI Microservice Bridge (Laravel → Python FastAPI)
Purpose
Laravel cannot run LangGraph agents natively. Build a clean HTTP bridge
so Laravel jobs can trigger AI agents and receive structured responses.
Laravel AI Client Service
File: app/Services/AI/TeraHarvestAIClient.php
phpclass TeraHarvestAIClient
{
    // All methods call the Python FastAPI microservice at AI_SERVICE_URL

    public function suggestPrice(string $cropType, float $quantityKg,
                                  string $woredaId): PriceSuggestion {}

    public function resolveAddress(string $description,
                                    ?float $lat, ?float $lng): AddressResolution {}

    public function dispatchRecommendation(string $orderId): DispatchRecommendation {}

    public function forecastDemand(string $cropType,
                                    string $regionId, int $daysAhead): DemandForecast {}

    public function checkCompliance(string $orderId): ComplianceResult {}

    public function generateInsightReport(string $tenantId,
                                           string $period): InsightReport {}

    public function predictShelfLife(array $params): ShelfLifePrediction {}

    public function draftSmsMessage(string $eventType,
                                     array $context): SmsDraft {}
}
Python FastAPI Microservice (/ai-service)
Build a standalone FastAPI service with these endpoints. Each endpoint
runs a LangGraph agent:
pythonPOST /agents/price-intelligence
     Input:  { crop_type, quantity_kg, woreda_id, listing_id }
     Output: { suggested_price_etb, ecx_price, mercato_price,
               trend_direction, confidence, reasoning }

POST /agents/address-resolver
     Input:  { description, lat?, lng?, language: am|en }
     Output: { landmark_id?, resolved_address, lat, lng,
               confidence, suggested_name_am, suggested_name_en }

POST /agents/smart-dispatch
     Input:  { order_id, available_drivers[], road_conditions[] }
     Output: { recommended_driver_id, estimated_pickup_minutes,
               reasoning, fallback_driver_id }

POST /agents/demand-forecast
     Input:  { crop_type, region_id, days_ahead, historical_data[] }
     Output: { forecast[], confidence, seasonal_factors, weather_impact }

POST /agents/compliance-check
     Input:  { order_id, documents[], crop_type, destination_country? }
     Output: { is_compliant, missing_documents[], warnings[], checklist }

POST /agents/shelf-life
     Input:  { crop_type, harvest_date, temp_log[], transit_hours }
     Output: { predicted_shelf_life_days, risk_level, recommendation }

POST /agents/sms-draft
     Input:  { event_type, context{}, language: am|en }
     Output: { message_am, message_en, character_count }

POST /agents/insight-report
     Input:  { tenant_id, period: weekly|monthly, data_summary{} }
     Output: { report_am, report_en, key_findings[], anomalies[] }
Agent Architecture (LangGraph, Python)
Each agent is a LangGraph StateGraph with:

TypedDict state schema
MCP tool server exposing Fleetbase MySQL + Redis as tools
Claude (claude-sonnet-4-20250514) as the LLM
OpenRouter as fallback (if Anthropic API unavailable)
Durable session logging to PostgreSQL (agent_sessions table)
Langfuse tracing on every LLM call
Pydantic response models — strict typed outputs only

MCP Tool Server (exposes Laravel backend to agents):
pythontools = [
    get_harvest_listings(crop_type, region_id, date_range),
    get_ecx_prices(crop_type, date_range),          # scrapes ECX website
    get_order_details(order_id),
    get_driver_availability(region_id),
    get_road_conditions(origin_woreda, dest_woreda),
    get_weather_forecast(lat, lng, days),            # Open-Meteo API
    get_quality_grades(shipment_id),
    get_payment_status(order_id),
    query_landmark_db(description, lat, lng),
    get_regional_demand_history(crop_type, region_id),
]
Laravel Queue Jobs that call AI Service

SuggestHarvestPrice — triggered on HarvestListing::created
ResolveAddressDescription — triggered on landmark submission
GenerateDispatchRecommendation — triggered on Order::confirmed
CheckOrderCompliance — triggered on shipment before departure
GenerateWeeklyInsightReport — scheduled every Monday 6am EAT
DraftFarmerSmsNotification — triggered on payment/delivery events


EXTENSION 6: SMS & Notification Engine
Purpose
All farmer-facing and driver-facing communication via Africa's Talking
(SMS/USSD). In-app notifications for web users via Laravel Reverb WebSocket.
Database Migrations
sql-- notification_messages
id, uuid, recipient_id (FK → contacts), recipient_phone,
channel (enum: sms|ussd|push|email|websocket),
language (enum: am|en), event_type, message_am, message_en,
sent_message (actual sent text), provider_response (JSON),
status (enum: queued|sent|delivered|failed),
provider (enum: africas_talking|firebase|mail|websocket),
retry_count (default 0), sent_at, delivered_at, created_at

-- ussd_sessions
id, session_id (Africa's Talking session ID), phone_number,
farmer_id (FK), current_menu, session_data (JSON),
status (enum: active|completed|timed_out),
created_at, updated_at
Africa's Talking Integration
AfricasTalkingService (app/Services/Notifications/AfricasTalkingService.php)
phppublic function sendSms(string $phone, string $message,
                         string $language = 'am'): SmsResult {}
public function sendBulkSms(array $recipients,
                              string $message): BulkSmsResult {}
public function handleUssdRequest(Request $request): string {}
    // Returns USSD menu string formatted for Africa's Talking
USSD Menu Structure (Ethiopia — Amharic)
CON ወደ ተራ ሃርቨስት እንኳን ደህና መጡ
    1. የኔ ትዕዛዞች (My Orders)
    2. የፈጠርኩት ዝርዝር (My Listings)
    3. የዋጋ ማረጋገጫ (Price Check)
    4. ቅሬታ ለመምዝገብ (Register Complaint)
    0. ለመውጣት (Exit)
API Endpoints
POST /api/v1/ussd/callback   (public — Africa's Talking callback)
POST /api/v1/sms/send        (internal — dispatches SMS job)
GET  /api/v1/notifications   (user's notification history)
PATCH /api/v1/notifications/{id}/read
Laravel Event → Notification Mapping
OrderConfirmed         → SMS to farmer + cooperative (Amharic)
DriverAssigned         → SMS to farmer: "Your produce will be picked up by [name]"
DeliveryConfirmed      → SMS to farmer: "Delivered. Payment of X ETB arriving in 48hrs"
PaymentReleased        → SMS to farmer: "X ETB sent to your Telebirr [phone]"
HarvestPriceBelowMarket → SMS to farmer: "ECX price is X ETB. Your listing: Y ETB"
QualityCertificateReady → SMS to cooperative: "Certificate QC-2026-XXXXX ready"
ColdChainAlert         → SMS to dispatcher + buyer: "Temperature exceeded for Order #X"
Jobs

SendSmsNotification — queued, handles retries (max 3), logs status
ProcessUssdSession — handles stateful USSD menu navigation


EXTENSION 7: Route Intelligence & Knowledge Base
Purpose
A knowledge base of Ethiopian road conditions, updated by drivers and the
AI agent, used to power smart dispatch and routing.
Database Migrations
sql-- route_segments
id, uuid, origin_woreda_id (FK), destination_woreda_id (FK),
distance_km (decimal 8,2), road_type (enum: paved|gravel|dirt|track),
condition_score (tinyint 1-10), avg_transit_hours (decimal 5,2),
truck_accessible (bool), rainy_season_passable (bool),
notes, last_verified_at, created_at, updated_at

-- route_reports
id, uuid, segment_id (FK), reported_by (FK → contacts),
report_type (enum: road_condition|hazard|closure|delay|
                    new_route|congestion),
description, lat, lng, severity (enum: low|medium|high|impassable),
is_verified (bool), verified_by (FK → users), verified_at,
photo_url, valid_from, valid_until, created_at

-- route_history
id, order_id (FK), driver_id (FK), planned_segments (JSON),
actual_path (JSON — GPS trace), planned_hours, actual_hours,
fuel_litres_used, incidents (JSON), created_at
API Endpoints
GET  /api/v1/routes/segments?origin_woreda=&dest_woreda=
GET  /api/v1/routes/recommend
     Body: { origin_woreda_id, destination_woreda_id, vehicle_type,
             date, priority: speed|fuel|reliability }
     → calls AI dispatch agent, returns ranked route options

POST /api/v1/routes/reports          (driver submits road report)
GET  /api/v1/routes/reports?segment_id=&severity=
POST /api/v1/routes/history          (driver app posts trip completion data)
Jobs

UpdateRouteConditionScore — on new driver reports, recalculates segment
condition score using weighted average of last 30 days of reports
ApplySeasonalRouteFlags — scheduled, applies/removes rainy season flags
based on Ethiopian calendar (June–September main rains)


EXTENSION 8: Compliance & Document Automation
Purpose
Auto-generate export compliance documents from order + quality data.
All documents are hashed, tamper-evident, and stored on S3.
Database Migrations
sql-- compliance_documents
id, uuid, order_id (FK), document_type
  (enum: phytosanitary|certificate_of_origin|ecx_grade|
         customs_declaration|bill_of_lading|packing_list|
         inspection_certificate),
document_number (unique), file_url, file_hash (SHA-256),
chain_hash (hash of previous doc in order's chain),
status (enum: draft|issued|submitted|accepted|rejected|expired),
issued_by (FK → users), issued_at, expires_at,
authority (issuing authority name), authority_reference,
created_at, updated_at

-- compliance_checklists
id, uuid, order_id (FK), generated_by_agent (bool),
items (JSON: [{ doc_type, required, present, status, notes }]),
overall_status (enum: complete|incomplete|flagged),
flagged_items (JSON), created_at, updated_at
API Endpoints
POST /api/v1/compliance/documents/generate
     Body: { order_id, document_type }
     → triggers GenerateComplianceDocument job

GET  /api/v1/compliance/documents/{id}
GET  /api/v1/compliance/documents/{id}/download  (PDF)
GET  /api/v1/compliance/documents/{id}/verify    (public — QR code target)
     → returns { valid: bool, hash_matches: bool, document_summary }

GET  /api/v1/compliance/checklists/{order_id}
POST /api/v1/compliance/checklists/{order_id}/run-check
     → calls ComplianceAgent, returns updated checklist

GET  /api/v1/compliance/documents/bundle/{order_id}
     → returns ZIP of all compliance docs for an order
Jobs

GenerateComplianceDocument — renders DomPDF template per doc type,
computes SHA-256 hash, uploads to S3, stores hash + chain hash
RunComplianceCheck — calls AI compliance agent, updates checklist,
notifies dispatcher if documents are missing before shipment departure
CheckDocumentExpiry — scheduled daily, flags docs expiring in 7 days


EXTENSION 9: Analytics & Reporting API
Purpose
Aggregate data endpoints for the government/NGO dashboard, buyer dashboards,
and cooperative dashboards. No frontend — pure JSON API + PDF/Excel export.
API Endpoints
Supply & Market Analytics
GET /api/v1/analytics/supply/by-crop
    ?region_id=&date_from=&date_to=&group_by=week|month
    → { crop_type, total_kg, total_listings, avg_price_etb, trend }

GET /api/v1/analytics/supply/heatmap
    → GeoJSON: regions with supply density, avg price, crop diversity

GET /api/v1/analytics/prices/history
    ?crop_type=&region_id=&date_from=&date_to=
    → price time series, ECX comparison, trend direction
Operations Analytics
GET /api/v1/analytics/orders/summary
    ?date_from=&date_to=&status=
    → total orders, completed, cancelled, avg fulfilment hours

GET /api/v1/analytics/drivers/performance
    → on_time_pct, avg_rating, trips_completed, incidents_reported

GET /api/v1/analytics/routes/bottlenecks
    → segments with highest avg_delay, grouped by region
Financial Analytics
GET /api/v1/analytics/payments/summary
    ?date_from=&date_to=&tenant_id=
    → total_volume_etb, avg_order_value, provider breakdown

GET /api/v1/analytics/farmers/income
    ?cooperative_id=&season=
    → avg income per farmer, top earners, crop breakdown
Export Endpoints
GET /api/v1/analytics/export/pdf?report=supply_summary&...
GET /api/v1/analytics/export/excel?report=farmer_income&...
Jobs

GenerateWeeklyAnalyticsSnapshot — scheduled Sunday midnight EAT,
pre-computes heavy aggregations into analytics_snapshots table
GenerateInsightReport — calls AI insight agent, stores Amharic +
English report, emails to registered government contacts


EXTENSION 10: Multi-Tenant Configuration
Purpose
Support multiple tenant organisations (cooperatives, agri-businesses, NGOs)
on the same infrastructure with strict data isolation.
Implementation
Fleetbase already has a basic company/organisation system. Extend it:
sql-- tenant_configurations
id, company_id (FK → Fleetbase companies), timezone (default: Africa/Addis_Ababa),
currency (default: ETB), default_language (default: am),
active_payment_providers (JSON: [chapa, telebirr, cbe_birr]),
active_crop_types (JSON), grading_standard (enum: ecx|fao|custom),
custom_grade_config (JSON), escrow_release_trigger
  (enum: delivery_confirmed|quality_approved|manual),
driver_commission_pct (decimal 5,2), broker_commission_pct (decimal 5,2),
sms_sender_id, logo_url, primary_colour,
ecx_notifications_enabled (bool),
compliance_document_types (JSON),
created_at, updated_at
Middleware

EnsureTeraHarvestTenant — applied to all /api/v1/ routes, scopes
all Eloquent queries to the current tenant via global scope
All models use HasTenant trait: where('company_id', currentTenantId())


DATABASE SCHEMA OVERVIEW
All new tables follow these conventions:

id (ULID — not integer, not UUID v4 — Fleetbase uses ULIDs)
uuid (separate UUID for public-facing references)
company_id (FK — multi-tenant isolation)
created_by (FK → users — audit trail)
created_at, updated_at, deleted_at (soft deletes)
All monetary amounts stored as DECIMAL(12,2) in ETB
All weights stored as DECIMAL(10,3) in kilograms
All GPS coordinates as DECIMAL(10,7) (7 decimal places = ~1cm precision)


GLOBAL LARAVEL CONFIGURATION
Config file: config/tera_harvest.php
phpreturn [
    'ai_service_url' => env('TERA_HARVEST_AI_URL', 'http://ai-service:8000'),
    'ai_service_timeout' => 30,
    'ecx_prices_url' => env('ECX_PRICES_URL'),
    'africas_talking' => [
        'api_key' => env('AT_API_KEY'),
        'username' => env('AT_USERNAME'),
        'sender_id' => env('AT_SENDER_ID', 'TeraHarvest'),
        'ussd_code' => env('AT_USSD_CODE'),
    ],
    'chapa' => [
        'secret_key' => env('CHAPA_SECRET_KEY'),
        'webhook_secret' => env('CHAPA_WEBHOOK_SECRET'),
        'base_url' => 'https://api.chapa.co/v1',
    ],
    'telebirr' => [
        'app_id' => env('TELEBIRR_APP_ID'),
        'app_key' => env('TELEBIRR_APP_KEY'),
        'short_code' => env('TELEBIRR_SHORT_CODE'),
        'base_url' => env('TELEBIRR_BASE_URL'),
    ],
    'escrow' => [
        'auto_release_hours' => 48,
        'dispute_window_hours' => 72,
    ],
    'cold_chain' => [
        'teff_max_temp_c' => 30,
        'coffee_max_temp_c' => 25,
        'vegetables_max_temp_c' => 8,
        'flowers_max_temp_c' => 4,
    ],
];

IMPLEMENTATION ORDER
Week 1

Fork Fleetbase, set up local Docker environment
Create tera-harvest-core Laravel package structure
Build Extension 1 (Ethiopian Admin Hierarchy) — migrations + seeders + API
Build multi-tenant middleware and tenant_configurations table
Write PHPUnit tests for address hierarchy endpoints

Week 2

Build Extension 2 (Harvest Listings) — full CRUD + photos + policies
Build SuggestHarvestPrice job skeleton (stub AI call for now)
Build Extension 6 (SMS Engine) — Africa's Talking integration + USSD menu
Write feature tests for listing creation + notification dispatch

Week 3

Build Extension 3 (Payments) — Chapa integration + escrow tables
Implement escrow hold/release pipeline
Add Telebirr + CBE Birr stubs (complete when API access obtained)
Build immutable payment event ledger with hash chain

Week 4

Build Extension 4 (Quality Grading) — grades + cold chain + PDF certs
Build Extension 7 (Route Knowledge Base) — segments + driver reports
Seed route segments for major Ethiopian agricultural corridors
(Addis→Jimma, Addis→Gondar, Addis→Hawassa, Addis→Adama, Addis→Dire Dawa)

Week 5

Build Python FastAPI AI microservice skeleton (all endpoints stubbed)
Build Extension 5 (AI Bridge) — TeraHarvestAIClient + all jobs
Implement Price Intelligence Agent (LangGraph) — ECX scraper + Claude
Implement Address Resolver Agent

Week 6

Implement Smart Dispatch Agent + Route Intelligence Agent
Implement Compliance Check Agent
Build Extension 8 (Compliance Documents) — PDF generation + hash chain
Wire all agents to their triggering Laravel events/jobs

Week 7

Build Extension 9 (Analytics API) — all aggregate endpoints
Implement GenerateWeeklyAnalyticsSnapshot scheduled job
Implement Insight Report Agent (weekly Amharic + English digest)
PDF + Excel export endpoints

Week 8

End-to-end integration tests (full order lifecycle, payment, cert)
Load testing (Laravel Telescope + queue monitoring under load)
Security audit: RBAC, webhook signature verification, SQL injection,
payment flow integrity
Docker Compose production config + GitHub Actions CI/CD pipeline


NON-NEGOTIABLES

Every API endpoint must be guarded by auth:sanctum + RBAC policy
Every payment event appended to the immutable ledger — no exceptions
All compliance PDFs must carry SHA-256 hash + QR verify link
All AI calls must be logged to Langfuse with cost attribution
USSD and SMS must work without any authenticated session
All monetary calculations use bcmath (PHP) — never float arithmetic
All Amharic strings stored as UTF-8 — verify MySQL charset is utf8mb4
Every queue job must be idempotent — safe to retry on failure
Webhook endpoints must verify provider signatures before processing
Row-level security: no query may return data outside the current tenant