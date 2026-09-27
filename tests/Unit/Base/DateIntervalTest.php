<?php

use PrettyPhp\Base\DateInterval;
use PrettyPhp\Base\Str;

mutates(\PrettyPhp\Base\DateInterval::class);

describe('DateInterval', function (): void {
    it('can be constructed from spec', function (): void {
        $interval = new DateInterval('P1D');
        expect($interval->days())->toBe(1);
    });

    it('can be constructed from DateInterval', function (): void {
        $native = new \DateInterval('P2D');
        $interval = new DateInterval($native);
        expect($interval->days())->toBe(2);
    });

    it('can use interval() helper', function (): void {
        $interval = interval('P1D');
        expect($interval)->toBeInstanceOf(DateInterval::class);
        expect($interval->days())->toBe(1);
    });

    // ==================== Static Constructors ====================

    describe('static constructors', function (): void {
        it('can create from interval', function (): void {
            $native = new \DateInterval('P1D');
            $interval = DateInterval::fromInterval($native);
            expect($interval->days())->toBe(1);
        });

        it('can create from spec', function (): void {
            $interval = DateInterval::fromSpec('P1Y2M3DT4H5M6S');
            expect($interval->years())->toBe(1);
            expect($interval->months())->toBe(2);
            expect($interval->days())->toBe(3);
            expect($interval->hours())->toBe(4);
            expect($interval->minutes())->toBe(5);
            expect($interval->seconds())->toBe(6);
        });

        it('can create from date string', function (): void {
            $interval = DateInterval::fromDateString('1 day');
            expect($interval->days())->toBe(1);
        });

        it('throws exception for invalid date string', function (): void {
            (void) DateInterval::fromDateString('invalid');
        })->throws(\DateMalformedIntervalStringException::class);

        it('can create from parts', function (): void {
            $interval = DateInterval::create(years: 1, months: 2, days: 3, hours: 4, minutes: 5, seconds: 6);
            expect($interval->years())->toBe(1);
            expect($interval->months())->toBe(2);
            expect($interval->days())->toBe(3);
            expect($interval->hours())->toBe(4);
            expect($interval->minutes())->toBe(5);
            expect($interval->seconds())->toBe(6);
        });

        it('can create from seconds', function (): void {
            $interval = DateInterval::create(seconds: 30);
            expect($interval->seconds())->toBe(30);
        });

        it('can create from minutes', function (): void {
            $interval = DateInterval::create(minutes: 15);
            expect($interval->minutes())->toBe(15);
        });

        it('can create from hours', function (): void {
            $interval = DateInterval::create(hours: 2);
            expect($interval->hours())->toBe(2);
        });

        it('can create from days', function (): void {
            $interval = DateInterval::create(days: 7);
            expect($interval->days())->toBe(7);
        });

        it('can create from months', function (): void {
            $interval = DateInterval::create(months: 3);
            expect($interval->months())->toBe(3);
        });

        it('can create from years', function (): void {
            $interval = DateInterval::create(years: 2);
            expect($interval->years())->toBe(2);
        });
    });

    // ==================== Formatting ====================

    describe('formatting', function (): void {
        it('can format interval', function (): void {
            $interval = DateInterval::create(days: 1, hours: 2, minutes: 3);
            $formatted = $interval->format('%d days, %h hours, %i minutes');
            expect($formatted)->toBeInstanceOf(Str::class);
            expect($formatted->get())->toBe('1 days, 2 hours, 3 minutes');
        });

        it('can format as ISO 8601', function (): void {
            $interval = DateInterval::create(years: 1, months: 2, days: 3, hours: 4, minutes: 5, seconds: 6);
            $iso = $interval->toIso8601();
            expect($iso->get())->toBe('P1Y2M3DT4H5M6S');
        });

        it('can format as human readable', function (): void {
            $interval = DateInterval::create(days: 1, hours: 2);
            $readable = $interval->toHumanReadable();
            expect($readable->get())->toBe('1 day, 2 hours');
        });

        it('handles plural correctly in human readable', function (): void {
            $interval = DateInterval::create(days: 2, hours: 3);
            $readable = $interval->toHumanReadable();
            expect($readable->get())->toBe('2 days, 3 hours');
        });

        it('implements Stringable', function (): void {
            $interval = DateInterval::create(days: 1);
            expect((string) $interval)->toContain('1 days');
        });
    });

    // ==================== Getters ====================

    describe('getters', function (): void {
        it('can get years', function (): void {
            $interval = DateInterval::create(years: 5);
            expect($interval->years())->toBe(5);
        });

        it('can get months', function (): void {
            $interval = DateInterval::create(months: 3);
            expect($interval->months())->toBe(3);
        });

        it('can get days', function (): void {
            $interval = DateInterval::create(days: 7);
            expect($interval->days())->toBe(7);
        });

        it('can get hours', function (): void {
            $interval = DateInterval::create(hours: 12);
            expect($interval->hours())->toBe(12);
        });

        it('can get minutes', function (): void {
            $interval = DateInterval::create(minutes: 45);
            expect($interval->minutes())->toBe(45);
        });

        it('can get seconds', function (): void {
            $interval = DateInterval::create(seconds: 30);
            expect($interval->seconds())->toBe(30);
        });

        it('can get underlying DateInterval', function (): void {
            $interval = DateInterval::create(days: 1);
            expect($interval->get())->toBeInstanceOf(\DateInterval::class);
        });
    });

    // ==================== Comparison ====================

    describe('comparison', function (): void {
        it('can check equality', function (): void {
            $interval1 = DateInterval::create(days: 1, hours: 2);
            $interval2 = DateInterval::create(days: 1, hours: 2);
            expect($interval1->equals($interval2))->toBeTrue();
        });

        it('can detect inequality', function (): void {
            $interval1 = DateInterval::create(days: 1);
            $interval2 = DateInterval::create(days: 2);
            expect($interval1->equals($interval2))->toBeFalse();
        });
    });

    // ==================== Conversion ====================

    describe('conversion', function (): void {
        it('can convert to seconds', function (): void {
            $interval = DateInterval::create(hours: 1);
            expect($interval->toSeconds())->toBe(3600);
        });

        it('can convert to minutes', function (): void {
            $interval = DateInterval::create(hours: 2);
            expect($interval->toMinutes())->toBe(120);
        });

        it('can convert to hours', function (): void {
            $interval = DateInterval::create(days: 1);
            expect($interval->toHours())->toBe(24);
        });

        it('can convert to array', function (): void {
            $interval = DateInterval::create(days: 1, hours: 2);
            $array = $interval->toArray();
            expect($array)->toBeArray();
            expect($array['days'])->toBe(1);
            expect($array['hours'])->toBe(2);
            expect($array['inverted'])->toBeFalse();
        });
    });
});

