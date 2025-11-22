<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Str;
use PrettyPhp\Base\Timezone;

describe('Timezone', function (): void {
    it('can be constructed from string', function (): void {
        $tz = new Timezone('UTC');
        expect($tz->name()->get())->toBe('UTC');
    });

    it('can be constructed from DateTimeZone', function (): void {
        $native = new \DateTimeZone('America/New_York');
        $tz = new Timezone($native);
        expect($tz->name()->get())->toBe('America/New_York');
    });

    it('can use timezone() helper', function (): void {
        $tz = timezone('UTC');
        expect($tz)->toBeInstanceOf(Timezone::class);
        expect($tz->name()->get())->toBe('UTC');
    });

    it('implements Stringable', function (): void {
        $tz = new Timezone('UTC');
        expect((string) $tz)->toBe('UTC');
    });

    // ==================== Static Constructors ====================

    describe('static constructors', function (): void {
        it('can create from timezone', function (): void {
            $native = new \DateTimeZone('UTC');
            $tz = Timezone::fromTimezone($native);
            expect($tz->name()->get())->toBe('UTC');
        });

        it('can create from identifier', function (): void {
            $tz = Timezone::fromIdentifier('Europe/Paris');
            expect($tz->name()->get())->toBe('Europe/Paris');
        });

        it('can create from abbreviation', function (): void {
            $tz = Timezone::fromAbbreviation('EST');
            expect($tz)->not->toBeNull();
            if ($tz instanceof \PrettyPhp\Base\Timezone) {
                expect($tz->name()->get())->toContain('America');
            }
        });

        it('can create UTC', function (): void {
            $tz = Timezone::utc();
            expect($tz->name()->get())->toBe('UTC');
        });
    });

    // ==================== Information ====================

    describe('information', function (): void {
        it('can get name', function (): void {
            $tz = new Timezone('America/New_York');
            $name = $tz->name();
            expect($name)->toBeInstanceOf(Str::class);
            expect($name->get())->toBe('America/New_York');
        });

        it('can get offset', function (): void {
            $tz = new Timezone('UTC');
            $offset = $tz->offset();
            expect($offset)->toBe(0);
        });

        it('can get offset in hours', function (): void {
            $tz = new Timezone('UTC');
            expect($tz->offsetHours())->toBe(0.0);
        });

        it('can get offset as string', function (): void {
            $tz = new Timezone('UTC');
            $offsetStr = $tz->offsetString();
            expect($offsetStr)->toBeInstanceOf(Str::class);
            expect($offsetStr->get())->toBe('+00:00');
        });

        it('can get location', function (): void {
            $tz = new Timezone('America/New_York');
            $location = $tz->location();
            expect($location)->toBeArray();
            if (is_array($location)) {
                expect($location)->toHaveKey('country_code');
                expect($location)->toHaveKey('latitude');
                expect($location)->toHaveKey('longitude');
            }
        });

        it('can get transitions', function (): void {
            $tz = new Timezone('America/New_York');
            $transitions = $tz->transitions();
            expect($transitions)->toBeArray();
        });

        it('can get underlying DateTimeZone', function (): void {
            $tz = new Timezone('UTC');
            expect($tz->get())->toBeInstanceOf(\DateTimeZone::class);
        });
    });

    // ==================== Comparison ====================

    describe('comparison', function (): void {
        it('can check equality', function (): void {
            $tz1 = new Timezone('UTC');
            $tz2 = new Timezone('UTC');
            expect($tz1->equals($tz2))->toBeTrue();
        });

        it('can detect inequality', function (): void {
            $tz1 = new Timezone('UTC');
            $tz2 = new Timezone('America/New_York');
            expect($tz1->equals($tz2))->toBeFalse();
        });

        it('can compare with string', function (): void {
            $tz = new Timezone('UTC');
            expect($tz->equals('UTC'))->toBeTrue();
        });

        it('can compare with DateTimeZone', function (): void {
            $tz = new Timezone('UTC');
            $native = new \DateTimeZone('UTC');
            expect($tz->equals($native))->toBeTrue();
        });
    });

    // ==================== Static Lists ====================

    describe('static lists', function (): void {
        it('can get all identifiers', function (): void {
            $identifiers = Timezone::identifiers();
            expect($identifiers)->toBeInstanceOf(Arr::class);
            expect($identifiers->count())->toBeGreaterThan(0);
        });

        it('can get abbreviations', function (): void {
            $abbreviations = Timezone::abbreviations();
            expect($abbreviations)->toBeArray();
            expect($abbreviations)->not->toBeEmpty();
        });

        it('can get Africa timezones', function (): void {
            $africa = Timezone::africa();
            expect($africa)->toBeInstanceOf(Arr::class);
            expect($africa->count())->toBeGreaterThan(0);
        });

        it('can get America timezones', function (): void {
            $america = Timezone::america();
            expect($america)->toBeInstanceOf(Arr::class);
            expect($america->count())->toBeGreaterThan(0);
        });

        it('can get Europe timezones', function (): void {
            $europe = Timezone::europe();
            expect($europe)->toBeInstanceOf(Arr::class);
            expect($europe->count())->toBeGreaterThan(0);
        });

        it('can get Asia timezones', function (): void {
            $asia = Timezone::asia();
            expect($asia)->toBeInstanceOf(Arr::class);
            expect($asia->count())->toBeGreaterThan(0);
        });

        it('can get timezones for country', function (): void {
            $us = Timezone::forCountry('US');
            expect($us)->toBeInstanceOf(Arr::class);
            expect($us->count())->toBeGreaterThan(0);
        });
    });

    // ==================== Conversion ====================

    describe('conversion', function (): void {
        it('can convert to array', function (): void {
            $tz = new Timezone('UTC');
            $array = $tz->toArray();
            expect($array)->toBeArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('offset');
            expect($array)->toHaveKey('offset_string');
            expect($array['name'])->toBe('UTC');
        });
    });
});
