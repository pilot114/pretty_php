<?php

declare(strict_types=1);

use PrettyPhp\Base\ArraySessionStorage;
use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Json;
use PrettyPhp\Base\Num;
use PrettyPhp\Base\Session;
use PrettyPhp\Base\Timezone;
use PrettyPhp\Exception\ArrException;
use PrettyPhp\Exception\NumException;

mutates(Json::class, Num::class, Arr::class, Session::class, Timezone::class);

describe('Json details', function (): void {
    it('falls back to an empty object for unencodable data', function (): void {
        expect((string) Json::fromData(INF))->toBe('{}');
        expect(Json::fromData(INF)->isValid())->toBeFalse();
    });

    it('decodes into a single-element list when not associative', function (): void {
        $decoded = Json::fromString('{"a":1}')->decode(false)->unwrap()->get();
        expect($decoded)->toHaveCount(1);
        expect($decoded[0]->a)->toBe(1);
    });

    it('names missing paths and replaces scalar intermediates', function (): void {
        expect(Json::fromData(['a' => 1])->path('a.b')->unwrapErr())->toBe('Path not found: a.b');
        expect(Json::fromData(['a' => 1])->remove('b')->unwrapErr())->toBe('Path not found: b');
        expect(Json::fromData(['a' => 1])->set('a.b', 2)->unwrap()->get())->toBe(['a' => ['b' => 2]]);
        expect(Json::fromData(['a' => ['b' => 1, 'c' => 2]])->remove('a.b')->unwrap()->get())->toBe(['a' => ['c' => 2]]);
    });

    it('returns data-backed results from manipulation', function (): void {
        $merged = Json::fromString('{"a":1}')->merge(Json::fromString('{"b":2}'))->unwrap();
        expect($merged->get())->toBe(['a' => 1, 'b' => 2]);
        expect($merged->encode()->unwrap()->get())->toBe('{"a":1,"b":2}');
        expect(Json::fromString('{"a":1}')->set('b', 2)->unwrap()->encode()->unwrap()->get())->toBe('{"a":1,"b":2}');
        expect(Json::fromString('{"a":1,"b":2}')->remove('a')->unwrap()->encode()->unwrap()->get())->toBe('{"b":2}');
    });
});

describe('Num details', function (): void {
    it('allows equal clamp bounds and rejects inverted ones', function (): void {
        expect(new Num(7)->clamp(5, 5)->get())->toBe(5);
        expect(fn (): \PrettyPhp\Base\Num => new Num(7)->clamp(6, 5))->toThrow(NumException::class, 'Min value cannot be greater than max value');
    });

    it('divides by any non-zero number', function (): void {
        expect(new Num(6)->divide(-1.0)->get())->toBe(-6.0);
        expect(new Num(6)->divide(1.0)->get())->toBe(6.0);
        expect(new Num(7)->mod(-1.0)->get())->toBe(0.0);
        expect(new Num(7)->mod(1.0)->get())->toBe(0.0);
        expect(fn (): \PrettyPhp\Base\Num => new Num(6)->divide(0.0))->toThrow(\DivisionByZeroError::class, 'Division by zero');
        expect(fn (): \PrettyPhp\Base\Num => new Num(6)->mod(0.0))->toThrow(\DivisionByZeroError::class, 'Modulo by zero');
    });

    it('takes square roots of zero and rejects negatives', function (): void {
        expect(new Num(0)->sqrt()->get())->toBe(0.0);
        expect(fn (): \PrettyPhp\Base\Num => new Num(-0.5)->sqrt())->toThrow(NumException::class, 'Cannot calculate square root of negative number');
    });

    it('detects primes and signs exactly', function (): void {
        expect(new Num(25)->isPrime())->toBeFalse();
        expect(new Num(49)->isPrime())->toBeFalse();
        expect(new Num(29)->isPrime())->toBeTrue();
        expect(new Num(1)->isPositive())->toBeTrue();
        expect(new Num(-1)->isNegative())->toBeTrue();
    });

    it('parses upper-case prefixes', function (): void {
        expect(Num::fromHex('0XFF')->get())->toBe(255);
        expect(Num::fromBinary('0B101')->get())->toBe(5);
        expect(Num::fromOctal('0O17')->get())->toBe(15);
    });
});

