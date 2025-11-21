# Date Module

Comprehensive date/time module covering all 48 PHP date functions with elegant object-oriented API.

## Classes

- **DateTime** - Immutable date/time with fluent API
- **DateInterval** - Time intervals with formatting
- **Timezone** - Timezone handling and information
- **Date** - Static utility class for all PHP date functions

## Quick Start

```php
use function PrettyPhp\{datetime, interval, timezone};
use PrettyPhp\Base\Date;

// DateTime operations
$dt = datetime('2024-01-15')
    ->addDays(7)
    ->setTime(14, 30, 0)
    ->format('Y-m-d H:i:s');

echo $dt; // "2024-01-22 14:30:00"

// Relative time
echo datetime('2024-01-01')->ago(); // "10 months ago"
echo datetime('2025-12-31')->until(); // "in 1 year"

// Intervals
$interval = interval('P1Y2M3D')->toHumanReadable();
echo $interval; // "1 year, 2 months, 3 days"

// Timezones
$tz = timezone('America/New_York');
echo $tz->offsetString(); // "-05:00"

// Static utilities
$timestamp = Date::strtotime('next Monday');
$valid = Date::checkdate(2, 29, 2024); // true (leap year)
```

## DateTime Class

### Creation

```php
// From various sources
$dt = datetime('2024-01-15 12:30:00');
$dt = datetime(1705324200); // from timestamp
$dt = datetime(null, 'America/New_York'); // now with timezone

// Static constructors
$dt = DateTime::now();
$dt = DateTime::today();
$dt = DateTime::yesterday();
$dt = DateTime::tomorrow();
$dt = DateTime::fromFormat('d/m/Y', '15/01/2024');
$dt = DateTime::fromTimestamp(1705324200);
```

### Modification

```php
$dt = datetime('2024-01-15')
    ->addYears(1)
    ->addMonths(2)
    ->addDays(3)
    ->addHours(4)
    ->addMinutes(5)
    ->addSeconds(6);

// Start/end of periods
$dt->startOfDay();     // 00:00:00
$dt->endOfDay();       // 23:59:59
$dt->startOfWeek();    // Monday 00:00:00
$dt->endOfWeek();      // Sunday 23:59:59
$dt->startOfMonth();
$dt->endOfMonth();
$dt->startOfYear();
$dt->endOfYear();
```

### Comparison

```php
$dt1 = datetime('2024-01-15');
$dt2 = datetime('2024-01-20');

$dt1->isBefore($dt2);           // true
$dt1->isAfter($dt2);            // false
$dt1->equals($dt2);             // false
$dt1->isBetween($dt1, $dt2);    // true

$dt->isPast();                  // bool
$dt->isFuture();                // bool
$dt->isToday();                 // bool
$dt->isYesterday();             // bool
$dt->isTomorrow();              // bool
$dt->isWeekend();               // bool
$dt->isWeekday();               // bool
$dt->isLeapYear();              // bool
```

### Difference

```php
$dt1 = datetime('2024-01-15');
$dt2 = datetime('2024-04-20');

$diff = $dt1->diff($dt2);           // DateInterval
$years = $dt1->diffInYears($dt2);   // 0
$months = $dt1->diffInMonths($dt2); // 3
$days = $dt1->diffInDays($dt2);     // 96
$hours = $dt1->diffInHours($dt2);
$minutes = $dt1->diffInMinutes($dt2);
$seconds = $dt1->diffInSeconds($dt2);
```

### Formatting

```php
$dt = datetime('2024-01-15 14:30:00');

$dt->format('Y-m-d H:i:s');        // Custom format
$dt->toIso8601();                  // 2024-01-15T14:30:00+00:00
$dt->toRfc2822();                  // Mon, 15 Jan 2024 14:30:00 +0000
$dt->toRfc3339();                  // 2024-01-15T14:30:00+00:00
$dt->toDateString();               // 2024-01-15
$dt->toTimeString();               // 14:30:00
$dt->toDateTimeString();           // 2024-01-15 14:30:00
$dt->timestamp();                  // 1705324200
```

### Timezone

```php
$dt = datetime('2024-01-15', 'UTC');

$tz = $dt->tz();                      // Timezone object
$name = $dt->timezoneName();          // "UTC"
$offset = $dt->timezoneOffset();      // 0

$nyTime = $dt->toTimezone('America/New_York');
$utcTime = $dt->toUtc();
```

### Sun Information

```php
$dt = datetime('2024-06-21');
$lat = 40.7128;  // New York latitude
$lon = -74.0060; // New York longitude

$sunrise = $dt->sunrise($lat, $lon);  // DateTime
$sunset = $dt->sunset($lat, $lon);    // DateTime
$sunInfo = $dt->sunInfo($lat, $lon);  // array with all sun times
```

### ISO Dates

