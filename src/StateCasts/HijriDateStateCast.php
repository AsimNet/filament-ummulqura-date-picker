<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\StateCasts;

use AsimNet\UmmulquraCalendar\HijriDate;
use AsimNet\UmmulquraCalendar\OutOfSupportedRangeException;
use AsimNet\UmmulquraCalendar\UmmAlQura;
use DateTimeInterface;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * State cast for fields whose storage calendar is Hijri. The internal state
 * is always a "Y-m-d H:i:s"-shaped Hijri string; hydration accepts Hijri
 * strings, HijriDate objects, or Gregorian DateTime objects (converted via
 * the Umm al-Qura tables), and dehydration reshapes the internal state to
 * the field's configured format. Calendar validity (e.g. day 30 of a 29-day
 * month) is deliberately left to the field's validation rule so users get an
 * error instead of a silently cleared value.
 */
class HijriDateStateCast implements StateCast
{
    final public const INTERNAL_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        protected string $format,
    ) {}

    public function get(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $parts = static::parse((string) $state);

        if ($parts === null) {
            return null;
        }

        return static::formatParts($parts, $this->format);
    }

    public function set(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if ($state instanceof HijriDate) {
            $state = $state->toIsoString();
        }

        if ($state instanceof DateTimeInterface) {
            try {
                $hijri = UmmAlQura::toHijri($state);
            } catch (OutOfSupportedRangeException) {
                return null;
            }

            return "{$hijri->toIsoString()} {$state->format('H:i:s')}";
        }

        $parts = static::parse((string) $state);

        if ($parts === null) {
            return null;
        }

        return static::formatParts($parts, self::INTERNAL_FORMAT);
    }

    /**
     * Parse a "Y-m-d", "Y-m-d H:i" or "Y-m-d H:i:s" shaped string into its
     * numeric parts without asserting calendar validity.
     *
     * @return array{year: int, month: int, day: int, hour: int, minute: int, second: int}|null
     */
    public static function parse(string $value): ?array
    {
        if (! preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[T ](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', trim($value), $matches)) {
            return null;
        }

        return [
            'year' => (int) $matches[1],
            'month' => (int) $matches[2],
            'day' => (int) $matches[3],
            'hour' => (int) ($matches[4] ?? 0),
            'minute' => (int) ($matches[5] ?? 0),
            'second' => (int) ($matches[6] ?? 0),
        ];
    }

    /**
     * Format parsed parts using PHP date() tokens applied to the HIJRI
     * components: Y y m n d j F (Arabic month name) M (English month name)
     * plus time tokens H G h g i s A a. A backslash escapes the next
     * character.
     *
     * @param  array{year: int, month: int, day: int, hour: int, minute: int, second: int}  $parts
     */
    public static function formatParts(array $parts, string $pattern): string
    {
        $date = new HijriDate($parts['year'], $parts['month'], $parts['day']);

        $out = '';
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $out .= $pattern[++$i];

                continue;
            }

            $out .= match ($char) {
                'Y' => sprintf('%04d', $parts['year']),
                'y' => substr(sprintf('%04d', $parts['year']), -2),
                'm' => sprintf('%02d', $parts['month']),
                'n' => (string) $parts['month'],
                'd' => sprintf('%02d', $parts['day']),
                'j' => (string) $parts['day'],
                'F' => $date->monthNameAr(),
                'M' => $date->monthNameEn(),
                'H' => sprintf('%02d', $parts['hour']),
                'G' => (string) $parts['hour'],
                'h' => sprintf('%02d', $parts['hour'] % 12 ?: 12),
                'g' => (string) ($parts['hour'] % 12 ?: 12),
                'i' => sprintf('%02d', $parts['minute']),
                's' => sprintf('%02d', $parts['second']),
                'A' => $parts['hour'] < 12 ? 'AM' : 'PM',
                'a' => $parts['hour'] < 12 ? 'am' : 'pm',
                default => $char,
            };
        }

        return $out;
    }
}
