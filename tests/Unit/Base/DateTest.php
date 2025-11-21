<?php

use PrettyPhp\Base\Date;
use PrettyPhp\Base\DateTime;
use PrettyPhp\Base\Str;

describe('Date', function (): void {
    // ==================== Current Time ====================

    describe('current time', function (): void {
        it('can get current timestamp', function (): void {
            $time = Date::time();
            expect($time)->toBeInt();
            expect($time)->toBeGreaterThan(0);
        });

        it('can get now', function (): void {
            $now = Date::now();
            expect($now)->toBeInstanceOf(DateTime::class);
        });
    });

    // ==================== Validation ====================

    describe('validation', function (): void {
        it('can validate valid date', function (): void {
            expect(Date::checkdate(1, 15, 2024))->toBeTrue();
        });

        it('can validate invalid date', function (): void {
            expect(Date::checkdate(2, 30, 2024))->toBeFalse();
        });

        it('can use isValid', function (): void {
            expect(Date::isValid(2024, 1, 15))->toBeTrue();
            expect(Date::isValid(2024, 2, 30))->toBeFalse();
        });
    });

    // ==================== Formatting ====================

    describe('formatting', function (): void {
        it('can format timestamp', function (): void {
            $formatted = Date::format('Y-m-d', 1705324200);
            expect($formatted)->toBeInstanceOf(Str::class);
            expect($formatted->get())->toBe('2024-01-15');
        });

        it('can format as gmdate', function (): void {
            $formatted = Date::gmdate('Y-m-d', 1705324200);
            expect($formatted)->toBeInstanceOf(Str::class);
        });

        it('can format as integer', function (): void {
            $year = Date::idate('Y', 1705324200);
            expect($year)->toBe(2024);
        });
    });

    // ==================== Parsing ====================

    describe('parsing', function (): void {
        it('can parse date string', function (): void {
            $parsed = Date::parse('2024-01-15 12:30:00');
            expect($parsed)->toBeArray();
            expect($parsed['year'])->toBe(2024);
            expect($parsed['month'])->toBe(1);
            expect($parsed['day'])->toBe(15);
        });

        it('can parse from format', function (): void {
            $parsed = Date::parseFromFormat('d/m/Y', '15/01/2024');
            expect($parsed)->toBeArray();
            expect($parsed['day'])->toBe(15);
            expect($parsed['month'])->toBe(1);
            expect($parsed['year'])->toBe(2024);
        });

        it('can strtotime', function (): void {
            $timestamp = Date::strtotime('2024-01-15');
            expect($timestamp)->toBeInt();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('returns false for invalid strtotime', function (): void {
            $timestamp = Date::strtotime('invalid date');
            expect($timestamp)->toBeFalse();
        });
    });

    // ==================== Timestamp Creation ====================

    describe('timestamp creation', function (): void {
        it('can create timestamp with mktime', function (): void {
            $timestamp = Date::mktime(12, 30, 0, 1, 15, 2024);
            expect($timestamp)->toBeInt();
        });

        it('can create timestamp with gmmktime', function (): void {
            $timestamp = Date::gmmktime(12, 30, 0, 1, 15, 2024);
            expect($timestamp)->toBeInt();
        });
    });

    // ==================== Date Information ====================

    describe('date information', function (): void {
        it('can get date info', function (): void {
            $info = Date::getdate(1705324200);
            expect($info)->toBeArray();
            expect($info['year'])->toBe(2024);
            expect($info['mon'])->toBe(1);
            expect($info['mday'])->toBe(15);
        });

        it('can get localtime', function (): void {
            $localtime = Date::localtime(1705324200, false);
            expect($localtime)->toBeArray();
        });

        it('can get localtime associative', function (): void {
            $localtime = Date::localtime(1705324200, true);
            expect($localtime)->toBeArray();
            expect($localtime)->toHaveKey('tm_year');
        });
    });

    // ==================== Timezone ====================

    describe('timezone', function (): void {
        it('can get default timezone', function (): void {
            $tz = Date::getDefaultTimezone();
            expect($tz)->toBeInstanceOf(Str::class);
            expect($tz->get())->not->toBeEmpty();
        });

        it('can set default timezone', function (): void {
            $originalTz = Date::getDefaultTimezone()->get();
            Date::setDefaultTimezone('UTC');
            expect(Date::getDefaultTimezone()->get())->toBe('UTC');
            Date::setDefaultTimezone($originalTz);
        });

        it('can get timezone version', function (): void {
            $version = Date::timezoneVersion();
            expect($version)->toBeInstanceOf(Str::class);
            expect($version->get())->not->toBeEmpty();
        });
    });

    // ==================== Sun Times ====================

    describe('sun times', function (): void {
        it('can get sunrise', function (): void {
            $sunrise = Date::sunrise(1705324200, \SUNFUNCS_RET_TIMESTAMP, 40.7128, -74.0060);
            expect($sunrise)->not->toBeFalse();
        });

        it('can get sunset', function (): void {
            $sunset = Date::sunset(1705324200, \SUNFUNCS_RET_TIMESTAMP, 40.7128, -74.0060);
            expect($sunset)->not->toBeFalse();
        });

        it('can get sun info', function (): void {
            $info = Date::sunInfo(1705324200, 40.7128, -74.0060);
            expect($info)->toBeArray();
            expect($info)->toHaveKey('sunrise');
            expect($info)->toHaveKey('sunset');
            expect($info)->toHaveKey('transit');
        });
    });

    // ==================== Factory Methods ====================

    describe('factory methods', function (): void {
        it('can create DateTime', function (): void {
            $dt = Date::create('2024-01-15');
            expect($dt)->toBeInstanceOf(DateTime::class);
            expect($dt->year())->toBe(2024);
        });

        it('can create from format', function (): void {
            $dt = Date::createFromFormat('d/m/Y', '15/01/2024');
            expect($dt)->toBeInstanceOf(DateTime::class);
            expect($dt->year())->toBe(2024);
        });

        it('can create from timestamp', function (): void {
            $dt = Date::createFromTimestamp(1705324200);
            expect($dt)->toBeInstanceOf(DateTime::class);
            expect($dt->timestamp())->toBe(1705324200);
        });
    });

    // ==================== Quick Helpers ====================

    describe('quick helpers', function (): void {
        it('can get current year', function (): void {
            $year = Date::year();
            expect($year)->toBeInt();
            expect($year)->toBeGreaterThan(2020);
        });

        it('can get current month', function (): void {
            $month = Date::month();
            expect($month)->toBeInt();
            expect($month)->toBeGreaterThanOrEqual(1);
            expect($month)->toBeLessThanOrEqual(12);
        });

        it('can get current day', function (): void {
            $day = Date::day();
            expect($day)->toBeInt();
            expect($day)->toBeGreaterThanOrEqual(1);
            expect($day)->toBeLessThanOrEqual(31);
        });

        it('can get current hour', function (): void {
            $hour = Date::hour();
            expect($hour)->toBeInt();
            expect($hour)->toBeGreaterThanOrEqual(0);
            expect($hour)->toBeLessThanOrEqual(23);
        });

        it('can get current minute', function (): void {
            $minute = Date::minute();
            expect($minute)->toBeInt();
            expect($minute)->toBeGreaterThanOrEqual(0);
            expect($minute)->toBeLessThanOrEqual(59);
        });

        it('can get current second', function (): void {
            $second = Date::second();
            expect($second)->toBeInt();
            expect($second)->toBeGreaterThanOrEqual(0);
            expect($second)->toBeLessThanOrEqual(59);
        });

        it('can get today', function (): void {
            $today = Date::today();
            expect($today)->toBeInstanceOf(DateTime::class);
            expect($today->hour())->toBe(0);
        });

        it('can get yesterday', function (): void {
            $yesterday = Date::yesterday();
            expect($yesterday)->toBeInstanceOf(DateTime::class);
            expect($yesterday->hour())->toBe(0);
        });

        it('can get tomorrow', function (): void {
            $tomorrow = Date::tomorrow();
            expect($tomorrow)->toBeInstanceOf(DateTime::class);
            expect($tomorrow->hour())->toBe(0);
        });
    });

    // ==================== Constants ====================

    describe('constants', function (): void {
        it('has format constants', function (): void {
            expect(Date::ATOM)->toBe(\DateTimeInterface::ATOM);
            expect(Date::RFC822)->toBe(\DateTimeInterface::RFC822);
            expect(Date::RFC3339)->toBe(\DateTimeInterface::RFC3339);
            expect(Date::W3C)->toBe(\DateTimeInterface::W3C);
        });
    });
});
