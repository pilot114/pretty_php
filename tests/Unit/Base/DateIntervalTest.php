<?php

use PrettyPhp\Base\DateInterval;
use PrettyPhp\Base\Str;

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
            DateInterval::fromDateString('invalid');
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
