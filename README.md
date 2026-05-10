# Tera Harvest — Ethiopian Agricultural Supply Chain Platform

> A full-stack, production-grade backend platform built on top of [Fleetbase](https://fleetbase.io), purpose-built for Ethiopian agricultural logistics. This platform connects smallholder farmers, cooperatives, buyers, input suppliers, drivers, NGOs, and government ministries through a unified supply chain OS with embedded AI agents.

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Feature Modules](#feature-modules)
  - [Phase 1 — Core Supply Chain (Extensions 1–10)](#phase-1--core-supply-chain-extensions-110)
  - [Phase 2 — Advanced Intelligence (Extensions 11–22)](#phase-2--advanced-intelligence-extensions-1122)
- [AI Microservice](#ai-microservice)
- [Database Design](#database-design)
- [Security](#security)
- [Testing](#testing)
- [Infrastructure](#infrastructure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [API Reference Summary](#api-reference-summary)

---

## Overview

Tera Harvest is a **22-extension Laravel package** (`fleetbase/tera-harvest-core`) plus a **Python FastAPI AI microservice**, built as a custom vertical on the Fleetbase open-source logistics OS. The system addresses real challenges in Ethiopian agriculture:

- Farmers lack market price visibility and sell below-market
- No standardised quality grading or tamper-proof certification
- Payment disbursement to rural smallholders is slow and opaque
- Cold chain failures cause significant post-harvest losses
- Government ministries have no real-time agricultural data access
- NGOs cannot efficiently track programme impact at scale
- Logistics carbon footprint goes unmeasured

Tera Harvest solves all of these through a cohesive, multi-tenant backend with SMS/USSD support in **Amharic**, integrated Ethiopian payment gateways, AI-powered pricing and disease diagnostics, and a blockchain-style immutable payment ledger.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Layer                              │
│         Mobile App  ·  Web Dashboard  ·  USSD (*XXX#)           │
└─────────────────────────┬───────────────────────────────────────┘
                           │ HTTPS / REST
┌─────────────────────────▼───────────────────────────────────────┐
│                   Laravel 10 API (Fleetbase)                      │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  22 Route    │  │  26 Eloquent │  │  5 Domain Services   │  │
│  │  Groups      │  │  Models      │  │  + 28 Queue Jobs     │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  Multi-      │  │  Immutable   │  │  Event / Listener    │  │
│  │  tenancy     │  │  Ledger      │  │  Pipeline (15 pairs) │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└────────────┬────────────────────────────────────────────────────┘
             │ HTTP (internal)
┌────────────▼────────────────────────────────────────────────────┐
│              Python FastAPI AI Microservice                       │
│                                                                   │
│  13 LangGraph ReAct Agents  ·  15 MCP Tools  ·  Langfuse Traces │
│  Claude Sonnet 4.6  ·  OpenRouter fallback                       │
└─────────────────────────────────────────────────────────────────┘
             │
┌────────────▼──────────────────┐  ┌──────────────────────────────┐
│  MySQL 8 (utf8mb4)            │  │  Redis (queues + rate limits) │
│  52 tables, ULID PKs          │  │                              │
└───────────────────────────────┘  └──────────────────────────────┘
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 10 (PHP 8.2) |
| Package System | Composer path repository, PSR-4 autoload |
| Database | MySQL 8 — utf8mb4, ULID primary keys |
| Cache / Queue | Redis (Laravel Horizon-compatible) |
| AI Agents | Python 3.12, FastAPI, LangGraph, Claude Sonnet 4.6 |
| AI Tracing | Langfuse (cost attribution per tenant) |
| SMS / USSD | Africa's Talking (Amharic menus) |
| Payment Gateways | Chapa, Telebirr, CBE Birr |
| PDF Generation | DomPDF (quality certificates, compliance docs) |
| File Storage | AWS S3 / compatible (certificates, photos) |
| Containerisation | Docker Compose (api, ai-service, mysql, redis) |
| Testing | PHPUnit 10 + Orchestra Testbench (SQLite in-memory) |

---

## Feature Modules

### Phase 1 — Core Supply Chain (Extensions 1–10)

#### Extension 1 — Ethiopian Administrative Hierarchy
Full five-level geographic model: **Region → Zone → Woreda → Kebele → Landmark**.

- 12 Ethiopian regions seeded with ISO 3166-2 codes and Amharic names
- Haversine bounding-box `scopeNearby()` for proximity landmark search
- AI address resolver converts natural-language Amharic descriptions to coordinates
- POLYGON geometry column on regions for GIS-ready spatial queries

#### Extension 2 — Harvest Listings & Price Intelligence
Multi-photo harvest listings with S3 presigned URL upload, soft-delete, and tenant scoping.

- `SuggestHarvestPrice` job calls AI price-intelligence agent on listing creation
- ECX (Ethiopian Commodity Exchange) price benchmarking
- Smart buyer-matching based on commodity, quality, and proximity
- `HarvestPriceBelowMarket` event triggers automatic SMS alert to farmer

#### Extension 3 — Payment Wallets & Multi-Gateway Transactions
Dual-gateway payment system with an append-only cryptographic ledger.

- Wallets use **bcmath** exclusively — no floating-point arithmetic anywhere
- SHA-256 hash chain: each `payment_event` row hashes the previous hash + payload
- `PaymentEvent::verifyChainIntegrity()` detects any tampering
- HMAC webhook validation for Chapa (`hash_equals` — timing-safe)
- Bulk farmer disbursement via Telebirr batch API

#### Extension 4 — Escrow & Delivery Confirmation
Fully transactional escrow pipeline protecting both buyer and seller.

- `EscrowHold` row created on order confirmation, funds locked immediately
- `ProcessEscrowRelease` job uses `DB::transaction()` + `lockForUpdate()` to prevent double-release
- Configurable auto-release window (default 48 hours post-delivery)
- Commission split calculated with bcmath to 6 decimal places before rounding

#### Extension 5 — Quality Grading & Certification
Standardised A/B/C quality grading with tamper-evident PDF certificates.

- DomPDF certificate includes SHA-256 hash printed on document
- QR code embedded in certificate pointing to public verify endpoint
- Grade photos uploaded to S3; cold chain temperature logged on submission
- `QualityCertificateReady` event fires → SMS to farmer with certificate link

#### Extension 6 — Cold Chain Monitoring
IoT-ready temperature logging with automated threshold alerts.

- Configurable min/max temperature thresholds per commodity in config
- `CheckColdChainThreshold` job fires `ColdChainAlert` event on breach
- Listener logs breach + queues SMS to driver and operations team
- `predictShelfLife` AI agent adjusts remaining shelf life based on temperature history

#### Extension 7 — Route Intelligence
Agricultural route management with condition scoring and AI dispatch.

- Route segments with condition scores (weighted 30-day rolling average)
- `SmartDispatch` AI agent selects best driver considering road conditions, vehicle type, and driver rating
- Weekly seasonal route recalibration via scheduled command
- Route bottleneck analytics endpoint for operations dashboards

#### Extension 8 — Compliance Document Management
Export compliance with document lifecycle, hash verification, and ZIP bundle export.

- Documents receive a chain hash (each document includes hash of previous)
- DomPDF generation with S3 storage; public `/verify/{id}` endpoint
- `RunComplianceCheck` job calls AI compliance agent against checklist
- ZIP bundle download via ZipStream for multi-document export
- Document expiry tracking with pre-expiry SMS notifications

#### Extension 9 — USSD & SMS Notifications
Africa's Talking integration with stateful USSD menus in Amharic.

- USSD session state stored in `ussd_sessions` table; `CON` / `END` response format
- Main menu in Amharic: ወደ ተራ ሃርቨስት / price check / balance / listings / help
- All SMS messages drafted by AI `sms-draft` agent (bilingual Amharic + English)
- Bulk SMS broadcast with chunking (100 recipients per API call)

#### Extension 10 — Analytics & Reporting
Multi-dimensional analytics with PDF/Excel export.

- Supply heatmap by crop and region
- Price history trending by commodity
- Driver performance leaderboard
- Route bottleneck identification
- Weekly AI-generated insight report (Amharic + English narrative)

---

### Phase 2 — Advanced Intelligence (Extensions 11–22)

#### Extension 11 — Farmer Credit Scoring
A proprietary 4-component credit scoring engine producing a 0–100 score and band (Unrated → Bronze → Silver → Gold → Platinum).

- **Delivery Reliability Score** (0–25): ratio of on-time deliveries
- **Quality Consistency Score** (0–25): weighted average of A/B/C grade history
- **Volume History Score** (0–25): total kg traded against a benchmark
- **Payment Behaviour Score** (0–25): deductions for disputes in rolling 12 months
- Every recalculation appends a `credit_score_history` row with delta and trigger event
- Microfinance-ready: anonymised data package shared with partner MFIs (never raw PII)
- `BulkRecalculateCreditScores` job fans out individual recalculation jobs across all farmers
- AI `/agents/credit-score-explain` generates personalised improvement advice in Amharic

#### Extension 12 — Bulk Aggregation Lots
Cooperative aggregation where multiple farmers contribute to a single tradeable lot.

- Pro-rata payout splits calculated entirely in **bcmath** (zero float errors on large batches)
- Platform commission (default 3.5%) deducted before farmer split
- Lot lifecycle: `open → closed → dispatched → sold → settled`
- Lot auto-closes when `collected_kg >= target_kg`; triggers `AutoMatchAggregationLot` job
- `SplitLotPayment` job settles all contributions atomically in a single DB transaction
- Individual farmer wallet credits happen in the same transaction; `NotifyLotContributors` sends Amharic SMS confirmation

#### Extension 13 — Input Supply Marketplace
Reverse-logistics marketplace connecting farmers with agri-input suppliers (seeds, fertiliser, pesticides, equipment).

- Supplier portal with product catalogue, stock levels, and reorder thresholds
- `isLowStock()` model method triggers `AlertLowStockSupplier` job
- Order placement calculates line totals in bcmath; `DispatchInputOrder` job confirms and notifies
- Delivery status SMS notifications in Amharic at each status change

#### Extension 14 — Forward Contracts & Buyer Subscriptions
Forward contract system allowing buyers to lock in future crop supply at agreed prices.

- 10% deposit (configurable) held in escrow on contract activation via `processDeposit()`
- Deposit forfeiture or refund on cancellation, controlled by `refund_deposit` flag
- `recordFulfilment()` updates `fulfilled_kg` and transitions contract status automatically
- Buyer subscriptions with frequency settings (daily/weekly/fortnightly/monthly); `MatchSubscriptionToListings` job notifies buyers when matching listings appear
- `AlertContractExpiry` job sends 7-day warning SMS to buyer

#### Extension 15 — Crop Disease Early Warning
AI-powered disease surveillance with tiered alert broadcasting.

- Farmers submit disease reports (text symptoms + photo URLs)
- `DiagnoseCropDisease` job calls Claude vision-capable agent against knowledge base (coffee berry disease, teff smut, maize armyworm, wheat rust, and more)
- Severity auto-escalated: `critical` reports trigger `EscalateHighSeverityDisease` → national-level alert, government notified flag set
- `BroadcastDiseaseAlert` fans SMS to all farmers in the affected region (50km radius default)
- Public disease alert feed exposed on Government API

#### Extension 16 — Weather Alert & Shipment Rerouting
Real-time weather monitoring via Open-Meteo API with automated logistics response.

- Runs every 6 hours across all 12 Ethiopian regions
- WMO-standard thresholds: heavy rain ≥50mm, strong wind ≥65km/h, extreme heat ≥38°C, frost ≤2°C
- Severity classification: `watch → warning → emergency`
- `RerouteAtRiskShipments` automatically flags active orders with delay hours based on severity
- `ShipmentWeatherImpact` audit trail records every automated rerouting decision

#### Extension 17 — AI-Assisted Price Negotiation
Turn-based negotiation engine between buyers and sellers with AI mediator.

- Up to 5 configurable turns; 48-hour expiry per negotiation
- Every offer is recorded as an immutable `NegotiationTurn` (no `updated_at`, append-only)
- `/agents/price-negotiation` AI agent references ECX market prices and past accepted deals to suggest a fair settlement price
- On acceptance, `CreateOrderFromAcceptedNegotiation` job automatically creates a confirmed order
- `ExpireStaleNegotiations` runs every 30 minutes to clean up timed-out negotiations

#### Extension 18 — Yield Prediction & Farm Plot Management
Per-plot yield forecasting using AI with historical accuracy tracking.

- Farm plots store soil type, irrigation type, elevation, polygon coordinates, and crop history
- `GenerateYieldPrediction` job calls AI yield agent combining plot data, regional yield history, and weather forecasts
- Returns predicted yield + confidence interval (min/max)
- `updateAccuracy()` method calculates real vs predicted variance when actual harvest is recorded
- `NotifyUpcomingHarvest` job sends harvest preparation reminders 14 days before expected harvest date

#### Extension 19 — Government Data API
Dedicated read-only API for Ethiopian government ministries with scoped, rate-limited keys.

- Keys stored as SHA-256 hashes (never plaintext); `verifyKey()` uses `hash_equals` (timing-safe)
- Per-minute and per-day rate limiting via Redis `RateLimiter`
- `EnsureGovernmentApiKey` middleware handles auth, rate limiting, and request logging in one pass
- Scopes system: keys can be restricted to specific endpoints (`supply-overview`, `farmer-stats`, `trade-volume`, `disease-alerts`, `carbon-summary`)
- Every request logged to `government_data_requests` with response time and record count

#### Extension 20 — NGO Programme Impact Tracking
End-to-end programme management for agricultural development NGOs.

- Programme lifecycle: `planning → active → completed → suspended`
- Beneficiary enrolment with baseline data capture (income, yield, demographics)
- `GenerateNgoProgrammeSnapshot` job aggregates: active/graduated beneficiaries, average income change %, total kg traded, women and youth disaggregation
- `incomeChangePercentage()` calculates farmer income growth vs baseline using bcmath
- Programme KPI framework with configurable JSON KPI definitions

#### Extension 21 — Carbon Emissions Tracking
IPCC 2006-compliant transport emission calculation with monthly reporting.

- Emission factors by fuel type: Diesel 2.68 kg CO₂/L, Petrol 2.31, LPG 1.61, Electric 0.00
- Vehicle fuel efficiency models: truck 0.30 L/km, van 0.12 L/km, motorcycle 0.05 L/km
- Every delivery automatically logged to `carbon_emissions_log` via `LogShipmentCarbon` job
- Monthly report aggregates: total CO₂e, per-order and per-km intensity, breakdown by fuel type and region
- Industry benchmark comparison with variance percentage; `CarbonThresholdExceeded` event when above benchmark
- Carbon data exposed on Government API for policy reporting

#### Extension 22 — Driver Earnings, Leaderboard & Badges
Weekly earnings summaries with gamified performance leaderboards.

- Base rate per delivery (ETB 85 default) + volume bonus (ETB 500 for 30+ deliveries)
- On-time delivery rate and average customer rating contribute to composite performance score
- Badge system: `high_performer`, `top_rated`, `punctuality_star` awarded automatically by `AwardDriverBadges` job
- Leaderboard **anonymises driver identity beyond rank 3** (privacy-by-design: only top 3 are named)
- `GenerateRegionalLeaderboard` supports filtering by region for local competitions
- `NotifyWeeklyEarningsSummary` sends personalised earnings SMS every Monday

---

## AI Microservice

The Python FastAPI service (`ai-service/`) implements 13 LangGraph ReAct agents and 15 MCP tools.

### Agents

| Endpoint | Purpose |
|---|---|
| `/agents/price-intelligence` | ECX price benchmarking + market trend detection |
| `/agents/address-resolver` | Amharic → GPS coordinates via landmark database |
| `/agents/smart-dispatch` | Driver assignment considering road conditions |
| `/agents/demand-forecast` | Regional crop demand forecasting |
| `/agents/compliance-check` | Export document completeness validation |
| `/agents/shelf-life` | Cold chain temperature-adjusted shelf life prediction |
| `/agents/sms-draft` | Bilingual Amharic/English SMS drafting |
| `/agents/insight-report` | Weekly agricultural intelligence narrative |
| `/agents/credit-score-explain` | Plain-language credit score explanation per farmer |
| `/agents/disease-diagnosis` | Symptom-based crop disease identification + treatment |
| `/agents/weather-alert` | Agricultural impact advisory from weather forecasts |
| `/agents/price-negotiation` | Market-aware fair price mediation |
| `/agents/yield-prediction` | Plot-level harvest volume forecasting |

### MCP Tools (LangGraph tool calls)

`get_harvest_listings`, `get_ecx_prices`, `get_order_details`, `get_driver_availability`, `get_road_conditions`, `get_weather_forecast`, `get_quality_grades`, `get_payment_status`, `query_landmark_db`, `get_regional_demand_history`, `get_farmer_credit_score`, `get_disease_knowledge_base`, `get_regional_yield_history`, `get_negotiation_history`, `get_driver_performance_history`

### Observability

All agent invocations are traced via **Langfuse** with tenant ID, token counts, and latency — enabling per-company AI cost attribution.

### Fallback Strategy

Every agent endpoint has a deterministic fallback response (no exceptions surfaced to the caller). The LLM model is `claude-sonnet-4-6` with an OpenRouter fallback via `get_llm(fallback=True)`.

---

## Database Design

### Key Design Decisions

| Decision | Rationale |
|---|---|
| **ULID primary keys** | Lexicographically sortable, URL-safe, collision-resistant; matches Fleetbase convention |
| **`company_id` on every table** | Multi-tenancy enforced at the Eloquent model layer via `HasTenant` global scope |
| **bcmath everywhere** | PHP floats cannot represent ETB currency values accurately at scale; all monetary fields are `DECIMAL(12,2)` |
| **Append-only `payment_events`** | No `updated_at` column; SHA-256 hash chain makes tampering detectable |
| **utf8mb4 charset** | Full Amharic Unicode support throughout |
| **`SoftDeletes` on listings/suppliers** | Regulatory audit trail without physical deletion |

### Table Count

- **Phase 1**: 23 tables
- **Phase 2**: 29 additional tables
- **Total**: **52 tables**

---

## Security

| Concern | Implementation |
|---|---|
| Multi-tenancy isolation | `HasTenant` global Eloquent scope; `EnsureTeraHarvestTenant` middleware validates `X-Company-Id` header |
| Payment webhook validation | Chapa HMAC verified with `hash_equals()` (constant-time comparison prevents timing attacks) |
| Government API key storage | SHA-256 hash only stored — raw key shown once at creation, never retrievable |
| Government API rate limiting | Redis-backed per-minute and per-day limits per key |
| Ledger integrity | SHA-256 hash chain on `payment_events`; `verifyChainIntegrity()` detects any row mutation |
| Queue job idempotency | All jobs check current model state before acting (e.g., escrow release checks `status = 'held'`) |
| S3 file access | Presigned URLs for photo upload; certificates stored privately, served via signed URLs |
| Input validation | All controller endpoints use `Validator::make()` with strict rules before any DB write |

---

## Testing

Six PHPUnit test classes covering the most critical invariants:

### `EthiopiaHierarchyTest`
- ULID generation on model creation
- Region → Zone → Woreda → Kebele relationship traversal
- `scopeNearby()` Haversine bounding-box correctness

### `HarvestListingTest`
- `scopeActive()` filters correctly
- Soft delete excludes from active scope
- Decimal precision on `price_per_kg_etb`

### `PaymentLedgerTest`
- Hash chain validity across multiple `payment_event` appends
- Tamper detection: modifying any row breaks the chain
- bcmath arithmetic produces exact ETB values
- Append-only: no `updated_at` column on `payment_events`

### `QualityGradeTest`
- Certificate number format: `QC-YYYY-XXXXXX`
- JSON `custom_attributes` cast round-trips correctly

### `CreditScoringTest`
- ULID generated on `FarmerCreditScore` save
- Default score band is `unrated`
- `isEligibleForMicrofinance()` correct for all bands
- `getComponentTotal()` sums all four components
- Unique constraint on `(farmer_id, company_id)` enforced

### `BulkAggregationTest`
- Remaining capacity calculated correctly
- Fill percentage arithmetic
- Lot closes when target reached
- Contribution exceeding capacity throws `RuntimeException`
- Pro-rata payout ratio correct (2:1 for 200kg vs 100kg contribution)

### `ForwardContractTest`
- Total value and deposit auto-calculated from quantity × price
- Contract number auto-generated with `FWD-` prefix
- Remaining kg decreases correctly on partial fulfilment
- Status transitions: `active → partially_fulfilled → fulfilled`
- Cancellation with forfeiture sets `deposit_status = forfeited`

### `PriceNegotiationTest`
- Midpoint calculation uses bcmath
- Max-turns detection
- Expiry detection
- Turns relationship sorts by `turn_number`

### `CarbonTrackingTest`
- Diesel shipment produces correct CO₂e > 0
- Electric vehicle produces exactly 0.000 kg CO₂e
- `CarbonEmissionsLog` has no `updated_at` (append-only)
- Monthly report aggregates multiple logs correctly
- Unique constraint on `(company_id, year, month)` enforced

### `GovernmentApiTest`
- `generateKey()` returns correct prefix length (8 chars) and hash length (64 chars)
- `verifyKey()` matches correct raw key, rejects wrong key
- `key_hash` hidden from model serialisation
- Empty scopes allows any scope; populated scopes restricts correctly
- Expiry detection for past and future dates

---

## Infrastructure

### Docker Compose Services

```yaml
services:
  api:         # Laravel 10 (PHP-FPM + Nginx)
  ai-service:  # Python 3.12 FastAPI (2 uvicorn workers)
  mysql:       # MySQL 8, utf8mb4
  redis:       # Redis 7 (queues + rate limiting)
```

### Scheduled Jobs (Laravel Scheduler)

| Schedule | Job |
|---|---|
| Daily 01:00 | Expire stale harvest listings |
| Daily 02:00 | Disburse pending farmer payments |
| Daily 03:00 | Reconcile payment transactions |
| Daily 06:00 | Check document expiry; pre-expiry notifications |
| Daily 07:00 | Alert forward contract expiries (7-day window) |
| Daily 08:00 | Notify farmers of upcoming harvest (14-day window) |
| Every 30 min | Expire stale price negotiations |
| Every 6 hours | Weather forecast check across all regions |
| Weekly Mon | Generate driver earnings summaries + leaderboards |
| Weekly Mon | Weekly AI insight report per tenant |
| Weekly Mon | Apply seasonal route condition updates |
| Weekly Sun | Analytics snapshot |

---

## Getting Started

### Prerequisites

- PHP 8.2+, Composer 2
- MySQL 8.0+
- Redis 7+
- Python 3.12+ (for AI service)
- Docker + Docker Compose (recommended)

### Installation

```bash
# 1. Clone the repository
git clone <repo-url>
cd fleetbase-main

# 2. Register the package in api/composer.json (already done)
# The path repository entry points to packages/tera-harvest-core

# 3. Install PHP dependencies
cd api
composer install

# 4. Run migrations (includes all 52 Tera Harvest tables)
php artisan migrate

# 5. Seed Ethiopian geographic data
php artisan tera-harvest:seed-ethiopia --routes

# 6. Install Python AI service dependencies
cd ../ai-service
pip install -r requirements.txt

# 7. Start with Docker Compose
cd ..
docker compose up -d
```

### Running Tests

```bash
cd packages/tera-harvest-core
composer install
./vendor/bin/phpunit
```

---

## Environment Variables

```env
# AI Microservice
TERA_HARVEST_AI_URL=http://ai-service:8000
TERA_HARVEST_AI_TIMEOUT=30

# Africa's Talking (SMS / USSD)
AT_API_KEY=your_key
AT_USERNAME=sandbox
AT_SENDER_ID=TeraHarvest
AT_USSD_CODE=*123#

# Chapa (payment gateway)
CHAPA_SECRET_KEY=your_key
CHAPA_WEBHOOK_SECRET=your_secret

# Telebirr
TELEBIRR_APP_ID=
TELEBIRR_APP_KEY=
TELEBIRR_SHORT_CODE=

# CBE Birr
CBE_BIRR_MERCHANT_ID=
CBE_BIRR_API_KEY=

# Claude AI (in ai-service)
ANTHROPIC_API_KEY=your_key

# Langfuse (AI observability)
LANGFUSE_PUBLIC_KEY=
LANGFUSE_SECRET_KEY=
LANGFUSE_HOST=https://cloud.langfuse.com

# Carbon Tracking
CARBON_BENCHMARK_KG=

# Driver Earnings
DRIVER_BASE_RATE_ETB=85.00
DRIVER_VOLUME_BONUS_ETB=500.00

# Aggregation
AGGREGATION_COMMISSION_PCT=3.5

# Forward Contracts
CONTRACT_DEPOSIT_PCT=10.00
```

---

## API Reference Summary

### Authenticated Endpoints (`/api/v1/` — requires `auth:sanctum` + `X-Company-Id`)

| Group | Base Path | Key Actions |
|---|---|---|
| Geography | `/ethiopia/` | Regions, zones, woredas, kebeles, landmarks, AI address resolve |
| Listings | `/harvest/listings/` | CRUD, photo upload, price intelligence, buyer match |
| Payments | `/payments/` | Initiate, webhooks, escrow, ledger history |
| Quality | `/quality/` | Grade submission, certificate PDF, cold chain logs |
| Routes | `/routes/` | Segments, smart dispatch, reports, history |
| Compliance | `/compliance/` | Generate, verify, ZIP bundle |
| Notifications | `/notifications/` | SMS send, USSD callback |
| Analytics | `/analytics/` | Supply, prices, orders, drivers, routes, payments, income |
| Credit | `/credit/` | Score, history, recalculate, AI explain, microfinance package |
| Aggregation | `/aggregation/lots/` | Create lot, add contribution, settle + payout |
| Input Supply | `/input-supply/` | Suppliers, products, order placement |
| Contracts | `/contracts/`, `/subscriptions/` | Forward contracts, deposits, fulfilments, buyer subscriptions |
| Disease | `/disease/` | Reports, AI diagnosis, broadcast alerts |
| Weather | `/weather/` | Active alerts, region check, shipment impacts |
| Negotiations | `/negotiations/` | Initiate, respond, accept, AI suggestion |
| Yield | `/yield/` | Farm plots, yield predictions, actual yield recording |
| Gov API Keys | `/gov-api-keys/` | Admin: issue, revoke, usage stats |
| NGO | `/ngo/programmes/` | Programmes, beneficiary enrolment, impact snapshots |
| Carbon | `/carbon/` | Log emission, generate monthly report, summary |
| Driver Earnings | `/driver-earnings/` | Summaries, leaderboard, pending disbursements |

### Government API (`/api/gov/v1/` — requires `X-Gov-Api-Key`)

| Endpoint | Data |
|---|---|
| `GET /supply-overview` | Active listings by commodity, total kg, avg prices |
| `GET /farmer-stats` | Farmer count by credit band, avg orders and kg |
| `GET /trade-volume` | Monthly trade volume by commodity (filterable by date range) |
| `GET /disease-alerts` | Active disease alerts (filterable by severity) |
| `GET /carbon-summary` | Monthly CO₂e reports for the last 12 months |

### Public Endpoints (no auth)

| Endpoint | Purpose |
|---|---|
| `POST /api/v1/payments/webhook/chapa` | Chapa payment webhook (HMAC validated) |
| `POST /api/v1/payments/webhook/telebirr` | Telebirr webhook |
| `POST /api/v1/ussd/callback` | Africa's Talking USSD session handler |
| `GET /api/v1/compliance/documents/{id}/verify` | Public certificate verification |
| `GET /api/v1/quality/grades/{id}/verify` | Public quality grade lookup |
| `GET /health` | AI service health check |

---

## Project Stats

| Metric | Count |
|---|---|
| Laravel package files | 180+ |
| Database tables | 52 |
| Eloquent models | 46 |
| HTTP controllers | 20 |
| Queue jobs | 41 |
| Events | 15 |
| Event listeners | 15 |
| Domain services | 8 |
| Middleware | 2 |
| Python AI agents | 13 |
| MCP tools | 15 |
| PHPUnit test classes | 9 |
| PHPUnit test cases | 45+ |
| API route groups | 22 |
| Scheduled tasks | 13 |

---

## Author

**Kidus Gashaw** — kidus@10academy.org

Built as a full-stack backend engineering portfolio project demonstrating:
- Production-grade Laravel package architecture
- Ethiopian fintech integration (Chapa, Telebirr, CBE Birr)
- LangGraph multi-agent AI orchestration with Claude
- Cryptographic payment ledger design
- Multi-tenant SaaS architecture
- Domain-driven design with bcmath financial precision
- Bilingual (Amharic / English) agricultural platform design
