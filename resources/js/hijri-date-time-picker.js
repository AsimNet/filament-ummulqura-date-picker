const DAY_MS = 86400000

// Reduced Julian Day (JDN - 2400000) of 1970-01-01, the convention used by
// the asimnet/ummulqura-calendar month-start tables.
const UNIX_EPOCH_RJD = 40588

const INDIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']

export function parseDateParts(value) {
    if (typeof value !== 'string') {
        return null
    }

    const matches = value
        .trim()
        .match(
            /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[T ](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/,
        )

    if (!matches) {
        return null
    }

    return {
        year: +matches[1],
        month: +matches[2],
        day: +matches[3],
        hour: +(matches[4] ?? 0),
        minute: +(matches[5] ?? 0),
        second: +(matches[6] ?? 0),
    }
}

export function rjdFromGregorian(year, month, day) {
    return Math.round(Date.UTC(year, month - 1, day) / DAY_MS) + UNIX_EPOCH_RJD
}

export function gregorianFromRjd(rjd) {
    const date = new Date((rjd - UNIX_EPOCH_RJD) * DAY_MS)

    return {
        year: date.getUTCFullYear(),
        month: date.getUTCMonth() + 1,
        day: date.getUTCDate(),
    }
}

export function weekdayOfRjd(rjd) {
    return new Date((rjd - UNIX_EPOCH_RJD) * DAY_MS).getUTCDay()
}

export function createUmmAlQuraEngine({ epoch, monthLengths, minYear }) {
    const epochParts = parseDateParts(epoch)

    const starts = new Array(monthLengths.length + 1)
    starts[0] = rjdFromGregorian(
        epochParts.year,
        epochParts.month,
        epochParts.day,
    )

    for (let index = 0; index < monthLengths.length; index++) {
        starts[index + 1] =
            starts[index] + (monthLengths[index] === '9' ? 29 : 30)
    }

    const monthIndex = (year, month) => {
        const index = (year - minYear) * 12 + month - 1

        return index >= 0 && index < monthLengths.length ? index : null
    }

    return {
        minRjd: starts[0],

        maxRjd: starts[starts.length - 1] - 1,

        toHijri(rjd) {
            if (rjd < starts[0] || rjd > starts[starts.length - 1] - 1) {
                return null
            }

            let low = 0
            let high = starts.length - 2

            while (low < high) {
                const mid = (low + high + 1) >> 1

                if (starts[mid] <= rjd) {
                    low = mid
                } else {
                    high = mid - 1
                }
            }

            return {
                year: minYear + Math.floor(low / 12),
                month: (low % 12) + 1,
                day: rjd - starts[low] + 1,
            }
        },

        toRjd(year, month, day) {
            const index = monthIndex(year, month)

            return index === null ? null : starts[index] + day - 1
        },

        monthLength(year, month) {
            const index = monthIndex(year, month)

            return index === null ? null : starts[index + 1] - starts[index]
        },
    }
}

export default function hijriDateTimePickerFormComponent({
    defaultFocusedDate,
    displayCalendar,
    displayFormat,
    firstDayOfWeek,
    hijriEpoch,
    hijriMaxYear,
    hijriMinYear,
    hijriMonthLengths,
    hijriMonthNames,
    indicDigits,
    isAutofocused,
    locale,
    shouldCloseOnDateSelection,
    shouldShowEquivalentDate,
    state,
    storageCalendar,
}) {
    const engine = createUmmAlQuraEngine({
        epoch: hijriEpoch,
        monthLengths: hijriMonthLengths,
        minYear: hijriMinYear,
    })

    firstDayOfWeek = ((firstDayOfWeek ?? 1) + 7) % 7

    const intlParts = (options, rjd) =>
        new Intl.DateTimeFormat(locale, { ...options, timeZone: 'UTC' }).format(
            new Date((rjd - UNIX_EPOCH_RJD) * DAY_MS),
        )

    const gregorianMonthName = (month, style) =>
        intlParts({ month: style }, rjdFromGregorian(2001, month, 1))

    const toIndicDigits = (value) =>
        `${value}`.replace(/[0-9]/g, (digit) => INDIC_DIGITS[+digit])

    const localizeDigits = (value) =>
        indicDigits ? toIndicDigits(value) : `${value}`

    const pad = (value) => `${value}`.padStart(2, '0')

    return {
        state,

        displayText: '',

        focusedDualText: '',

        focusedYear: null,

        focusedMonth: null,

        focusedDay: null,

        hour: null,

        minute: null,

        second: null,

        isClearingState: false,

        isSyncingFocus: false,

        months: [],

        dayLabels: [],

        daysInFocusedMonth: [],

        emptyDaysInFocusedMonth: [],

        yearInputMin: null,

        yearInputMax: null,

        init() {
            this.months =
                displayCalendar === 'hijri'
                    ? hijriMonthNames
                    : Array.from({ length: 12 }, (_, index) =>
                          gregorianMonthName(index + 1, 'long'),
                      )

            this.dayLabels = this.getDayLabels()

            const yearBounds =
                displayCalendar === 'hijri'
                    ? [hijriMinYear, hijriMaxYear]
                    : [
                          gregorianFromRjd(engine.minRjd).year,
                          gregorianFromRjd(engine.maxRjd).year,
                      ]
            this.yearInputMin = yearBounds[0]
            this.yearInputMax = yearBounds[1]

            const selected = this.getSelected()

            this.hour = selected?.hour ?? 0
            this.minute = selected?.minute ?? 0
            this.second = selected?.second ?? 0

            this.setFocusedRjd(
                selected?.rjd ?? this.getDefaultFocusedRjd() ?? this.todayRjd(),
            )

            this.setDisplayText()

            if (isAutofocused) {
                this.$nextTick(() => this.togglePanelVisibility())
            }

            this.$watch('focusedMonth', () => {
                this.focusedMonth = +this.focusedMonth

                if (this.isSyncingFocus) {
                    return
                }

                this.clampFocusedDay()
                this.setupDaysGrid()
            })

            this.$watch('focusedYear', () => {
                if (`${this.focusedYear}`.length > 4) {
                    this.focusedYear = +`${this.focusedYear}`.substring(0, 4)
                }

                if (`${this.focusedYear}`.length !== 4) {
                    return
                }

                let year = +this.focusedYear

                if (!Number.isInteger(year)) {
                    year = this.yearInputMin
                }

                year = Math.min(
                    Math.max(year, this.yearInputMin),
                    this.yearInputMax,
                )

                if (year !== +this.focusedYear) {
                    this.focusedYear = year
                }

                if (this.isSyncingFocus) {
                    return
                }

                this.clampFocusedDay()
                this.setupDaysGrid()
            })

            const watchTimePart = (part, max) => {
                this.$watch(part, () => {
                    let value = +this[part]

                    if (!Number.isInteger(value) || value > max) {
                        this[part] = 0
                    } else if (value < 0) {
                        this[part] = max
                    } else {
                        this[part] = value
                    }

                    if (this.isClearingState) {
                        return
                    }

                    this.setState(this.getSelected()?.rjd ?? this.focusedRjd())
                })
            }

            watchTimePart('hour', 23)
            watchTimePart('minute', 59)
            watchTimePart('second', 59)

            this.$watch('state', () => {
                if (this.state === undefined) {
                    return
                }

                const selected = this.getSelected()

                if (selected === null) {
                    if (
                        blankState(this.state) &&
                        (this.hour || this.minute || this.second)
                    ) {
                        this.isClearingState = true
                        this.hour = 0
                        this.minute = 0
                        this.second = 0
                        this.$nextTick(() => (this.isClearingState = false))
                    }

                    this.setDisplayText()

                    return
                }

                if (this.hour !== selected.hour) {
                    this.hour = selected.hour
                }

                if (this.minute !== selected.minute) {
                    this.minute = selected.minute
                }

                if (this.second !== selected.second) {
                    this.second = selected.second
                }

                this.setDisplayText()
            })
        },

        getDayLabels() {
            // 2023-01-01 was a Sunday, giving a stable weekday reference.
            const sundayRjd = rjdFromGregorian(2023, 1, 1)

            const labels = Array.from({ length: 7 }, (_, index) =>
                intlParts({ weekday: 'short' }, sundayRjd + index),
            )

            return Array.from(
                { length: 7 },
                (_, index) => labels[(firstDayOfWeek + index) % 7],
            )
        },

        focusedRjd() {
            return this.rjdFromDisplayParts(
                this.focusedYear,
                this.focusedMonth,
                this.focusedDay,
            )
        },

        rjdFromDisplayParts(year, month, day) {
            if (displayCalendar === 'hijri') {
                return (
                    engine.toRjd(year, month, day) ??
                    Math.min(Math.max(this.todayRjd(), engine.minRjd), engine.maxRjd)
                )
            }

            return Math.min(
                Math.max(rjdFromGregorian(year, month, day), engine.minRjd),
                engine.maxRjd,
            )
        },

        displayPartsFromRjd(rjd) {
            return displayCalendar === 'hijri'
                ? engine.toHijri(rjd)
                : gregorianFromRjd(rjd)
        },

        displayMonthLength(year, month) {
            if (displayCalendar === 'hijri') {
                return engine.monthLength(year, month) ?? 30
            }

            return new Date(Date.UTC(year, month, 0)).getUTCDate()
        },

        setFocusedRjd(rjd) {
            rjd = Math.min(Math.max(rjd, engine.minRjd), engine.maxRjd)

            const parts = this.displayPartsFromRjd(rjd)

            this.isSyncingFocus = true

            this.focusedYear = parts.year
            this.focusedMonth = parts.month
            this.focusedDay = parts.day

            this.$nextTick(() => (this.isSyncingFocus = false))

            this.setupDaysGrid()
        },

        clampFocusedDay() {
            const length = this.displayMonthLength(
                +this.focusedYear,
                +this.focusedMonth,
            )

            if (this.focusedDay > length) {
                this.focusedDay = length
            }
        },

        setupDaysGrid() {
            const length = this.displayMonthLength(
                +this.focusedYear,
                +this.focusedMonth,
            )

            const firstRjd = this.rjdFromDisplayParts(
                +this.focusedYear,
                +this.focusedMonth,
                1,
            )

            this.emptyDaysInFocusedMonth = Array.from(
                { length: (weekdayOfRjd(firstRjd) - firstDayOfWeek + 7) % 7 },
                (_, index) => index + 1,
            )

            this.daysInFocusedMonth = Array.from(
                { length },
                (_, index) => index + 1,
            )

            this.setFocusedDualText()
        },

        todayRjd() {
            const now = new Date()

            return rjdFromGregorian(
                now.getFullYear(),
                now.getMonth() + 1,
                now.getDate(),
            )
        },

        getDefaultFocusedRjd() {
            const parts = parseDateParts(defaultFocusedDate ?? '')

            return parts ? rjdFromGregorian(parts.year, parts.month, parts.day) : null
        },

        getSelected() {
            if (blankState(this.state)) {
                return null
            }

            const parts = parseDateParts(this.state)

            if (!parts) {
                return null
            }

            let rjd

            if (storageCalendar === 'hijri') {
                if (
                    parts.month < 1 ||
                    parts.month > 12 ||
                    parts.day < 1 ||
                    parts.day > (engine.monthLength(parts.year, parts.month) ?? 0)
                ) {
                    return null
                }

                rjd = engine.toRjd(parts.year, parts.month, parts.day)

                if (rjd === null) {
                    return null
                }
            } else {
                rjd = rjdFromGregorian(parts.year, parts.month, parts.day)
            }

            return {
                rjd,
                hour: parts.hour,
                minute: parts.minute,
                second: parts.second,
            }
        },

        setState(rjd) {
            if (rjd === null) {
                this.state = null
                this.setDisplayText()

                return
            }

            if (this.dateIsDisabledRjd(rjd)) {
                return
            }

            let parts

            if (storageCalendar === 'hijri') {
                parts = engine.toHijri(rjd)

                if (parts === null) {
                    return
                }
            } else {
                parts = gregorianFromRjd(rjd)
            }

            this.state = `${`${parts.year}`.padStart(4, '0')}-${pad(parts.month)}-${pad(parts.day)} ${pad(this.hour ?? 0)}:${pad(this.minute ?? 0)}:${pad(this.second ?? 0)}`

            this.setDisplayText()
        },

        clearState() {
            this.isClearingState = true

            this.state = null
            this.hour = 0
            this.minute = 0
            this.second = 0

            this.setDisplayText()

            this.$nextTick(() => (this.isClearingState = false))
        },

        dateIsDisabledRjd(rjd) {
            if (rjd < engine.minRjd || rjd > engine.maxRjd) {
                return true
            }

            const boundRjd = (ref) => {
                const parts = parseDateParts(this.$refs?.[ref]?.value ?? '')

                return parts
                    ? rjdFromGregorian(parts.year, parts.month, parts.day)
                    : null
            }

            const minRjd = boundRjd('minDate')

            if (minRjd !== null && rjd < minRjd) {
                return true
            }

            const maxRjd = boundRjd('maxDate')

            if (maxRjd !== null && rjd > maxRjd) {
                return true
            }

            let disabledDates = []

            try {
                disabledDates = JSON.parse(this.$refs?.disabledDates?.value ?? '[]')
            } catch {
                disabledDates = []
            }

            return disabledDates.some((date) => {
                const parts = parseDateParts(`${date}`)

                return (
                    parts &&
                    rjdFromGregorian(parts.year, parts.month, parts.day) === rjd
                )
            })
        },

        dayIsDisabled(day) {
            return this.dateIsDisabledRjd(
                this.rjdFromDisplayParts(
                    +this.focusedYear,
                    +this.focusedMonth,
                    day,
                ),
            )
        },

        dayIsSelected(day) {
            const selected = this.getSelected()

            return (
                selected !== null &&
                selected.rjd ===
                    this.rjdFromDisplayParts(
                        +this.focusedYear,
                        +this.focusedMonth,
                        day,
                    )
            )
        },

        dayIsToday(day) {
            return (
                this.todayRjd() ===
                this.rjdFromDisplayParts(+this.focusedYear, +this.focusedMonth, day)
            )
        },

        focusPreviousDay() {
            this.setFocusedRjd(this.focusedRjd() - 1)
        },

        focusNextDay() {
            this.setFocusedRjd(this.focusedRjd() + 1)
        },

        focusPreviousWeek() {
            this.setFocusedRjd(this.focusedRjd() - 7)
        },

        focusNextWeek() {
            this.setFocusedRjd(this.focusedRjd() + 7)
        },

        setFocusedDay(day) {
            this.focusedDay = day

            this.setFocusedDualText()
        },

        selectDate(day = null) {
            if (day) {
                this.setFocusedDay(day)
            }

            this.setState(this.focusedRjd())

            if (shouldCloseOnDateSelection) {
                this.togglePanelVisibility()
            }
        },

        selectToday() {
            this.setFocusedRjd(this.todayRjd())

            this.selectDate()
        },

        formatGridDay(day) {
            return localizeDigits(day)
        },

        formatDate(rjd, calendar, pattern) {
            let parts

            if (calendar === 'hijri') {
                parts = engine.toHijri(rjd)

                if (parts === null) {
                    calendar = 'gregorian'
                    parts = gregorianFromRjd(rjd)
                }
            } else {
                parts = gregorianFromRjd(rjd)
            }

            const monthName = (style) =>
                calendar === 'hijri'
                    ? hijriMonthNames[parts.month - 1]
                    : gregorianMonthName(parts.month, style)

            const hour12 = this.hour % 12 || 12

            let out = ''

            for (let index = 0; index < pattern.length; index++) {
                const char = pattern[index]

                if (char === '\\' && index + 1 < pattern.length) {
                    out += pattern[++index]

                    continue
                }

                switch (char) {
                    case 'Y':
                        out += `${parts.year}`.padStart(4, '0')
                        break
                    case 'y':
                        out += `${parts.year}`.padStart(4, '0').slice(-2)
                        break
                    case 'm':
                        out += pad(parts.month)
                        break
                    case 'n':
                        out += `${parts.month}`
                        break
                    case 'd':
                        out += pad(parts.day)
                        break
                    case 'j':
                        out += `${parts.day}`
                        break
                    case 'F':
                        out += monthName('long')
                        break
                    case 'M':
                        out += monthName('short')
                        break
                    case 'l':
                        out += intlParts({ weekday: 'long' }, rjd)
                        break
                    case 'D':
                        out += intlParts({ weekday: 'short' }, rjd)
                        break
                    case 'H':
                        out += pad(this.hour ?? 0)
                        break
                    case 'G':
                        out += `${this.hour ?? 0}`
                        break
                    case 'h':
                        out += pad(hour12)
                        break
                    case 'g':
                        out += `${hour12}`
                        break
                    case 'i':
                        out += pad(this.minute ?? 0)
                        break
                    case 's':
                        out += pad(this.second ?? 0)
                        break
                    case 'A':
                        out += (this.hour ?? 0) < 12 ? 'AM' : 'PM'
                        break
                    case 'a':
                        out += (this.hour ?? 0) < 12 ? 'am' : 'pm'
                        break
                    default:
                        out += char
                }
            }

            return localizeDigits(out)
        },

        equivalentDateText(rjd, calendar) {
            if (calendar === 'hijri') {
                const parts = engine.toHijri(rjd)

                if (parts === null) {
                    return ''
                }

                return localizeDigits(
                    `${parts.day} ${hijriMonthNames[parts.month - 1]} ${parts.year}هـ`,
                )
            }

            return intlParts(
                { day: 'numeric', month: 'long', year: 'numeric' },
                rjd,
            )
        },

        setDisplayText() {
            const selected = this.getSelected()

            if (selected === null) {
                this.displayText = ''

                return
            }

            let text = this.formatDate(selected.rjd, displayCalendar, displayFormat)

            if (shouldShowEquivalentDate) {
                const equivalent = this.equivalentDateText(
                    selected.rjd,
                    displayCalendar === 'hijri' ? 'gregorian' : 'hijri',
                )

                if (equivalent) {
                    text += ` (${equivalent})`
                }
            }

            this.displayText = text
        },

        setFocusedDualText() {
            const rjd = this.focusedRjd()

            const primary = this.equivalentDateText(rjd, displayCalendar)
            const secondary = this.equivalentDateText(
                rjd,
                displayCalendar === 'hijri' ? 'gregorian' : 'hijri',
            )

            this.focusedDualText =
                primary && secondary ? `${primary} = ${secondary}` : ''
        },

        togglePanelVisibility() {
            if (!this.isOpen()) {
                const selected = this.getSelected()

                if (selected !== null) {
                    this.setFocusedRjd(selected.rjd)
                } else {
                    this.setupDaysGrid()
                }
            }

            this.$refs.panel.toggle(this.$refs.button)
        },

        isOpen() {
            return this.$refs.panel?.style.display === 'block'
        },
    }
}

function blankState(state) {
    return state === null || state === undefined || state === ''
}
