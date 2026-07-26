# Ummulqura Hijri Date Picker for Filament

A Hijri (Umm al-Qura) date & time picker for [Filament](https://filamentphp.com) that always shows the user **both calendars while picking**, and lets you choose independently which calendar the UI displays and which calendar the value is stored in.

Conversions use [`asimnet/ummulqura-calendar`](https://github.com/AsimNet/ummulqura-calendar) — exact month-start tables harvested from the official Saudi KACST Umm al-Qura service, not an arithmetic approximation.

## Features

- **Real Hijri month grid** — month lengths (29/30 days) come from the official tables, including years where Dhu al-Hijjah has 30 days.
- **Two independent axes** — display calendar (what the picker shows) and storage calendar (what goes into your database), in any combination.
- **Equivalent date always visible** — the display input shows the other calendar's date in parentheses, and the panel footer live-updates both calendars for the focused day, so the user always knows exactly which day they are picking.
- **Runtime calendar switch** — a button in the panel lets the user flip the grid between Hijri and Gregorian on the fly; the stored value never changes (it stays in the configured storage calendar). Disable with `->calendarToggle(false)`.
- **Time support** — hours/minutes/seconds alongside the Hijri date, preserved in either storage calendar.
- **Server-side authority** — the selected value is re-validated on the server against the Umm al-Qura tables; nonexistent dates (e.g. 30 Safar) are rejected with a translatable message.
- **Familiar API** — extends Filament's own `DateTimePicker`, so `minDate()`, `maxDate()`, `disabledDates()`, `displayFormat()`, `closeOnDateSelection()`, `firstDayOfWeek()` and friends all work.
- RTL, dark mode, keyboard navigation, Arabic/English month names, and optional Eastern Arabic (Indic) numerals.
- Supported range: **1318-01-01 → 1500-12-30 AH** (1900-04-30 → 2077-11-16 CE).

## Matching national IDs and civil records

Conversions follow the Umm al-Qura calendar **as it was published at the time** (`Reckoning::AsPublished` in the underlying library). This only affects dates before **1420 AH (April 1999)**: today's official KACST service applies the current criterion retroactively and can land a day off from what the almanac actually printed — and the printed value is what Saudi national IDs and Ahwal Madaniyya documents recorded. The picker uses the printed value, so a birth date round-trips against the ID.

From 1420 AH onward both readings are identical, so present-day and future dates are unaffected. For 1318–1355 AH (before 1937 CE), where the historical almanacs are not settled, the picker falls back to the official reconstruction.

## Installation

```bash
composer require asimnet/filament-ummulqura-date-picker
php artisan filament:assets
```

## Usage

### Hijri in the UI, Gregorian in the database (default)

The user picks a Hijri date; your model receives a plain Gregorian `Y-m-d` (or `Y-m-d H:i:s`) value — no schema changes, works with `date`/`datetime` casts:

```php
use AsimNet\FilamentUmmulqura\Forms\Components\HijriDatePicker;
use AsimNet\FilamentUmmulqura\Forms\Components\HijriDateTimePicker;

HijriDatePicker::make('birth_date')

HijriDateTimePicker::make('starts_at') // with time (and seconds by default)
```

### Gregorian in the UI, Hijri in the database

```php
use AsimNet\FilamentUmmulqura\Enums\CalendarSystem;

HijriDatePicker::make('hijri_date')
    ->displayCalendar(CalendarSystem::Gregorian)
    ->storageCalendar(CalendarSystem::Hijri) // state: "1447-01-15"
```

Any combination of the two axes is valid — including Hijri display with Hijri storage for legacy Hijri columns. When the storage calendar is Hijri, filling the form with a `DateTimeInterface`/`Carbon` instance or an `AsimNet\UmmulquraCalendar\HijriDate` object converts automatically.

### Options

```php
HijriDateTimePicker::make('starts_at')
    ->seconds(false)                      // hide seconds
    ->time(false)                         // date only (or use HijriDatePicker)
    ->indicDigits()                       // ٢٥ محرم ١٤٤٧هـ instead of 25 محرم 1447هـ
    ->equivalentDate(false)               // hide the other-calendar hint
    ->calendarToggle(false)               // hide the in-panel Hijri/Gregorian switch button
    ->displayFormat('j F Yهـ H:i')        // PHP date() tokens, applied to the display calendar
    ->minDate(now()->subYear())           // Gregorian bound (Carbon/string)
    ->maxHijriDate('1447-12-29')          // or bound it in Hijri directly
    ->minDate(new HijriDate(1445, 1, 1))  // HijriDate objects work everywhere
    ->disabledDates(['2025-07-01'])       // Gregorian, like the stock picker
    ->closeOnDateSelection()
    ->locale('ar')                        // month/weekday names (defaults to app locale)
```

`displayFormat()` supports the PHP tokens `d j D l m n M F y Y H G h g i s A a` — `F`/`M` and `D`/`l` resolve month and weekday names in the display calendar and locale.

### Storage formats

| Storage calendar | Date only | With time |
| --- | --- | --- |
| Gregorian (default) | `2025-06-26` | `2025-06-26 14:30:00` |
| Hijri | `1447-01-01` | `1447-01-01 14:30:00` |

### Displaying stored Gregorian dates as Hijri in tables

```php
use AsimNet\UmmulquraCalendar\UmmAlQura;

TextColumn::make('birth_date')
    ->formatStateUsing(fn ($state) => UmmAlQura::format(Carbon::parse($state), 'j F Yهـ'))
```

## Notes & caveats

- Conversion is **civil-date based** (midnight boundary). The religious Hijri day begins at sunset; if you show a date near midnight it follows the civil convention, like every Umm al-Qura conversion service.
- Dates outside the supported range fail validation with a translatable message instead of throwing.
- The JS calendar engine is generated from the PHP package's tables and is cross-verified against it (every month start, middle, and end in the supported range) by `tools/verify-js-engine.mjs`.

## Testing

```bash
composer test
```

## Credits & license

- Calendar data: [KACST Umm al-Qura](https://ummulqura.org.sa) via [asimnet/ummulqura-calendar](https://github.com/AsimNet/ummulqura-calendar).
- Component structure inspired by Filament's own `DateTimePicker`.

MIT — see [LICENSE](LICENSE).
