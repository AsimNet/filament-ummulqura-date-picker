<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Support;

use AsimNet\UmmulquraCalendar\HijriDate;
use AsimNet\UmmulquraCalendar\UmmAlQura;

/**
 * Compact representations of the Umm al-Qura tables for shipping to the
 * Alpine component, derived from the asimnet/ummulqura-calendar public API
 * so both sides always agree.
 */
final class UmmAlQuraScriptData
{
    private static ?string $epoch = null;

    private static ?string $monthLengths = null;

    /**
     * Gregorian date (Y-m-d) of 1 Muharram of the first supported Hijri year.
     */
    public static function epoch(): string
    {
        return self::$epoch ??= UmmAlQura::toGregorian(UmmAlQura::HIJRI_MIN_YEAR, 1, 1)->format('Y-m-d');
    }

    /**
     * One character per supported Hijri month, in chronological order:
     * '8' for a 28-day month, '9' for a 29-day month, '0' for a 30-day month.
     * (The as-published tables carry a single 28-day month, Sha'ban 1364 AH.)
     *
     * Lengths follow the same reckoning as every other conversion in the
     * package — see {@see Reckonings} — so the client engine agrees with the
     * PHP side, including for pre-1420 AH dates recorded on national IDs.
     */
    public static function monthLengths(): string
    {
        if (self::$monthLengths !== null) {
            return self::$monthLengths;
        }

        $lengths = '';

        for ($year = UmmAlQura::HIJRI_MIN_YEAR; $year <= UmmAlQura::HIJRI_MAX_YEAR; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $lengths .= match (UmmAlQura::monthLength($year, $month, Reckonings::forHijriYear($year))) {
                    28 => '8',
                    29 => '9',
                    default => '0',
                };
            }
        }

        return self::$monthLengths = $lengths;
    }

    /**
     * The twelve Hijri month names for the given locale.
     *
     * @return array<int, string>
     */
    public static function hijriMonthNames(string $locale): array
    {
        $names = [];

        foreach (range(1, 12) as $month) {
            $date = new HijriDate(UmmAlQura::HIJRI_MIN_YEAR, $month, 1);

            $names[] = str_starts_with($locale, 'ar') ? $date->monthNameAr() : $date->monthNameEn();
        }

        return $names;
    }
}
