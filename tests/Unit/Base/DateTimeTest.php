<?php

use PrettyPhp\Base\DateTime;
use PrettyPhp\Base\Str;

describe('DateTime', function (): void {
    it('can be constructed with null (now)', function (): void {
        $dt = new DateTime();
        expect($dt->get())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('can be constructed from string', function (): void {
        $dt = new DateTime('2024-01-15 12:30:00');
        expect($dt->year())->toBe(2024);
        expect($dt->month())->toBe(1);
        expect($dt->day())->toBe(15);
    });

    it('can be constructed from timestamp', function (): void {
        $timestamp = 1705324200; // 2024-01-15 12:30:00 UTC
        $dt = new DateTime($timestamp);
        expect($dt->timestamp())->toBe($timestamp);
    });

    it('can be constructed with timezone', function (): void {
        $dt = new DateTime('2024-01-15 12:30:00', 'America/New_York');
        expect($dt->timezoneName()->get())->toBe('America/New_York');
    });

    it('can use datetime() helper', function (): void {
        $dt = datetime('2024-01-15 12:30:00');
        expect($dt)->toBeInstanceOf(DateTime::class);
        expect($dt->year())->toBe(2024);
    });

    it('implements Stringable', function (): void {
        $dt = new DateTime('2024-01-15 12:30:45');
        expect((string) $dt)->toBe('2024-01-15 12:30:45');
    });

    // ==================== Static Constructors ====================

    describe('static constructors', function (): void {
        it('can create from immutable', function (): void {
            $immutable = new \DateTimeImmutable('2024-01-15 12:30:00');
            $dt = DateTime::fromImmutable($immutable);
            expect($dt->year())->toBe(2024);
        });

        it('can create from mutable', function (): void {
            $mutable = new \DateTime('2024-01-15 12:30:00');
            $dt = DateTime::fromMutable($mutable);
            expect($dt->year())->toBe(2024);
        });

        it('can create from timestamp', function (): void {
            $dt = DateTime::fromTimestamp(1705324200);
            expect($dt->timestamp())->toBe(1705324200);
        });

        it('can create from format', function (): void {
            $dt = DateTime::fromFormat('d/m/Y H:i', '15/01/2024 12:30');
            expect($dt->year())->toBe(2024);
            expect($dt->month())->toBe(1);
            expect($dt->day())->toBe(15);
        });

        it('throws exception for invalid format', function (): void {
            DateTime::fromFormat('Y-m-d', 'invalid');
        })->throws(\InvalidArgumentException::class);

        it('can parse datetime string', function (): void {
            $dt = DateTime::parse('2024-01-15 12:30:00');
            expect($dt->year())->toBe(2024);
        });

        it('can create now', function (): void {
            $dt = DateTime::now();
            expect($dt->get())->toBeInstanceOf(\DateTimeImmutable::class);
        });

        it('can create today', function (): void {
            $dt = DateTime::today();
            expect($dt->hour())->toBe(0);
            expect($dt->minute())->toBe(0);
            expect($dt->second())->toBe(0);
        });

        it('can create tomorrow', function (): void {
            $today = DateTime::today();
            $tomorrow = DateTime::tomorrow();
            expect($tomorrow->diffInDays($today))->toBe(1);
        });

        it('can create yesterday', function (): void {
            $today = DateTime::today();
            $yesterday = DateTime::yesterday();
            expect($today->diffInDays($yesterday))->toBe(1);
        });
    });

    // ==================== Formatting ====================

    describe('formatting', function (): void {
        it('can format with custom format', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            $result = $dt->format('Y/m/d');
            expect($result)->toBeInstanceOf(Str::class);
            expect($result->get())->toBe('2024/01/15');
        });

        it('can format as ISO 8601', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'UTC');
            $result = $dt->toIso8601();
            expect($result->get())->toContain('2024-01-15T12:30:00');
        });

        it('can format as RFC 2822', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'UTC');
            $result = $dt->toRfc2822();
            expect($result->get())->toContain('15 Jan 2024');
        });

        it('can format as RFC 3339', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'UTC');
            $result = $dt->toRfc3339();
            expect($result->get())->toContain('2024-01-15T12:30:00');
        });

        it('can format as date string', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->toDateString()->get())->toBe('2024-01-15');
        });

        it('can format as time string', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->toTimeString()->get())->toBe('12:30:45');
        });

        it('can format as datetime string', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->toDateTimeString()->get())->toBe('2024-01-15 12:30:45');
        });

        it('can get timestamp', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'UTC');
            expect($dt->timestamp())->toBeInt();
        });
    });

    // ==================== Comparison ====================

    describe('comparison', function (): void {
        it('can check equality with DateTime', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:00');
            expect($dt1->equals($dt2))->toBeTrue();
        });

        it('can check equality with string', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            expect($dt->equals('2024-01-15 12:30:00'))->toBeTrue();
        });

        it('can check if before', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-16 12:30:00');
            expect($dt1->isBefore($dt2))->toBeTrue();
            expect($dt2->isBefore($dt1))->toBeFalse();
        });

        it('can check if after', function (): void {
            $dt1 = new DateTime('2024-01-16 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:00');
            expect($dt1->isAfter($dt2))->toBeTrue();
            expect($dt2->isAfter($dt1))->toBeFalse();
        });

        it('can check if before or equal', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:00');
            $dt3 = new DateTime('2024-01-16 12:30:00');
            expect($dt1->isBeforeOrEqual($dt2))->toBeTrue();
            expect($dt1->isBeforeOrEqual($dt3))->toBeTrue();
        });

        it('can check if after or equal', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:00');
            $dt3 = new DateTime('2024-01-14 12:30:00');
            expect($dt1->isAfterOrEqual($dt2))->toBeTrue();
            expect($dt1->isAfterOrEqual($dt3))->toBeTrue();
        });

        it('can check if between (inclusive)', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $start = new DateTime('2024-01-10 00:00:00');
            $end = new DateTime('2024-01-20 00:00:00');
            expect($dt->isBetween($start, $end))->toBeTrue();
        });

        it('can check if between (exclusive)', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $start = new DateTime('2024-01-15 12:30:00');
            $end = new DateTime('2024-01-20 00:00:00');
            expect($dt->isBetween($start, $end, false))->toBeFalse();
        });

        it('can check if is weekend', function (): void {
            $saturday = new DateTime('2024-01-13 12:00:00'); // Saturday
            $sunday = new DateTime('2024-01-14 12:00:00');   // Sunday
            $monday = new DateTime('2024-01-15 12:00:00');   // Monday

            expect($saturday->isWeekend())->toBeTrue();
            expect($sunday->isWeekend())->toBeTrue();
            expect($monday->isWeekend())->toBeFalse();
        });

        it('can check if is weekday', function (): void {
            $saturday = new DateTime('2024-01-13 12:00:00'); // Saturday
            $monday = new DateTime('2024-01-15 12:00:00');   // Monday

            expect($saturday->isWeekday())->toBeFalse();
            expect($monday->isWeekday())->toBeTrue();
        });
    });

    // ==================== Modification ====================

    describe('modification', function (): void {
        it('can add years', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addYears(2);
            expect($result->year())->toBe(2026);
        });

        it('can add months', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addMonths(2);
            expect($result->month())->toBe(3);
        });

        it('can add days', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addDays(5);
            expect($result->day())->toBe(20);
        });

        it('can add hours', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addHours(3);
            expect($result->hour())->toBe(15);
        });

        it('can add minutes', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addMinutes(45);
            expect($result->minute())->toBe(15);
        });

        it('can add seconds', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addSeconds(90);
            expect($result->second())->toBe(30);
            expect($result->minute())->toBe(31);
        });

        it('can subtract years', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->subYears(2);
            expect($result->year())->toBe(2022);
        });

        it('can subtract months', function (): void {
            $dt = new DateTime('2024-03-15 12:30:00');
            $result = $dt->subMonths(2);
            expect($result->month())->toBe(1);
        });

        it('can subtract days', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->subDays(5);
            expect($result->day())->toBe(10);
        });

        it('can subtract hours', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->subHours(3);
            expect($result->hour())->toBe(9);
        });

        it('can subtract minutes', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->subMinutes(45);
            expect($result->minute())->toBe(45);
            expect($result->hour())->toBe(11);
        });

        it('can subtract seconds', function (): void {
            $dt = new DateTime('2024-01-15 12:30:30');
            $result = $dt->subSeconds(90);
            expect($result->second())->toBe(0);
            expect($result->minute())->toBe(29);
        });

        it('can add interval', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $interval = new \DateInterval('P1D');
            $result = $dt->add($interval);
            expect($result->day())->toBe(16);
        });

        it('can subtract interval', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $interval = new \DateInterval('P1D');
            $result = $dt->sub($interval);
            expect($result->day())->toBe(14);
        });

        it('can modify with relative formats', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->modify('next monday');
            expect($result->dayOfWeek())->toBe(1); // Monday
        });

        it('throws exception for invalid modifier', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $dt->modify('this is not a valid modifier string at all');
        })->throws(\InvalidArgumentException::class);

        it('is immutable', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->addDays(5);
            expect($dt->day())->toBe(15);
            expect($result->day())->toBe(20);
        });
    });

    // ==================== Set Components ====================

    describe('set components', function (): void {
        it('can set year', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->setYear(2025);
            expect($result->year())->toBe(2025);
            expect($result->month())->toBe(1);
        });

        it('can set month', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->setMonth(6);
            expect($result->month())->toBe(6);
        });

        it('can set day', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->setDay(20);
            expect($result->day())->toBe(20);
        });

        it('can set time', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->setTime(15, 45, 30);
            expect($result->hour())->toBe(15);
            expect($result->minute())->toBe(45);
            expect($result->second())->toBe(30);
        });

        it('can set date', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00');
            $result = $dt->setDate(2025, 6, 20);
            expect($result->year())->toBe(2025);
            expect($result->month())->toBe(6);
            expect($result->day())->toBe(20);
        });

        it('can set to start of day', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            $result = $dt->startOfDay();
            expect($result->hour())->toBe(0);
            expect($result->minute())->toBe(0);
            expect($result->second())->toBe(0);
        });

        it('can set to end of day', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            $result = $dt->endOfDay();
            expect($result->hour())->toBe(23);
            expect($result->minute())->toBe(59);
            expect($result->second())->toBe(59);
        });

        it('can set to start of month', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            $result = $dt->startOfMonth();
            expect($result->day())->toBe(1);
            expect($result->hour())->toBe(0);
        });

        it('can set to end of month', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            $result = $dt->endOfMonth();
            expect($result->day())->toBe(31);
            expect($result->hour())->toBe(23);
        });

        it('can set to start of year', function (): void {
            $dt = new DateTime('2024-06-15 12:30:45');
            $result = $dt->startOfYear();
            expect($result->month())->toBe(1);
            expect($result->day())->toBe(1);
            expect($result->hour())->toBe(0);
        });

        it('can set to end of year', function (): void {
            $dt = new DateTime('2024-06-15 12:30:45');
            $result = $dt->endOfYear();
            expect($result->month())->toBe(12);
            expect($result->day())->toBe(31);
            expect($result->hour())->toBe(23);
        });

        it('can set to start of week', function (): void {
            $dt = new DateTime('2024-01-17 12:30:45'); // Wednesday
            $result = $dt->startOfWeek();
            expect($result->dayOfWeek())->toBe(1); // Monday
            expect($result->hour())->toBe(0);
        });

        it('can set to end of week', function (): void {
            $dt = new DateTime('2024-01-17 12:30:45'); // Wednesday
            $result = $dt->endOfWeek();
            expect($result->dayOfWeek())->toBe(7); // Sunday
            expect($result->hour())->toBe(23);
        });
    });

    // ==================== Timezone ====================

    describe('timezone', function (): void {
        it('can get timezone', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'America/New_York');
            $tz = $dt->timezone();
            expect($tz)->toBeInstanceOf(\DateTimeZone::class);
        });

        it('can get timezone name', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'America/New_York');
            expect($dt->timezoneName()->get())->toBe('America/New_York');
        });

        it('can convert to timezone', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'UTC');
            $result = $dt->toTimezone('America/New_York');
            expect($result->timezoneName()->get())->toBe('America/New_York');
        });

        it('can convert to UTC', function (): void {
            $dt = new DateTime('2024-01-15 12:30:00', 'America/New_York');
            $result = $dt->toUtc();
            expect($result->timezoneName()->get())->toBe('UTC');
        });
    });

    // ==================== Difference ====================

    describe('difference', function (): void {
        it('can get diff', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-20 12:30:00');
            $diff = $dt1->diff($dt2);
            expect($diff)->toBeInstanceOf(\DateInterval::class);
            expect($diff->d)->toBe(5);
        });

        it('can get diff in years', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2026-01-15 12:30:00');
            expect($dt1->diffInYears($dt2))->toBe(2);
        });

        it('can get diff in months', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-04-15 12:30:00');
            expect($dt1->diffInMonths($dt2))->toBe(3);
        });

        it('can get diff in days', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-20 12:30:00');
            expect($dt1->diffInDays($dt2))->toBe(5);
        });

        it('can get diff in hours', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 15:30:00');
            expect($dt1->diffInHours($dt2))->toBe(3);
        });

        it('can get diff in minutes', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:45:00');
            expect($dt1->diffInMinutes($dt2))->toBe(15);
        });

        it('can get diff in seconds', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:30');
            expect($dt1->diffInSeconds($dt2))->toBe(30);
        });

        it('respects absolute flag', function (): void {
            $dt1 = new DateTime('2024-01-20 12:30:00');
            $dt2 = new DateTime('2024-01-15 12:30:00');
            expect($dt1->diffInDays($dt2, true))->toBe(5);
            expect($dt1->diffInDays($dt2, false))->toBe(5); // dt1 is after dt2, so positive
            expect($dt2->diffInDays($dt1, false))->toBe(-5); // dt2 is before dt1, so negative
        });
    });

    // ==================== Relative Time ====================

    describe('relative time', function (): void {
        it('can get time ago for seconds', function (): void {
            $dt = new DateTime('-5 seconds');
            $result = $dt->ago();
            expect($result->get())->toBe('5 seconds ago');
        });

        it('can get time ago for minutes', function (): void {
            $dt = new DateTime('-5 minutes');
            $result = $dt->ago();
            expect($result->get())->toBe('5 minutes ago');
        });

        it('can get time ago for hours', function (): void {
            $dt = new DateTime('-3 hours');
            $result = $dt->ago();
            expect($result->get())->toBe('3 hours ago');
        });

        it('can get time ago for days', function (): void {
            $dt = new DateTime('-3 days');
            $result = $dt->ago();
            expect($result->get())->toBe('3 days ago');
        });

        it('can get time ago for weeks', function (): void {
            $dt = new DateTime('-14 days');
            $result = $dt->ago();
            expect($result->get())->toBe('2 weeks ago');
        });

        it('can get time ago for months', function (): void {
            $dt = new DateTime('-2 months');
            $result = $dt->ago();
            expect($result->get())->toBe('2 months ago');
        });

        it('can get time ago for years', function (): void {
            $dt = new DateTime('-2 years');
            $result = $dt->ago();
            expect($result->get())->toBe('2 years ago');
        });

        it('can get time until for seconds', function (): void {
            $now = new \DateTimeImmutable();
            $dt = DateTime::fromImmutable($now->modify('+10 seconds'));
            $result = $dt->until();
            expect($result->get())->toContain('second');
        });

        it('can get time until for minutes', function (): void {
            $now = new \DateTimeImmutable();
            $dt = DateTime::fromImmutable($now->modify('+5 minutes'));
            $result = $dt->until();
            expect($result->get())->toContain('minute');
        });

        it('can get time until for hours', function (): void {
            $now = new \DateTimeImmutable();
            $dt = DateTime::fromImmutable($now->modify('+3 hours'));
            $result = $dt->until();
            expect($result->get())->toContain('hour');
        });

        it('can get time until for days', function (): void {
            $now = new \DateTimeImmutable();
            $dt = DateTime::fromImmutable($now->modify('+3 days'));
            $result = $dt->until();
            expect($result->get())->toContain('day');
        });

        it('can get diff for humans (past)', function (): void {
            $dt = new DateTime('-3 hours');
            $result = $dt->diffForHumans();
            expect($result->get())->toBe('3 hours ago');
        });

        it('can get diff for humans (future)', function (): void {
            $now = new \DateTimeImmutable();
            $dt = DateTime::fromImmutable($now->modify('+3 hours'));
            $result = $dt->diffForHumans();
            expect($result->get())->toContain('hour');
        });
    });

    // ==================== Getters ====================

    describe('getters', function (): void {
        it('can get year', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->year())->toBe(2024);
        });

        it('can get month', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->month())->toBe(1);
        });

        it('can get day', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->day())->toBe(15);
        });

        it('can get hour', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->hour())->toBe(12);
        });

        it('can get minute', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->minute())->toBe(30);
        });

        it('can get second', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->second())->toBe(45);
        });

        it('can get day of week', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45'); // Monday
            expect($dt->dayOfWeek())->toBe(1);
        });

        it('can get day of year', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->dayOfYear())->toBe(14); // 15th day - 1 (0-indexed)
        });

        it('can get week of year', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->weekOfYear())->toBeInt();
        });

        it('can get days in month', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45');
            expect($dt->daysInMonth())->toBe(31);
        });

        it('can get quarter', function (): void {
            $dt1 = new DateTime('2024-01-15 12:30:45');
            $dt2 = new DateTime('2024-04-15 12:30:45');
            $dt3 = new DateTime('2024-07-15 12:30:45');
            $dt4 = new DateTime('2024-10-15 12:30:45');

            expect($dt1->quarter())->toBe(1);
            expect($dt2->quarter())->toBe(2);
            expect($dt3->quarter())->toBe(3);
            expect($dt4->quarter())->toBe(4);
        });
    });

    // ==================== Utility ====================

    describe('utility', function (): void {
        it('can convert to array', function (): void {
            $dt = new DateTime('2024-01-15 12:30:45', 'UTC');
            $arr = $dt->toArray();

            expect($arr)->toBeArray();
            expect($arr['year'])->toBe(2024);
            expect($arr['month'])->toBe(1);
            expect($arr['day'])->toBe(15);
            expect($arr['hour'])->toBe(12);
            expect($arr['minute'])->toBe(30);
            expect($arr['second'])->toBe(45);
            expect($arr['timezone'])->toBe('UTC');
        });
    });
});