```php
// Set date by ISO week
$dt = datetime('2024-01-01')->setISODate(2024, 15, 3); // Week 15, Wednesday
```

### Getters

```php
$dt = datetime('2024-01-15 14:30:45');

$dt->year();         // 2024
$dt->month();        // 1
$dt->day();          // 15
$dt->hour();         // 14
$dt->minute();       // 30
$dt->second();       // 45
$dt->microsecond();  // 0
$dt->dayOfWeek();    // 1 (Monday)
$dt->dayOfYear();    // 15
$dt->weekOfYear();   // 3
$dt->daysInMonth();  // 31
$dt->quarter();      // 1
```

## DateInterval Class

### Creation

```php
$interval = interval('P1Y2M3DT4H5M6S'); // ISO 8601
$interval = DateInterval::fromDateString('1 day');
$interval = DateInterval::create(
    years: 1,
    months: 2,
    days: 3,
    hours: 4,
    minutes: 5,
    seconds: 6
);
```

### Formatting

```php
$interval = interval('P1Y2M3D');

$interval->format('%y years, %m months'); // Custom format
$interval->toIso8601();                   // P1Y2M3D
$interval->toHumanReadable();             // "1 year, 2 months, 3 days"
```

### Getters

```php
$interval->years();       // 1
$interval->months();      // 2
$interval->days();        // 3
$interval->hours();       // 0
$interval->minutes();     // 0
$interval->seconds();     // 0
$interval->totalDays();   // Total days (if from diff)
$interval->isInverted();  // bool (negative interval)
```

### Conversion

```php
$interval = interval('P1DT2H30M');

$interval->toSeconds();   // ~95400 (approximate)
$interval->toMinutes();   // ~1590
$interval->toHours();     // ~26
$interval->toArray();     // Detailed array
```

## Timezone Class

### Creation

```php
$tz = timezone('America/New_York');
$tz = Timezone::utc();
$tz = Timezone::fromAbbreviation('EST');
```

### Information

```php
$tz = timezone('America/New_York');

$tz->name();              // "America/New_York"
$tz->offset();            // -18000 (seconds)
$tz->offsetHours();       // -5.0
$tz->offsetString();      // "-05:00"
$tz->location();          // Array with country, lat, lon
$tz->transitions();       // DST transitions
```

### Lists

```php
// Get all timezones
$all = Timezone::identifiers();          // All timezones
$africa = Timezone::africa();            // Africa timezones
$america = Timezone::america();          // America timezones
$europe = Timezone::europe();            // Europe timezones
$asia = Timezone::asia();                // Asia timezones
$us = Timezone::forCountry('US');        // US timezones

// Abbreviations
$abbrs = Timezone::abbreviations();      // All abbreviations
```

## Date Utility Class

Static methods wrapping all PHP date functions:

### Current Time

```php
Date::time();        // current timestamp
Date::now();         // DateTime now
Date::year();        // current year
Date::month();       // current month
Date::day();         // current day
Date::hour();        // current hour
Date::minute();      // current minute
Date::second();      // current second
```

### Validation

```php
Date::checkdate(2, 29, 2024);          // true
Date::isValid(2024, 2, 29);            // true
```

### Formatting

```php
Date::format('Y-m-d', $timestamp);     // date()
Date::gmdate('Y-m-d', $timestamp);     // gmdate()
Date::idate('Y', $timestamp);          // idate()
```

### Parsing

```php
$parsed = Date::parse('2024-01-15');           // date_parse()
$parsed = Date::parseFromFormat('d/m/Y', '15/01/2024');
$timestamp = Date::strtotime('next Monday');   // strtotime()
$errors = Date::getLastErrors();               // date_get_last_errors()
```

### Timestamp Creation

```php
$ts = Date::mktime(12, 30, 0, 1, 15, 2024);    // mktime()
$ts = Date::gmmktime(12, 30, 0, 1, 15, 2024);  // gmmktime()
```

### Date Information

```php
$info = Date::getdate($timestamp);      // getdate()
$local = Date::localtime($timestamp);   // localtime()
```

### Timezone Operations

```php
$tz = Date::getDefaultTimezone();       // date_default_timezone_get()
Date::setDefaultTimezone('UTC');        // date_default_timezone_set()
$version = Date::timezoneVersion();     // timezone_version_get()
```

### Sun Times

```php
$sunrise = Date::sunrise($timestamp, \SUNFUNCS_RET_TIMESTAMP, $lat, $lon);
$sunset = Date::sunset($timestamp, \SUNFUNCS_RET_TIMESTAMP, $lat, $lon);
$sunInfo = Date::sunInfo($timestamp, $lat, $lon);
```

### Factory Methods

```php
$dt = Date::create('2024-01-15');
$dt = Date::createFromFormat('d/m/Y', '15/01/2024');
$dt = Date::createFromTimestamp($timestamp);
$dt = Date::today();
$dt = Date::yesterday();
$dt = Date::tomorrow();
```