describe('DateInterval edge cases', function (): void {
    it('formats every unit in human readable form', function (): void {
        expect(DateInterval::fromSpec('P1Y1M1DT1H1M1S')->toHumanReadable()->get())
            ->toBe('1 year, 1 month, 1 day, 1 hour, 1 minute, 1 second');
        expect(DateInterval::fromSpec('P2Y2M2DT2H2M2S')->toHumanReadable()->get())
            ->toBe('2 years, 2 months, 2 days, 2 hours, 2 minutes, 2 seconds');
        expect(DateInterval::fromSpec('PT0S')->toHumanReadable()->get())->toBe('0 seconds');
    });

    it('exposes microseconds, total days and inversion', function (): void {
        $interval = DateInterval::fromSpec('P1D');
        expect($interval->microseconds())->toBe(0.0);
        expect($interval->totalDays())->toBeFalse();
        expect($interval->isInverted())->toBeFalse();
    });

    it('compares with native DateInterval', function (): void {
        expect(DateInterval::fromSpec('P1D')->equals(new \DateInterval('P1D')))->toBeTrue();
    });
});

describe('DateInterval exact values', function (): void {
    it('creates intervals from individual parts', function (array $parts, string $expected): void {
        expect(DateInterval::create(...$parts)->toIso8601()->get())->toBe($expected);
    })->with([
        'nothing' => [[], 'P0D'],
        'years' => [['years' => 1], 'P1Y'],
        'months' => [['months' => 1], 'P1M'],
        'days' => [['days' => 1], 'P1D'],
        'hours' => [['hours' => 1], 'PT1H'],
        'minutes' => [['minutes' => 1], 'PT1M'],
        'seconds' => [['seconds' => 1], 'PT1S'],
        'everything' => [[2, 3, 4, 5, 6, 7], 'P2Y3M4DT5H6M7S'],
        'date and seconds' => [['days' => 2, 'seconds' => 9], 'P2DT9S'],
        'negative parts ignored' => [[-1, -1, -1, -1, -1, -1], 'P0D'],
    ]);

    it('converts to total seconds, minutes and hours', function (string $spec, int $seconds): void {
        expect(DateInterval::fromSpec($spec)->toSeconds())->toBe($seconds);
    })->with([
        ['P1Y', 31_536_000],
        ['P1M', 2_592_000],
        ['P1D', 86_400],
        ['PT1H', 3_600],
        ['PT1M', 60],
        ['PT1S', 1],
        ['P1Y1M1DT1H1M1S', 31_536_000 + 2_592_000 + 86_400 + 3_600 + 60 + 1],
    ]);

    it('truncates minutes and hours towards zero', function (): void {
        expect(DateInterval::fromSpec('PT119S')->toMinutes())->toBe(1);
        expect(DateInterval::fromSpec('PT7199S')->toHours())->toBe(1);

        $negative = new DateInterval(new \DateTimeImmutable('2024-01-01 00:01:59')->diff(new \DateTimeImmutable('2024-01-01')));
        expect($negative->isInverted())->toBeTrue();
        expect($negative->toSeconds())->toBe(-119);
        expect($negative->toMinutes())->toBe(-1);
        expect($negative->toArray())->toBe([
            'years' => 0,
            'months' => 0,
            'days' => 0,
            'hours' => 0,
            'minutes' => 1,
            'seconds' => 59,
            'microseconds' => 0.0,
            'total_days' => 0,
            'inverted' => true,
        ]);
    });

    it('compares every component', function (string $other): void {
        expect(DateInterval::fromSpec('P1Y1M1DT1H1M1S')->equals($other))->toBeFalse();
    })->with(['P2Y1M1DT1H1M1S', 'P1Y2M1DT1H1M1S', 'P1Y1M2DT1H1M1S', 'P1Y1M1DT2H1M1S', 'P1Y1M1DT1H2M1S', 'P1Y1M1DT1H1M2S']);

    it('compares direction and accepts all interval types', function (): void {
        $forward = new DateInterval(new \DateTimeImmutable('2024-01-01')->diff(new \DateTimeImmutable('2024-01-02')));
        $backward = new DateInterval(new \DateTimeImmutable('2024-01-02')->diff(new \DateTimeImmutable('2024-01-01')));
        expect($forward->equals($backward))->toBeFalse();
        expect(DateInterval::fromSpec('P1D')->equals('P1D'))->toBeTrue();
        expect(DateInterval::fromSpec('P1D')->equals(DateInterval::fromSpec('P1D')))->toBeTrue();
        expect(DateInterval::fromSpec('P1D')->equals(new \DateInterval('P1D')))->toBeTrue();
    });
});
