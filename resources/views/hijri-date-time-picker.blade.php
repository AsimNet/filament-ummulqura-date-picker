@php
    $fieldWrapperView = $getFieldWrapperView();
    $datalistOptions = $getDatalistOptions();
    $disabledDates = $getDisabledDates();
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $extraAttributeBag = $getExtraAttributeBag();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $hasDate = $hasDate();
    $hasTime = $hasTime();
    $hasSeconds = $hasSeconds();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isAutofocused = $isAutofocused();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $maxDate = $getMaxDate();
    $minDate = $getMinDate();
    $defaultFocusedDate = $getDefaultFocusedDate();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $placeholder = $getPlaceholder();
    $isReadOnly = $isReadOnly();
    $isConcealed = $isConcealed();
    $livewireKey = $getLivewireKey();
    $displayCalendar = $getDisplayCalendar()->value;
    $storageCalendar = $getStorageCalendar()->value;
    $hasIndicDigits = $hasIndicDigits();
    $shouldShowEquivalentDate = $shouldShowEquivalentDate();
    $isCalendarToggleAllowed = $isCalendarToggleAllowed();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$prefixIconColor"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$suffixIconColor"
        :valid="! $errors->has($statePath)"
        x-on:focus-input.stop="$el.querySelector('input:not([type=hidden])')?.focus()"
        :attributes="\Filament\Support\prepare_inherited_attributes($extraAttributeBag)->class(['fi-fo-date-time-picker fi-fo-hijri-date-time-picker'])"
    >
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('hijri-date-time-picker', 'asimnet/filament-ummulqura-date-picker') }}"
            x-data="hijriDateTimePickerFormComponent({
                        defaultFocusedDate: @js($defaultFocusedDate),
                        allowCalendarToggle: @js($isCalendarToggleAllowed),
                        displayCalendar: @js($displayCalendar),
                        displayFormatHijri: @js($getHijriDisplayFormat()),
                        displayFormatGregorian: @js($getGregorianDisplayFormat()),
                        toggleToHijriLabel: @js(__('filament-ummulqura-date-picker::picker.switch_to_hijri')),
                        toggleToGregorianLabel: @js(__('filament-ummulqura-date-picker::picker.switch_to_gregorian')),
                        firstDayOfWeek: {{ $getFirstDayOfWeek() }},
                        hijriEpoch: @js($getUmmAlQuraEpoch()),
                        hijriMaxYear: {{ $getHijriMaxYear() }},
                        hijriMinYear: {{ $getHijriMinYear() }},
                        hijriMonthLengths: @js($getUmmAlQuraMonthLengths()),
                        hijriMonthNames: @js($getHijriMonthNames()),
                        indicDigits: @js($hasIndicDigits),
                        isAutofocused: @js($isAutofocused),
                        locale: @js($getLocale()),
                        shouldCloseOnDateSelection: @js($shouldCloseOnDateSelection()),
                        shouldShowEquivalentDate: @js($shouldShowEquivalentDate),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        storageCalendar: @js($storageCalendar),
                    })"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{
                substr(md5(serialize([
                    $disabledDates,
                    $isDisabled,
                    $isReadOnly,
                    $maxDate,
                    $minDate,
                    $hasDate,
                    $hasTime,
                    $hasSeconds,
                    $displayCalendar,
                    $storageCalendar,
                    $hasIndicDigits,
                    $shouldShowEquivalentDate,
                    $isCalendarToggleAllowed,
                ])), 0, 64)
            }}"
            x-on:keydown.esc="isOpen() && $event.stopPropagation()"
            {{ $getExtraAlpineAttributeBag() }}
        >
            <input x-ref="maxDate" type="hidden" value="{{ $maxDate }}" />

            <input x-ref="minDate" type="hidden" value="{{ $minDate }}" />

            <input
                x-ref="disabledDates"
                type="hidden"
                value="{{ json_encode($disabledDates) }}"
            />

            <button
                x-ref="button"
                x-on:click="togglePanelVisibility()"
                x-on:keydown.enter.prevent.stop="
                    if (! $el.disabled) {
                        isOpen() ? selectDate() : togglePanelVisibility()
                    }
                "
                x-on:keydown.arrow-left.prevent.stop="if (! $el.disabled) focusPreviousDay()"
                x-on:keydown.arrow-right.prevent.stop="if (! $el.disabled) focusNextDay()"
                x-on:keydown.arrow-up.prevent.stop="if (! $el.disabled) focusPreviousWeek()"
                x-on:keydown.arrow-down.prevent.stop="if (! $el.disabled) focusNextWeek()"
                x-on:keydown.backspace.prevent.stop="if (! $el.disabled) clearState()"
                x-on:keydown.clear.prevent.stop="if (! $el.disabled) clearState()"
                x-on:keydown.delete.prevent.stop="if (! $el.disabled) clearState()"
                aria-label="{{ $placeholder }}"
                type="button"
                tabindex="-1"
                @disabled($isDisabled || $isReadOnly)
                {{
                    $getExtraTriggerAttributeBag()->class([
                        'fi-fo-date-time-picker-trigger',
                    ])
                }}
            >
                <input
                    @disabled($isDisabled)
                    readonly
                    placeholder="{{ $placeholder }}"
                    wire:key="{{ $livewireKey }}.display-text"
                    x-model="displayText"
                    @if ($id) id="{{ $id }}" @endif
                    @class([
                        'fi-fo-date-time-picker-display-text-input',
                    ])
                />
            </button>

            <div
                x-ref="panel"
                x-cloak
                x-float.placement.bottom-start.offset.flip.shift="{ offset: 8 }"
                wire:ignore
                wire:key="{{ $livewireKey }}.panel"
                @class([
                    'fi-fo-date-time-picker-panel',
                    'fi-fo-hijri-date-time-picker-panel',
                ])
            >
                @if ($hasDate)
                    <div class="fi-fo-date-time-picker-panel-header">
                        {{-- x-effect re-applies the selection after the
                             options themselves render: x-model writes the
                             value while the x-for template is still empty,
                             which would otherwise leave the field showing
                             the first month whatever the focused one is. --}}
                        <select
                            x-model="focusedMonth"
                            x-effect="months.length && $nextTick(() => ($el.value = focusedMonth))"
                            class="fi-fo-date-time-picker-month-select"
                        >
                            <template x-for="(month, index) in months">
                                <option
                                    x-bind:value="index + 1"
                                    x-text="month"
                                ></option>
                            </template>
                        </select>

                        <input
                            type="number"
                            inputmode="numeric"
                            x-model.debounce="focusedYear"
                            x-bind:min="yearInputMin"
                            x-bind:max="yearInputMax"
                            class="fi-fo-date-time-picker-year-input"
                        />
                    </div>

                    <div class="fi-fo-date-time-picker-calendar-header">
                        <template
                            x-for="(day, index) in dayLabels"
                            x-bind:key="index"
                        >
                            <div
                                x-text="day"
                                class="fi-fo-date-time-picker-calendar-header-day"
                            ></div>
                        </template>
                    </div>

                    <div
                        role="grid"
                        class="fi-fo-date-time-picker-calendar"
                    >
                        <template
                            x-for="day in emptyDaysInFocusedMonth"
                            x-bind:key="day"
                        >
                            <div></div>
                        </template>

                        <template
                            x-for="day in daysInFocusedMonth"
                            x-bind:key="day"
                        >
                            <div
                                x-text="formatGridDay(day)"
                                x-on:click="dayIsDisabled(day) || selectDate(day)"
                                x-on:mouseenter="setFocusedDay(day)"
                                role="option"
                                x-bind:aria-selected="focusedDay === day"
                                x-bind:class="{
                                    'fi-fo-date-time-picker-calendar-day-today': dayIsToday(day),
                                    'fi-focused': focusedDay === day,
                                    'fi-selected': dayIsSelected(day),
                                    'fi-disabled': dayIsDisabled(day),
                                }"
                                class="fi-fo-date-time-picker-calendar-day"
                            ></div>
                        </template>
                    </div>
                @endif

                @if ($hasTime)
                    <div class="fi-fo-date-time-picker-time-inputs">
                        <input
                            max="23"
                            min="0"
                            step="{{ $getHoursStep() }}"
                            type="number"
                            inputmode="numeric"
                            x-model.debounce="hour"
                        />

                        <span
                            class="fi-fo-date-time-picker-time-input-separator"
                        >
                            :
                        </span>

                        <input
                            max="59"
                            min="0"
                            step="{{ $getMinutesStep() }}"
                            type="number"
                            inputmode="numeric"
                            x-model.debounce="minute"
                        />

                        @if ($hasSeconds)
                            <span
                                class="fi-fo-date-time-picker-time-input-separator"
                            >
                                :
                            </span>

                            <input
                                max="59"
                                min="0"
                                step="{{ $getSecondsStep() }}"
                                type="number"
                                inputmode="numeric"
                                x-model.debounce="second"
                            />
                        @endif
                    </div>
                @endif

                @if ($hasDate)
                    <div class="fi-fo-hijri-date-time-picker-footer">
                        @if ($shouldShowEquivalentDate)
                            <span
                                class="fi-fo-hijri-date-time-picker-footer-equivalent"
                                x-text="focusedDualText"
                            ></span>
                        @endif

                        <div class="fi-fo-hijri-date-time-picker-footer-actions">
                            @if ($isCalendarToggleAllowed)
                                <button
                                    type="button"
                                    class="fi-fo-hijri-date-time-picker-footer-toggle-btn"
                                    x-on:click="toggleCalendar()"
                                    x-text="calendarToggleLabel()"
                                ></button>
                            @endif

                            <button
                                type="button"
                                class="fi-fo-hijri-date-time-picker-footer-today-btn"
                                x-on:click="selectToday()"
                            >
                                {{ __('filament-ummulqura-date-picker::picker.today') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::input.wrapper>

    @if ($datalistOptions)
        <datalist id="{{ $id }}-list">
            @foreach ($datalistOptions as $option)
                <option value="{{ $option }}" />
            @endforeach
        </datalist>
    @endif
</x-dynamic-component>
