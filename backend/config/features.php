<?php

return [
    // Bandera general: la API sigue en construcción activa.
    'maintenance' => (bool) env('APP_MAINTENANCE', false),

    // Stripe/Cashier (Fase 2) todavía no está integrado — los endpoints
    // de facturación/pagos deben responder "en mantenimiento" hasta entonces.
    'payments_maintenance' => (bool) env('PAYMENTS_MAINTENANCE', true),
];
