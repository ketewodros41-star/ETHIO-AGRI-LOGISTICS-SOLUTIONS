<?php

return [
    'ai_service_url'     => env('TERA_HARVEST_AI_URL', 'http://ai-service:8000'),
    'ai_service_timeout' => 30,
    'ecx_prices_url'     => env('ECX_PRICES_URL'),

    'africas_talking' => [
        'api_key'   => env('AT_API_KEY'),
        'username'  => env('AT_USERNAME'),
        'sender_id' => env('AT_SENDER_ID', 'TeraHarvest'),
        'ussd_code' => env('AT_USSD_CODE'),
    ],

    'chapa' => [
        'secret_key'      => env('CHAPA_SECRET_KEY'),
        'webhook_secret'  => env('CHAPA_WEBHOOK_SECRET'),
        'base_url'        => 'https://api.chapa.co/v1',
    ],

    'telebirr' => [
        'app_id'     => env('TELEBIRR_APP_ID'),
        'app_key'    => env('TELEBIRR_APP_KEY'),
        'short_code' => env('TELEBIRR_SHORT_CODE'),
        'base_url'   => env('TELEBIRR_BASE_URL'),
    ],

    'cbe_birr' => [
        'merchant_id'    => env('CBE_BIRR_MERCHANT_ID'),
        'api_key'        => env('CBE_BIRR_API_KEY'),
        'base_url'       => env('CBE_BIRR_BASE_URL'),
    ],

    'escrow' => [
        'auto_release_hours'  => 48,
        'dispute_window_hours' => 72,
    ],

    'cold_chain' => [
        'teff_max_temp_c'       => 30,
        'coffee_max_temp_c'     => 25,
        'vegetables_max_temp_c' => 8,
        'flowers_max_temp_c'    => 4,
    ],

    'certificate' => [
        'prefix'     => 'QC',
        's3_disk'    => env('CERTIFICATE_DISK', 's3'),
        's3_prefix'  => 'certificates/',
    ],

    'compliance' => [
        's3_disk'   => env('COMPLIANCE_DISK', 's3'),
        's3_prefix' => 'compliance/',
    ],

    'langfuse' => [
        'public_key'  => env('LANGFUSE_PUBLIC_KEY'),
        'secret_key'  => env('LANGFUSE_SECRET_KEY'),
        'host'        => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),
    ],

    // Extension 11 — Credit Scoring
    'credit_scoring' => [
        'auto_recalculate_on_order' => true,
        'bulk_recalculate_schedule' => 'weekly',
        'microfinance_eligible_bands' => ['silver', 'gold', 'platinum'],
    ],

    // Extension 12 — Bulk Aggregation
    'aggregation' => [
        'platform_commission_pct' => env('AGGREGATION_COMMISSION_PCT', '3.5'),
    ],

    // Extension 13 — Input Supply
    'input_supply' => [
        'base_delivery_fee_etb' => env('INPUT_DELIVERY_FEE_ETB', '50.00'),
    ],

    // Extension 14 — Forward Contracts
    'forward_contracts' => [
        'default_deposit_pct'    => env('CONTRACT_DEPOSIT_PCT', '10.00'),
        'expiry_warning_days'    => 7,
        'cancellation_forfeit_pct' => '100',
    ],

    // Extension 16 — Weather Monitoring
    'weather' => [
        'check_interval_hours'    => 6,
        'reroute_on_emergency'    => true,
        'open_meteo_base_url'     => 'https://api.open-meteo.com/v1',
    ],

    // Extension 17 — Price Negotiation
    'negotiation' => [
        'default_max_turns'      => 5,
        'default_expiry_hours'   => 48,
    ],

    // Extension 21 — Carbon Tracking
    'carbon' => [
        'industry_benchmark_kg_per_month' => env('CARBON_BENCHMARK_KG', null),
        'ipcc_version'                    => '2006',
        'emission_factors' => [
            'diesel'   => 2.68,
            'petrol'   => 2.31,
            'lpg'      => 1.61,
            'electric' => 0.00,
        ],
        'fuel_efficiency_litres_per_km' => [
            'truck'      => 0.30,
            'van'        => 0.12,
            'motorcycle' => 0.05,
            'default'    => 0.20,
        ],
    ],

    // Extension 22 — Driver Earnings
    'driver_earnings' => [
        'base_rate_per_delivery_etb' => env('DRIVER_BASE_RATE_ETB', '85.00'),
        'volume_bonus_threshold'     => 30,
        'volume_bonus_etb'           => env('DRIVER_VOLUME_BONUS_ETB', '500.00'),
        'leaderboard_anonymise_after_rank' => 3,
    ],
];
