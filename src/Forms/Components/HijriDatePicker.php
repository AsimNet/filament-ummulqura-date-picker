<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura\Forms\Components;

class HijriDatePicker extends HijriDateTimePicker
{
    public function hasTime(): bool
    {
        return false;
    }
}
