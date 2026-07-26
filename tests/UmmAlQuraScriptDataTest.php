<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Tests;

use AsimNet\FilamentUmmulqura\Support\Reckonings;
use AsimNet\FilamentUmmulqura\Support\UmmAlQuraScriptData;
use AsimNet\UmmulquraCalendar\UmmAlQura;
use PHPUnit\Framework\TestCase;

final class UmmAlQuraScriptDataTest extends TestCase
{
    public function test_epoch_is_the_gregorian_date_of_the_first_supported_hijri_day(): void
    {
        $this->assertSame(UmmAlQura::GREGORIAN_MIN, UmmAlQuraScriptData::epoch());
    }

    public function test_month_lengths_string_encodes_every_supported_month(): void
    {
        $lengths = UmmAlQuraScriptData::monthLengths();

        $expectedMonths = (UmmAlQura::HIJRI_MAX_YEAR - UmmAlQura::HIJRI_MIN_YEAR + 1) * 12;

        $this->assertSame($expectedMonths, strlen($lengths));

        $index = 0;

        for ($year = UmmAlQura::HIJRI_MIN_YEAR; $year <= UmmAlQura::HIJRI_MAX_YEAR; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $expected = match (UmmAlQura::monthLength($year, $month, Reckonings::forHijriYear($year))) {
                    28 => '8',
                    29 => '9',
                    default => '0',
                };

                if ($lengths[$index] !== $expected) {
                    $this->fail("Month length mismatch at {$year}-{$month}.");
                }

                $index++;
            }
        }

        $this->assertSame($expectedMonths, $index);
    }

    public function test_month_lengths_use_the_as_published_reckoning(): void
    {
        // Sha'ban 1364 AH is 28 days only in the as-published tables that
        // Saudi civil records follow; the official reconstruction never is.
        // Its presence proves the client engine ships as-published data.
        $index = ((1364 - UmmAlQura::HIJRI_MIN_YEAR) * 12) + (8 - 1);

        $this->assertSame('8', UmmAlQuraScriptData::monthLengths()[$index]);
    }

    public function test_hijri_month_names_follow_the_locale(): void
    {
        $arabic = UmmAlQuraScriptData::hijriMonthNames('ar');
        $english = UmmAlQuraScriptData::hijriMonthNames('en');

        $this->assertCount(12, $arabic);
        $this->assertCount(12, $english);
        $this->assertSame('محرم', $arabic[0]);
        $this->assertSame('رمضان', $arabic[8]);
        $this->assertSame('Muharram', $english[0]);
        $this->assertSame('Ramadan', $english[8]);
    }
}
