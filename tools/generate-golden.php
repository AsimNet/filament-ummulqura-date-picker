<?php

declare(strict_types=1);

/*
 * Emits golden Hijri<->Gregorian pairs (every month start + middle + end in
 * the supported range) as JSON for tools/verify-js-engine.mjs.
 */

use AsimNet\FilamentUmmulqura\Support\UmmAlQuraScriptData;
use AsimNet\UmmulquraCalendar\UmmAlQura;

foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;

        break;
    }
}

if (! class_exists(UmmAlQuraScriptData::class)) {
    require_once __DIR__.'/../src/Support/UmmAlQuraScriptData.php';
}

$pairs = [];
$monthLengthSamples = [];

for ($year = UmmAlQura::HIJRI_MIN_YEAR; $year <= UmmAlQura::HIJRI_MAX_YEAR; $year++) {
    for ($month = 1; $month <= 12; $month++) {
        $length = UmmAlQura::monthLength($year, $month);
        $monthLengthSamples[] = [$year, $month, $length];

        foreach ([1, 15, $length] as $day) {
            $pairs[] = [$year, $month, $day, UmmAlQura::toGregorian($year, $month, $day)->format('Y-m-d')];
        }
    }
}

$rjd = static function (string $date): int {
    [$year, $month, $day] = array_map(intval(...), explode('-', $date));

    return gregoriantojd($month, $day, $year) - 2400000;
};

echo json_encode([
    'epoch' => UmmAlQuraScriptData::epoch(),
    'monthLengths' => UmmAlQuraScriptData::monthLengths(),
    'minYear' => UmmAlQura::HIJRI_MIN_YEAR,
    'minRjd' => $rjd(UmmAlQura::GREGORIAN_MIN),
    'maxRjd' => $rjd(UmmAlQura::GREGORIAN_MAX),
    'pairs' => $pairs,
    'monthLengthSamples' => $monthLengthSamples,
]);
