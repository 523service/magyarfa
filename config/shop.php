<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nyitvatartás
    |--------------------------------------------------------------------------
    |
    | Rendes heti nyitvatartás és speciális/ünnepi napok.
    | Timezone: Europe/Budapest
    |
    | Hét napjai: 1=Hétfő ... 6=Szombat, 7=Vasárnap (Carbon::dayOfWeek szerint)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Értesítések / Figyelmeztetések
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Szállítási beállítások
    |--------------------------------------------------------------------------
    |
    | free_shipping_threshold: ettől az összegtől (Ft) ingyenes a futárszállítás.
    |
    */
    'store_email' => env('STORE_EMAIL', ''),

    'feedback_email' => env('FEEDBACK_EMAIL', env('STORE_EMAIL', '')),

    'free_shipping_threshold' => (int) env('SHOP_FREE_SHIPPING_THRESHOLD', 550000),

    /*
     * Futárral kiszállítás díja (Ft-ban), ha a kosár nem éri el a küszöböt.
     */
    'courier_price' => (int) env('SHOP_COURIER_PRICE', 59900),

    'notices' => [
        'price_volatility' => 'Az alapanyagárak extrém emelkedése miatt az EPS és XPS árai tájékoztató jellegűek, csak a visszaigazolás után tekinthető ajánlatnak!',
    ],

    'opening_hours' => [

        // Rendes heti nyitvatartás
        'weekday' => ['open' => '06:00', 'close' => '17:00'],   // H–P
        'saturday' => ['open' => '06:00', 'close' => '12:00'],  // SZ
        'sunday' => null,                                       // V – zárva

        /*
        | Speciális/ünnepi napok
        | Ezek felülírják a rendes nyitvatartást.
        |
        | Formátum:
        |   'YYYY-MM-DD' => ['label' => '...', 'hours' => '06:00–12:00'] // speciális idő
        |   'YYYY-MM-DD' => ['label' => '...', 'hours' => null]          // zárva
        */
        'special_days' => [
            // Ide vehetsz fel további speciális napokat:
            // '2026-05-25' => ['label' => 'Pünkösd hétfő', 'hours' => null],
            // '2026-08-20' => ['label' => 'Államalapítás', 'hours' => null],
        ],

    ],

];