describe('Arr details', function (): void {
    it('creates from generators preserving keys', function (): void {
        $generator = (static function (): Generator {
            yield 'a' => 1;
            yield 'b' => 2;
        })();
        expect(Arr::from($generator)->get())->toBe(['a' => 1, 'b' => 2]);
    });

    it('flips only scalar values', function (): void {
        expect(new Arr([[1], 'a', 2])->flip()->get())->toBe(['a' => 1, 2 => 2]);
    });

    it('accepts chunks of one and rejects zero', function (): void {
        expect(new Arr([1, 2])->chunk(1)->get())->toBe([[1], [2]]);
        expect(fn (): \PrettyPhp\Base\Arr => new Arr([1])->chunk(0))->toThrow(ArrException::class, 'Chunk size must be at least 1');
    });

    it('joins numbers and averages single values', function (): void {
        expect(new Arr([1, 2.5, true, null])->join('|')->get())->toBe('1|2.5|1|');
        expect(new Arr([5])->average())->toBe(5.0);
        expect(new Arr([])->average())->toBe(0.0);
    });

    it('zips by position regardless of keys and iterable type', function (): void {
        $generator = (static function (): Generator {
            yield 'x' => 'a';
            yield 'y' => 'b';
        })();
        expect(new Arr(['k1' => 1, 'k2' => 2])->zip($generator, ['p' => true])->get())
            ->toBe([[1, 'a', true], [2, 'b', null]]);
        expect(new Arr([])->unzip()->get())->toBe([]);
    });

    it('computes set operations with generators', function (): void {
        $make = static fn (array $values): Generator => (static function () use ($values): Generator {
            yield from $values;
        })();
        expect(array_values(new Arr([1, 2, 3])->difference($make([2]))->get()))->toBe([1, 3]);
        expect(array_values(new Arr([1, 2, 3])->intersection($make([2, 3]), $make([3]))->get()))->toBe([3]);
        expect(array_values(new Arr([1])->union($make([2]), $make([1, 3]))->get()))->toBe([1, 2, 3]);
    });

    it('sorts mixed arrays and objects stably', function (): void {
        $object = new stdClass();
        $object->rank = 1;
        $object->name = 'object';

        $items = [['rank' => 2, 'name' => 'first'], $object, ['rank' => 2, 'name' => 'second'], ['name' => 'no rank']];

        $sorted = new Arr($items)->sortByKeys(['rank' => 'asc'])->get();
        expect(array_map(static fn (array|object $item): string => is_array($item) ? $item['name'] : $item->name, $sorted))
            ->toBe(['no rank', 'object', 'first', 'second']);
    });

    it('plucks from objects and integer keys', function (): void {
        $object = new stdClass();
        $object->{'0'} = 'zero';

        expect(new Arr([['a', 'b'], $object, 'scalar'])->pluck(0)->get())->toBe(['a', 'zero']);
    });
});

describe('Session flash lifecycle', function (): void {
    beforeEach(function (): void {
        Session::useStorage(new ArraySessionStorage());
    });

    afterEach(function (): void {
        Session::useStorage(null);
        if (Session::isActive()) {
            Session::close();
        }
    });

    it('keeps flashed data for exactly one more request', function (): void {
        Session::flash('notice', 'saved');

        // next request
        Session::ageFlashData();
        expect(Session::get('_old_flash'))->toBe(['notice' => 'saved']);
        expect(Session::get('_flash'))->toBe([]);
        Session::keepFlash('notice');
        expect(Session::hasFlash('notice'))->toBeTrue();

        // request after that: kept value becomes old once more, then expires
        Session::ageFlashData();
        expect(Session::get('_old_flash'))->toBe(['notice' => 'saved']);
        Session::ageFlashData();
        expect(Session::get('_old_flash'))->toBe([]);
    });

    it('starts counters from zero and pops nothing from non-arrays', function (): void {
        expect(Session::increment('fresh'))->toBe(1);
        expect(Session::decrement('other'))->toBe(-1);
        Session::set('scalar', 'value');
        expect(Session::pop('scalar'))->toBeNull();
    });

    it('marks the first activity check and uses the configured timeout by default', function (): void {
        expect(Session::hasExpired())->toBeFalse();
        expect(Session::get('_last_activity'))->toBeInt();

        Session::set('_last_activity', time() - Session::getTimeout() - 100);
        expect(Session::hasExpired())->toBeTrue();

        Session::set('_last_activity', time());
        expect(Session::hasExpired(1000))->toBeFalse();
        expect(Session::get('_last_activity'))->toBeGreaterThanOrEqual(time() - 1);
    });
});

describe('Timezone details', function (): void {
    it('formats offsets including negative half hours', function (string $zone, string $date, string $expected, float $hours): void {
        $tz = new Timezone($zone);
        $moment = new DateTimeImmutable($date);
        expect($tz->offsetString($moment)->get())->toBe($expected);
        expect($tz->offsetHours($moment))->toBe($hours);
    })->with([
        ['America/St_Johns', '2024-01-15', '-03:30', -3.5],
        ['Asia/Kolkata', '2024-01-15', '+05:30', 5.5],
        ['Europe/Berlin', '2024-01-15', '+01:00', 1.0],
        ['Europe/Berlin', '2024-07-15', '+02:00', 2.0],
        ['UTC', '2024-07-15', '+00:00', 0.0],
    ]);

    it('resolves abbreviations with offset and daylight saving hints', function (): void {
        expect(Timezone::fromAbbreviation('EST')?->name()->get())->toBe('America/New_York');
        expect(Timezone::fromAbbreviation('', 3600, false)?->name()->get())->toBe('Europe/Paris');
        expect(Timezone::fromAbbreviation('', 3600, true)?->name()->get())->toBe('Europe/London');
        expect(Timezone::fromAbbreviation('', -18000, false)?->name()->get())->toBe('America/New_York');
        expect(Timezone::fromAbbreviation('', -18000))->toBeNull();
    });

    it('lists transitions with and without bounds', function (): void {
        $tz = new Timezone('Europe/Berlin');
        expect(count($tz->transitions()))->toBeGreaterThan(100);
        $bounded = $tz->transitions(1_704_067_200, 1_735_689_600); // year 2024
        expect(array_column(array_slice($bounded, 1), 'time'))->toBe(['2024-03-31T01:00:00+00:00', '2024-10-27T01:00:00+00:00']);
        expect(count($tz->transitions(1_704_067_200)))->toBeGreaterThan(count($bounded));
    });
});
