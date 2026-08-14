<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),

    /*
     * 'sync'  = admin vár, azonnali eredmény (max ~5 termékhez)
     * 'queue' = háttérben fut, nagy mennyiséghez ajánlott
     */
    'bulk_mode' => env('AI_BULK_MODE', 'queue'),

    'models' => [
        'anthropic' => env('AI_ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'openai' => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'system_prompt' => env('AI_SYSTEM_PROMPT', 'Te egy profi marketingszöveg-író vagy, aki szigetelési és építőipari termékek leírásait írja. A leírás MINDIG magyar nyelvű, szakmai de olvasható, SEO-barát legyen. A description mező HTML formátumú lehet (h2, h3, p, ul, ol, li, strong, em, br tagokat használhatsz). A seo_description legyen tömör, max 160 karakter, plain text. FONTOS: Kizárólag a megadott termékadatokból dolgozz. Ne találj ki műszaki paramétert, tanúsítványt, éghetőségi osztályt, hővezetési értéket (lambda), szabványt vagy gyártói állítást. Ha egy adat nem szerepel a termékadatokban, NE írd bele a leírásba.'),
];
