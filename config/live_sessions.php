<?php

/**
 * Configuration des sessions en direct (Live Classroom).
 *
 * Le fuseau horaire « de référence » pour l'affichage et la saisie des
 * horaires est LIVE_SESSION_TIMEZONE (par défaut : Africa/Lubumbashi,
 * c'est-à-dire l'heure de Goma / l'est de la RDC, UTC+2 — les horaires
 * ne changent jamais d'offset dans cette région).
 * Les dates sont toujours stockées en UTC ; la conversion est faite côté
 * serveur (saisie → UTC) et côté client (UTC → fuseau affiché).
 */

return [

    'timezone' => env('LIVE_SESSION_TIMEZONE', 'Africa/Lubumbashi'),

    /**
     * Fuseaux proposés dans le sélecteur de la planification.
     */
    'timezones' => [
        ['value' => 'Africa/Lubumbashi', 'label' => 'Goma / Lubumbashi — UTC+2'],
        ['value' => 'Africa/Kigali',      'label' => 'Kigali / Bukavu — UTC+2'],
        ['value' => 'Africa/Kinshasa',    'label' => 'Kinshasa / Brazzaville — UTC+1'],
        ['value' => 'Africa/Brazzaville', 'label' => 'Brazzaville — UTC+1'],
        ['value' => 'Africa/Douala',      'label' => 'Douala / Abidjan — UTC+1'],
        ['value' => 'Africa/Dakar',       'label' => 'Dakar — UTC+0'],
        ['value' => 'Europe/Paris',       'label' => 'Paris — UTC+1/+2'],
        ['value' => 'UTC',                'label' => 'UTC (coordonné universel)'],
    ],

];
