<?php

return [
    // Bandera general: la API sigue en construcción activa.
    'maintenance' => (bool) env('APP_MAINTENANCE', false),

    // Stripe/Cashier (Fase 2) todavía no está integrado — los endpoints
    // de facturación/pagos deben responder "en mantenimiento" hasta entonces.
    'payments_maintenance' => (bool) env('PAYMENTS_MAINTENANCE', true),

    // Demo: permite entrar al portal de cliente sin Google, como cuenta demo.
    // OFF por defecto — es un bypass de login. Prender solo para mostrar el
    // panel y apagar después. Solo alcanza al tenant demo (datos propios).
    'demo_login' => (bool) env('DEMO_LOGIN', false),

    // Correos que reciben rol super_admin al entrar con Google. Se crea el
    // Consultant en el primer login; quitar un correo de aquí NO revoca el
    // acceso de un Consultant ya creado (hay que degradarlo en la base).
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

    // Dominios permitidos para el alta de clientes con Google. Vacío = cualquiera.
    'allowed_signup_domains' => array_values(array_filter(array_map(
        fn (string $domain): string => mb_strtolower(trim($domain)),
        explode(',', (string) env('ALLOWED_SIGNUP_DOMAINS', '')),
    ))),
];
