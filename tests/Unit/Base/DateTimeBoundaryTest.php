<?php

declare(strict_types=1);

use PrettyPhp\Base\DateInterval;
use PrettyPhp\Base\DateTime;
use PrettyPhp\Base\Timezone;

mutates(DateTime::class);

function relative(string $modifier): DateTime
{
    return DateTime::fromImmutable(new \DateTimeImmutable()->modify($modifier));
}

describe('DateTime construction', function (): void {
    it('keeps the given moment and converts timezone', function (): void {
        $immutable = new \DateTimeImmutable('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $dt = new DateTime($immutable, 'Asia/Tokyo');
        expect($dt->format('Y-m-d H:i e')->get())->toBe('2024-01-15 19:00 Asia/Tokyo');
        expect(new DateTime($immutable)->format('Y-m-d H:i e')->get())->toBe('2024-01-15 10:00 UTC');
    });

    it('interprets timestamps in UTC unless timezone is given', function (): void {
        expect(new DateTime(0)->format('Y-m-d H:i e')->get())->toBe('1970-01-01 00:00 UTC');
        expect(new DateTime(0, 'Asia/Tokyo')->format('Y-m-d H:i e')->get())->toBe('1970-01-01 09:00 Asia/Tokyo');
    });

    it('parses formats in the default timezone when none is given', function (): void {
        $dt = DateTime::fromFormat('Y-m-d H:i', '2024-01-15 10:00');
        expect($dt->timezoneName()->get())->toBe(date_default_timezone_get());
    });

    it('reports exact error messages', function (): void {
        expect(fn (): DateTime => DateTime::fromFormat('Y-m-d', 'nope'))
            ->toThrow(\InvalidArgumentException::class, 'Failed to parse datetime: nope');

        $error = null;
        try {
            (void) new DateTime('2024-01-15')->modify('bogus modifier');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $error = $invalidArgumentException;
        }

        expect($error?->getMessage())->toBe('Invalid modifier: bogus modifier');
        expect($error?->getCode())->toBe(0);
        expect($error?->getPrevious())->toBeInstanceOf(\DateMalformedStringException::class);
    });
});

describe('DateTime comparison boundaries', function (): void {
    it('treats equal moments as neither before nor after', function (): void {
        $a = new DateTime('2024-01-15 10:00:00', 'UTC');
        $b = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        expect($a->isBefore($b))->toBeFalse();
        expect($a->isAfter($b))->toBeFalse();
        expect($a->isBeforeOrEqual($b))->toBeTrue();
        expect($a->isAfterOrEqual($b))->toBeTrue();
    });

    it('includes or excludes range boundaries', function (): void {
        $start = new DateTime('2024-01-01', 'UTC');
        $end = new DateTime('2024-01-31', 'UTC');

        expect($start->isBetween($start, $end))->toBeTrue();
        expect($end->isBetween($start, $end))->toBeTrue();
        expect($start->isBetween($start, $end, false))->toBeFalse();
        expect($end->isBetween($start, $end, false))->toBeFalse();
        expect(new DateTime('2024-01-15', 'UTC')->isBetween($start, $end, false))->toBeTrue();
        expect(new DateTime('2024-02-15', 'UTC')->isBetween($start, $end))->toBeFalse();
        expect(new DateTime('2023-12-15', 'UTC')->isBetween($start, $end))->toBeFalse();
    });
});

describe('DateTime differences', function (): void {
    it('truncates differences towards zero and keeps the sign', function (): void {
        $base = new DateTime('2024-01-15 12:00:00', 'UTC');
        $earlier = '2024-01-14 00:00:00';

        expect($base->diffInDays($earlier))->toBe(1);
        expect($base->diffInDays($earlier, false))->toBe(1);
        expect(new DateTime($earlier, 'UTC')->diffInDays($base->format('Y-m-d H:i:s')->get(), false))->toBe(-1);
        expect($base->diffInHours('2024-01-15 10:30:00'))->toBe(1);
        expect(new DateTime('2024-01-15 10:30:00', 'UTC')->diffInHours('2024-01-15 12:00:00', false))->toBe(-1);
        expect($base->diffInMinutes('2024-01-15 11:58:30'))->toBe(1);
        expect(new DateTime('2024-01-15 11:58:30', 'UTC')->diffInMinutes('2024-01-15 12:00:00', false))->toBe(-1);
        expect($base->diffInSeconds('2024-01-15 12:00:05', false))->toBe(-5);

        // Just under two units must still count as one
        expect($base->diffInDays('2024-01-13 12:00:02'))->toBe(1);
        expect($base->diffInHours('2024-01-15 10:00:02'))->toBe(1);
        expect($base->diffInMinutes('2024-01-15 11:58:02'))->toBe(1);
    });

    it('counts months across years and supports signed differences', function (): void {
        $base = new DateTime('2024-03-15', 'UTC');
        expect($base->diffInMonths('2022-01-15'))->toBe(26);
        expect($base->diffInYears('2022-01-15'))->toBe(2);
        expect($base->diff('2024-03-20')->isInverted())->toBeFalse();
        expect(new DateTime('2024-03-20', 'UTC')->diff('2024-03-15')->isInverted())->toBeTrue();
        expect(new DateTime('2024-03-20', 'UTC')->diff('2024-03-15', true)->isInverted())->toBeFalse();
    });
});

describe('DateTime relative descriptions', function (): void {
    it('describes past moments', function (string $modifier, string $expected): void {
        expect(relative($modifier)->ago()->get())->toBe($expected);
    })->with([
        ['-1 year -1 hour', '1 year ago'],
        ['-2 years -1 hour', '2 years ago'],
        ['-1 month -1 hour', '1 month ago'],
        ['-2 months -1 hour', '2 months ago'],
        ['-1 day -1 hour', '1 day ago'],
        ['-2 days -1 hour', '2 days ago'],
        ['-6 days -1 hour', '6 days ago'],
        ['-7 days -1 hour', '1 week ago'],
        ['-13 days -1 hour', '1 week ago'],
        ['-14 days -1 hour', '2 weeks ago'],
        ['-1 hour -1 minute', '1 hour ago'],
        ['-2 hours -1 minute', '2 hours ago'],
        ['-1 minute -1 second', '1 minute ago'],
        ['-2 minutes -1 second', '2 minutes ago'],
        ['-1 second', '1 second ago'],
        ['-5 seconds', '5 seconds ago'],
        ['+0 seconds', 'just now'],
    ]);

    it('describes future moments', function (string $modifier, string $expected): void {
        expect(relative($modifier)->until()->get())->toBe($expected);
    })->with([
        ['+1 day +1 hour', 'in 1 day'],
        ['+2 days +1 hour', 'in 2 days'],
        ['+6 days +1 hour', 'in 6 days'],
        ['+7 days +1 hour', 'in 1 week'],
        ['+13 days +1 hour', 'in 1 week'],
        ['+1 hour +30 seconds', 'in 1 hour'],
        ['+2 hours +30 seconds', 'in 2 hours'],
        ['+1 minute +30 seconds', 'in 1 minute'],
        ['+2 minutes +30 seconds', 'in 2 minutes'],
        ['+1 second +500 milliseconds', 'in 1 second'],
        ['+5 seconds +500 milliseconds', 'in 5 seconds'],
        ['+0 seconds', 'just now'],
    ]);
});

describe('DateTime boundaries of periods', function (): void {
    it('sets exact start and end moments', function (string $method, string $expected): void {
        $dt = new DateTime('2024-05-15 13:45:30.123456', 'UTC');
        expect($dt->{$method}()->format('Y-m-d H:i:s.u')->get())->toBe($expected);
    })->with([
        ['startOfDay', '2024-05-15 00:00:00.000000'],
        ['endOfDay', '2024-05-15 23:59:59.999999'],
        ['startOfMonth', '2024-05-01 00:00:00.000000'],
        ['endOfMonth', '2024-05-31 23:59:59.999999'],
        ['startOfYear', '2024-01-01 00:00:00.000000'],
        ['endOfYear', '2024-12-31 23:59:59.999999'],
        ['startOfWeek', '2024-05-13 00:00:00.000000'],
        ['endOfWeek', '2024-05-19 23:59:59.999999'],
    ]);

    it('uses zero defaults for seconds, microseconds and ISO weekday', function (): void {
        $dt = new DateTime('2024-05-15 13:45:30.123456', 'UTC');
        expect($dt->setTime(8, 5)->format('H:i:s.u')->get())->toBe('08:05:00.000000');
        expect($dt->setISODate(2024, 10)->format('Y-m-d l')->get())->toBe('2024-03-04 Monday');
    });

    it('adds and subtracts wrapped and native intervals', function (): void {
        $dt = new DateTime('2024-01-15 00:00:00', 'UTC');
        expect($dt->add(DateInterval::fromSpec('P1D'))->format('Y-m-d')->get())->toBe('2024-01-16');
        expect($dt->add(new \DateInterval('P2D'))->format('Y-m-d')->get())->toBe('2024-01-17');
        expect($dt->sub(DateInterval::fromSpec('P1D'))->format('Y-m-d')->get())->toBe('2024-01-14');
        expect($dt->sub(new \DateInterval('P2D'))->format('Y-m-d')->get())->toBe('2024-01-13');
    });

    it('converts to a timezone given as Timezone object', function (): void {
        $dt = new DateTime('2024-01-15 10:00:00', 'UTC');
        expect($dt->toTimezone(new Timezone('Asia/Tokyo'))->format('H:i e')->get())->toBe('19:00 Asia/Tokyo');
    });

    it('exports all components', function (): void {
        expect(new DateTime('2024-05-15 13:45:30.123456', 'UTC')->toArray())->toBe([
            'year' => 2024,
            'month' => 5,
            'day' => 15,
            'hour' => 13,
            'minute' => 45,
            'second' => 30,
            'microsecond' => 123456,
            'timestamp' => 1715780730,
            'timezone' => 'UTC',
        ]);
    });
});
