<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Support;

use AsimNet\UmmulquraCalendar\Reckoning;
use AsimNet\UmmulquraCalendar\UmmAlQura;
use DateTimeInterface;

/**
 * Picks which Umm al-Qura reckoning to convert against.
 *
 * The picker is meant to match Saudi civil records — national ID and Ahwal
 * Madaniyya — which hold the Hijri date as the Umm al-Qura almanac PRINTED it
 * at the time, not as today's KACST service reconstructs it. The official
 * criterion was last revised for 1420 AH, so for earlier dates the two
 * readings can differ by a day; {@see Reckoning::AsPublished} reproduces the
 * printed almanac and therefore the recorded ID date.
 *
 * AsPublished is only defined from 1356 AH / 1937 CE onward, so for anything
 * earlier we fall back to {@see Reckoning::Official}. From 1420 AH (1999 CE)
 * onward the two readings are identical, so all present-day and future dates
 * are unaffected by the choice.
 */
final class Reckonings
{
    public static function forHijriYear(int $year): Reckoning
    {
        return $year >= UmmAlQura::AS_PUBLISHED_MIN_HIJRI_YEAR
            ? Reckoning::AsPublished
            : Reckoning::Official;
    }

    public static function forGregorian(DateTimeInterface $date): Reckoning
    {
        return $date->format('Y-m-d') >= UmmAlQura::AS_PUBLISHED_MIN_GREGORIAN
            ? Reckoning::AsPublished
            : Reckoning::Official;
    }
}
