<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Forms\Components;

use AsimNet\FilamentUmmulqura\Enums\CalendarSystem;
use AsimNet\FilamentUmmulqura\StateCasts\HijriDateStateCast;
use AsimNet\FilamentUmmulqura\Support\UmmAlQuraScriptData;
use AsimNet\UmmulquraCalendar\HijriDate;
use AsimNet\UmmulquraCalendar\OutOfSupportedRangeException;
use AsimNet\UmmulquraCalendar\UmmAlQura;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Illuminate\Support\Carbon;

/**
 * A Filament date-time picker on the official Saudi Umm al-Qura calendar.
 *
 * Two independent axes are configurable: the calendar the picker UI shows
 * (`displayCalendar()`, Hijri by default) and the calendar the state is
 * stored in (`storageCalendar()`, Gregorian by default). While picking, the
 * equivalent date in the other calendar is always shown so the user knows
 * exactly which day they are selecting.
 */
class HijriDateTimePicker extends DateTimePicker
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-ummulqura-date-picker::hijri-date-time-picker';

    protected CalendarSystem|string|Closure $displayCalendar = CalendarSystem::Hijri;

    protected CalendarSystem|string|Closure $storageCalendar = CalendarSystem::Gregorian;

    protected bool|Closure $hasIndicDigits = false;

    protected bool|Closure $shouldShowEquivalentDate = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(
            static fn (HijriDateTimePicker $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (blank($value)) {
                    return;
                }

                $parts = HijriDateStateCast::parse((string) $value);

                if ($parts === null) {
                    $fail(__('filament-ummulqura-date-picker::validation.invalid_hijri_date'));

                    return;
                }

                try {
                    $gregorian = UmmAlQura::toGregorian($parts['year'], $parts['month'], $parts['day']);
                } catch (OutOfSupportedRangeException) {
                    $fail(__('filament-ummulqura-date-picker::validation.invalid_hijri_date'));

                    return;
                }

                $timestamp = Carbon::instance($gregorian)->setTime($parts['hour'], $parts['minute'], $parts['second']);

                if (($minDate = $component->getMinDate()) && $timestamp->lt(Carbon::parse($minDate))) {
                    $fail(__('filament-ummulqura-date-picker::validation.after_or_equal', [
                        'date' => UmmAlQura::format(Carbon::parse($minDate), 'j F Yهـ'),
                    ]));
                }

                if (($maxDate = $component->getMaxDate()) && $timestamp->gt(Carbon::parse($maxDate)->endOfDay())) {
                    $fail(__('filament-ummulqura-date-picker::validation.before_or_equal', [
                        'date' => UmmAlQura::format(Carbon::parse($maxDate), 'j F Yهـ'),
                    ]));
                }
            },
            static fn (HijriDateTimePicker $component): bool => $component->storesHijri() && $component->hasDate(),
        );
    }

    /**
     * The picker never renders a native HTML input: no browser implements an
     * Umm al-Qura `<input type="date">`.
     */
    public function isNative(): bool
    {
        return false;
    }

    public function displayCalendar(CalendarSystem|string|Closure $calendar): static
    {
        $this->displayCalendar = $calendar;

        return $this;
    }

    public function storageCalendar(CalendarSystem|string|Closure $calendar): static
    {
        $this->storageCalendar = $calendar;

        return $this;
    }

    /**
     * Render digits in the display text and calendar grid using Eastern
     * Arabic (Indic) numerals (٠-٩).
     */
    public function indicDigits(bool|Closure $condition = true): static
    {
        $this->hasIndicDigits = $condition;

        return $this;
    }

    /**
     * Toggle the equivalent-date hint (the other calendar's date) shown in
     * the display input and the picker panel.
     */
    public function equivalentDate(bool|Closure $condition = true): static
    {
        $this->shouldShowEquivalentDate = $condition;

        return $this;
    }

    public function getDisplayCalendar(): CalendarSystem
    {
        $calendar = $this->evaluate($this->displayCalendar);

        return $calendar instanceof CalendarSystem ? $calendar : CalendarSystem::from($calendar);
    }

    public function getStorageCalendar(): CalendarSystem
    {
        $calendar = $this->evaluate($this->storageCalendar);

        return $calendar instanceof CalendarSystem ? $calendar : CalendarSystem::from($calendar);
    }

    public function displaysHijri(): bool
    {
        return $this->getDisplayCalendar() === CalendarSystem::Hijri;
    }

    public function storesHijri(): bool
    {
        return $this->getStorageCalendar() === CalendarSystem::Hijri;
    }

    public function hasIndicDigits(): bool
    {
        return (bool) $this->evaluate($this->hasIndicDigits);
    }

    public function shouldShowEquivalentDate(): bool
    {
        return (bool) $this->evaluate($this->shouldShowEquivalentDate);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if (! $this->storesHijri()) {
            return parent::getDefaultStateCasts();
        }

        return [
            ...Field::getDefaultStateCasts(),
            app(HijriDateStateCast::class, [
                'format' => $this->getFormat(),
            ]),
        ];
    }

    public function maxDate(HijriDate|CarbonInterface|string|Closure|null $date): static
    {
        $this->maxDate = static::gregorianDateConfig($date);

        $this->rule(static function (HijriDateTimePicker $component) {
            return "before_or_equal:{$component->getMaxDate()}";
        }, static fn (HijriDateTimePicker $component): bool => (bool) $component->getMaxDate() && ! $component->storesHijri());

        return $this;
    }

    public function minDate(HijriDate|CarbonInterface|string|Closure|null $date): static
    {
        $this->minDate = static::gregorianDateConfig($date);

        $this->rule(static function (HijriDateTimePicker $component) {
            return "after_or_equal:{$component->getMinDate()}";
        }, static fn (HijriDateTimePicker $component): bool => (bool) $component->getMinDate() && ! $component->storesHijri());

        return $this;
    }

    /**
     * Set the maximum selectable date from a Hijri date ("1447-01-15" or a
     * HijriDate instance).
     */
    public function maxHijriDate(HijriDate|string|Closure|null $date): static
    {
        return $this->maxDate(static fn (HijriDateTimePicker $component): ?string => static::gregorianDateStringFromHijri($component->evaluate($date)));
    }

    /**
     * Set the minimum selectable date from a Hijri date ("1447-01-15" or a
     * HijriDate instance).
     */
    public function minHijriDate(HijriDate|string|Closure|null $date): static
    {
        return $this->minDate(static fn (HijriDateTimePicker $component): ?string => static::gregorianDateStringFromHijri($component->evaluate($date)));
    }

    public function getDefaultDateDisplayFormat(): string
    {
        return $this->displaysHijri() ? 'j F Yهـ' : parent::getDefaultDateDisplayFormat();
    }

    public function getDefaultDateTimeDisplayFormat(): string
    {
        return $this->displaysHijri() ? 'j F Yهـ H:i' : parent::getDefaultDateTimeDisplayFormat();
    }

    public function getDefaultDateTimeWithSecondsDisplayFormat(): string
    {
        return $this->displaysHijri() ? 'j F Yهـ H:i:s' : parent::getDefaultDateTimeWithSecondsDisplayFormat();
    }

    /**
     * @return array<int, string>
     */
    public function getHijriMonthNames(): array
    {
        return UmmAlQuraScriptData::hijriMonthNames($this->getLocale());
    }

    public function getUmmAlQuraEpoch(): string
    {
        return UmmAlQuraScriptData::epoch();
    }

    public function getUmmAlQuraMonthLengths(): string
    {
        return UmmAlQuraScriptData::monthLengths();
    }

    public function getHijriMinYear(): int
    {
        return UmmAlQura::HIJRI_MIN_YEAR;
    }

    public function getHijriMaxYear(): int
    {
        return UmmAlQura::HIJRI_MAX_YEAR;
    }

    protected static function gregorianDateConfig(HijriDate|CarbonInterface|string|Closure|null $date): CarbonInterface|string|Closure|null
    {
        if ($date instanceof HijriDate) {
            return static::gregorianDateStringFromHijri($date);
        }

        return $date;
    }

    protected static function gregorianDateStringFromHijri(HijriDate|string|null $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        if (is_string($date)) {
            $parts = HijriDateStateCast::parse($date);

            if ($parts === null) {
                return null;
            }

            $date = new HijriDate($parts['year'], $parts['month'], $parts['day']);
        }

        return UmmAlQura::toGregorian($date->year, $date->month, $date->day)->format('Y-m-d');
    }
}