### Constants

```php
Date::ATOM      // \DateTimeInterface::ATOM
Date::RFC822    // \DateTimeInterface::RFC822
Date::RFC3339   // \DateTimeInterface::RFC3339
Date::W3C       // \DateTimeInterface::W3C
// ... and more
```

## PHP Functions Coverage

All 48 PHP date functions are covered:

| PHP Function | Implementation |
|-------------|----------------|
| `checkdate()` | `Date::checkdate()` |
| `date()` | `Date::format()` |
| `date_add()` | `DateTime::add()` |
| `date_create()` | `DateTime::__construct()`, `Date::create()` |
| `date_create_from_format()` | `DateTime::fromFormat()`, `Date::createFromFormat()` |
| `date_create_immutable()` | `DateTime::__construct()` |
| `date_create_immutable_from_format()` | `DateTime::fromFormat()` |
| `date_date_set()` | `DateTime::setDate()` |
| `date_default_timezone_get()` | `Date::getDefaultTimezone()` |
| `date_default_timezone_set()` | `Date::setDefaultTimezone()` |
| `date_diff()` | `DateTime::diff()` |
| `date_format()` | `DateTime::format()` |
| `date_get_last_errors()` | `Date::getLastErrors()` |
| `date_interval_create_from_date_string()` | `DateInterval::fromDateString()` |
| `date_interval_format()` | `DateInterval::format()` |
| `date_isodate_set()` | `DateTime::setISODate()` |
| `date_modify()` | `DateTime::modify()` |
| `date_offset_get()` | `DateTime::timezoneOffset()` |
| `date_parse()` | `Date::parse()` |
| `date_parse_from_format()` | `Date::parseFromFormat()` |
| `date_sub()` | `DateTime::sub()` |
| `date_sun_info()` | `DateTime::sunInfo()`, `Date::sunInfo()` |
| `date_sunrise()` | `DateTime::sunrise()`, `Date::sunrise()` |
| `date_sunset()` | `DateTime::sunset()`, `Date::sunset()` |
| `date_time_set()` | `DateTime::setTime()` |
| `date_timestamp_get()` | `DateTime::timestamp()` |
| `date_timestamp_set()` | `DateTime::fromTimestamp()` |
| `date_timezone_get()` | `DateTime::timezone()`, `DateTime::tz()` |
| `date_timezone_set()` | `DateTime::toTimezone()` |
| `getdate()` | `Date::getdate()` |
| `gmdate()` | `Date::gmdate()` |
| `gmmktime()` | `Date::gmmktime()` |
| `idate()` | `Date::idate()` |
| `localtime()` | `Date::localtime()` |
| `mktime()` | `Date::mktime()` |
| `strtotime()` | `Date::strtotime()` |
| `time()` | `Date::time()` |
| `timezone_abbreviations_list()` | `Timezone::abbreviations()` |
| `timezone_identifiers_list()` | `Timezone::identifiers()` |
| `timezone_location_get()` | `Timezone::location()` |
| `timezone_name_from_abbr()` | `Timezone::fromAbbreviation()` |
| `timezone_name_get()` | `Timezone::name()` |
| `timezone_offset_get()` | `Timezone::offset()` |
| `timezone_open()` | `Timezone::__construct()` |
| `timezone_transitions_get()` | `Timezone::transitions()` |
| `timezone_version_get()` | `Date::timezoneVersion()` |

## Advanced Examples

### Working with Business Days

```php
function addBusinessDays(DateTime $dt, int $days): DateTime {
    $added = 0;
    $current = $dt;

    while ($added < $days) {
        $current = $current->addDays(1);
        if ($current->isWeekday()) {
            $added++;
        }
    }

    return $current;
}

$delivery = addBusinessDays(datetime('2024-01-15'), 5);
```

### Date Ranges

```php
function dateRange(DateTime $start, DateTime $end): array {
    $dates = [];
    $current = $start;

    while ($current->isBeforeOrEqual($end)) {
        $dates[] = $current;
        $current = $current->addDays(1);
    }

    return $dates;
}

$dates = dateRange(datetime('2024-01-01'), datetime('2024-01-07'));
```

### Age Calculation

```php
function calculateAge(DateTime $birthDate): int {
    return $birthDate->diffInYears(datetime());
}

$age = calculateAge(datetime('1990-05-15')); // Age in years
```

### Timezone Conversion

```php
$utc = datetime('2024-01-15 12:00:00', 'UTC');
$tokyo = $utc->toTimezone('Asia/Tokyo');
$newYork = $utc->toTimezone('America/New_York');

echo "UTC:      {$utc}\n";
echo "Tokyo:    {$tokyo}\n";
echo "New York: {$newYork}\n";
```
