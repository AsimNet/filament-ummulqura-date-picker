<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Tests;

use AsimNet\FilamentUmmulqura\StateCasts\HijriDateStateCast;
use AsimNet\UmmulquraCalendar\HijriDate;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class HijriDateStateCastTest extends TestCase
{
    public function test_set_normalizes_a_date_only_string_to_the_internal_format(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertSame('1447-01-01 00:00:00', $cast->set('1447-01-01'));
        $this->assertSame('1447-01-01 14:30:00', $cast->set('1447-1-1 14:30'));
    }

    public function test_set_accepts_hijri_date_objects(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertSame('1447-01-15 00:00:00', $cast->set(new HijriDate(1447, 1, 15)));
    }

    public function test_set_converts_gregorian_date_objects_using_the_umm_al_qura_tables(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertSame('1447-01-01 08:15:30', $cast->set(new DateTimeImmutable('2025-06-26 08:15:30')));
    }

    public function test_set_returns_null_for_gregorian_dates_outside_the_supported_range(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertNull($cast->set(new DateTimeImmutable('1850-01-01')));
    }

    public function test_set_returns_null_for_unparseable_values(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertNull($cast->set('not-a-date'));
        $this->assertNull($cast->set(''));
        $this->assertNull($cast->set(null));
    }

    public function test_set_keeps_pattern_valid_but_nonexistent_dates_for_validation_to_reject(): void
    {
        $cast = new HijriDateStateCast('Y-m-d');

        $this->assertSame('1447-02-30 00:00:00', $cast->set('1447-02-30'));
    }

    public function test_get_reshapes_the_internal_state_to_the_configured_format(): void
    {
        $this->assertSame('1447-01-01', (new HijriDateStateCast('Y-m-d'))->get('1447-01-01 14:30:45'));
        $this->assertSame('1447-01-01 14:30', (new HijriDateStateCast('Y-m-d H:i'))->get('1447-01-01 14:30:45'));
        $this->assertSame('1447-01-01 14:30:45', (new HijriDateStateCast('Y-m-d H:i:s'))->get('1447-01-01 14:30:45'));
    }

    public function test_format_parts_supports_month_name_and_twelve_hour_tokens(): void
    {
        $parts = HijriDateStateCast::parse('1447-09-01 14:05:00');

        $this->assertSame('1 رمضان 1447هـ', HijriDateStateCast::formatParts($parts, 'j F Yهـ'));
        $this->assertSame('1 Ramadan 1447', HijriDateStateCast::formatParts($parts, 'j M Y'));
        $this->assertSame('02:05 PM', HijriDateStateCast::formatParts($parts, 'h:i A'));
    }

    public function test_parse_rejects_malformed_strings(): void
    {
        $this->assertNull(HijriDateStateCast::parse('1447/01/01'));
        $this->assertNull(HijriDateStateCast::parse('1447-01'));
        $this->assertNull(HijriDateStateCast::parse('tomorrow'));
    }
}
