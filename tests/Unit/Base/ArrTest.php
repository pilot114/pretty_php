<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Str;

describe('Arr', function (): void {
    it('can be constructed and get value', function (): void {
        $arr = new Arr([1, 2, 3]);
        expect($arr->get())->toBe([1, 2, 3]);
        expect($arr->get())->toBe([1, 2, 3]);
    });

    it('can count elements', function (): void {
        expect(new Arr([1, 2, 3])->count())->toBe(3);
        expect(new Arr([])->count())->toBe(0);
    });

    it('can check if empty', function (): void {
        expect(new Arr([])->isEmpty())->toBeTrue();
        expect(new Arr([1, 2, 3])->isEmpty())->toBeFalse();
    });

    it('can check if not empty', function (): void {
        expect(new Arr([])->isNotEmpty())->toBeFalse();
        expect(new Arr([1, 2, 3])->isNotEmpty())->toBeTrue();
    });

    it('can get first element', function (): void {
        expect(new Arr([1, 2, 3])->first())->toBe(1);
        expect(new Arr([])->first())->toBeFalse();
    });

    it('can get last element', function (): void {
        expect(new Arr([1, 2, 3])->last())->toBe(3);
        expect(new Arr([])->last())->toBeFalse();
    });

    it('can push element', function (): void {
        $arr = new Arr([1, 2])->push(3);
        expect($arr->get())->toBe([1, 2, 3]);
        expect($arr)->not->toBe(new Arr([1, 2])); // immutable
    });

    it('can pop element', function (): void {
        $result = new Arr([1, 2, 3])->pop();
        expect($result)->toBe(3);
    });

    it('can shift element', function (): void {
        $result = new Arr([1, 2, 3])->shift();
        expect($result)->toBe(1);
    });

    it('can unshift element', function (): void {
        $arr = new Arr([2, 3])->unshift(1);
        expect($arr->get())->toBe([1, 2, 3]);
    });

    it('can check if contains element', function (): void {
        expect(new Arr([1, 2, 3])->contains(2))->toBeTrue();
        expect(new Arr([1, 2, 3])->contains(4))->toBeFalse();
        expect(new Arr([1, 2, 3])->contains('2'))->toBeFalse(); // strict
    });

    it('can find index of element', function (): void {
        expect(new Arr([1, 2, 3])->indexOf(2))->toBe(1);
        expect(new Arr([1, 2, 3])->indexOf(4))->toBe(-1);
    });

    it('can slice array', function (): void {
        $arr = new Arr([1, 2, 3, 4, 5])->slice(1, 3);
        expect($arr->get())->toBe([2, 3, 4]);
    });

    it('can slice array without length', function (): void {
        $arr = new Arr([1, 2, 3, 4, 5])->slice(2);
        expect($arr->get())->toBe([3, 4, 5]);
    });

    it('can splice array', function (): void {
        $arr = new Arr([1, 2, 3, 4])->splice(1, 2, [10, 20]);
        expect($arr->get())->toBe([1, 10, 20, 4]);
    });

    it('can merge arrays', function (): void {
        $arr = new Arr([1, 2])->merge([3, 4]);
        expect($arr->get())->toBe([1, 2, 3, 4]);
    });

    it('can get unique values', function (): void {
        $arr = new Arr([1, 2, 2, 3, 3])->unique();
        expect($arr->get())->toBe([0 => 1, 1 => 2, 3 => 3]);
    });

    it('can reverse array', function (): void {
        $arr = new Arr([1, 2, 3])->reverse();
        expect($arr->get())->toBe([3, 2, 1]);
    });

    it('can sort array', function (): void {
        $arr = new Arr([3, 1, 2])->sort();
        expect($arr->get())->toBe([1, 2, 3]);
    });

    it('can sort array with callback', function (): void {
        $arr = new Arr([3, 1, 2])->sort(fn($a, $b): int => $b <=> $a);
        expect($arr->get())->toBe([3, 2, 1]);
    });

    it('can filter array', function (): void {
        $arr = new Arr([1, 2, 3, 4])->filter(fn($x): bool => $x > 2);
        expect($arr->get())->toBe([2 => 3, 3 => 4]);
    });

    it('can filter array with default filter', function (): void {
        $arr = new Arr([0, 1, '', 'hello', false, null, []])->filter();
        expect($arr->get())->toBe([1 => 1, 3 => 'hello']);
    });

    it('can map array', function (): void {
        $arr = new Arr([1, 2, 3])->map(fn($x): int => $x * 2);
        expect($arr->get())->toBe([2, 4, 6]);
    });

    it('can map with key', function (): void {
        $arr = new Arr(['a' => 1, 'b' => 2])->map(fn($value, $key): string => $key . $value);
        expect($arr->get())->toBe(['a' => 'a1', 'b' => 'b2']);
    });

    it('can reduce array', function (): void {
        $result = new Arr([1, 2, 3, 4])->reduce(fn($carry, $item): float|int => $carry + $item, 0);
        expect($result)->toBe(10);
    });

    it('can iterate with each', function (): void {
        $result = [];
        new Arr([1, 2, 3])->each(function ($value) use (&$result): void {
            $result[] = $value * 2;
        });
        expect($result)->toBe([2, 4, 6]);
    });

    it('can find element', function (): void {
        $result = new Arr([1, 2, 3, 4])->find(fn($x): bool => $x > 2);
        expect($result)->toBe(3);

        $notFound = new Arr([1, 2])->find(fn($x): bool => $x > 5);
        expect($notFound)->toBeNull();
    });

    it('can find index', function (): void {
        $result = new Arr([1, 2, 3, 4])->findIndex(fn($x): bool => $x > 2);
        expect($result)->toBe(2);

        $notFound = new Arr([1, 2])->findIndex(fn($x): bool => $x > 5);
        expect($notFound)->toBe(-1);
    });

    it('can check some condition', function (): void {
        expect(new Arr([1, 2, 3])->some(fn($x): bool => $x > 2))->toBeTrue();
        expect(new Arr([1, 2])->some(fn($x): bool => $x > 5))->toBeFalse();
    });

    it('can check every condition', function (): void {
        expect(new Arr([2, 3, 4])->every(fn($x): bool => $x > 1))->toBeTrue();
        expect(new Arr([1, 2, 3])->every(fn($x): bool => $x > 1))->toBeFalse();
    });

    it('can get keys', function (): void {
        $arr = new Arr(['a' => 1, 'b' => 2])->keys();
        expect($arr->get())->toBe(['a', 'b']);
    });

    it('can get values', function (): void {
        $arr = new Arr(['a' => 1, 'b' => 2])->values();
        expect($arr->get())->toBe([1, 2]);
    });

    it('can flip array', function (): void {
        $arr = new Arr(['a' => 1, 'b' => 2])->flip();
        expect($arr->get())->toBe([1 => 'a', 2 => 'b']);
    });

    it('can flip array with mixed values', function (): void {
        $arr = new Arr(['a', 'b', 1.5, new stdClass()])->flip();
        expect($arr->get())->toBe(['a' => 0, 'b' => 1]); // only strings and ints
    });

    it('can chunk array', function (): void {
        $arr = new Arr([1, 2, 3, 4, 5])->chunk(2);
        expect($arr->get())->toBe([[1, 2], [3, 4], [5]]);
    });

    it('throws exception for invalid chunk size', function (): void {
        expect(fn(): \PrettyPhp\Base\Arr => new Arr([1, 2, 3])->chunk(0))
            ->toThrow(InvalidArgumentException::class, 'Chunk size must be at least 1');
    });

    it('can join elements', function (): void {
        $str = new Arr(['a', 'b', 'c'])->join(',');
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('a,b,c');
    });

    it('can join without separator', function (): void {
        $str = new Arr(['a', 'b', 'c'])->join();
        expect($str->get())->toBe('abc');
    });

    it('can group by key', function (): void {
        $arr = new Arr([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'veggie', 'name' => 'carrot']
        ]);

        $result = $arr->groupBy(fn($item): string => $item['type']);
        $grouped = $result->get();

        expect($grouped['fruit'])->toHaveCount(2);
        expect($grouped['veggie'])->toHaveCount(1);
    });

    it('can flatten array', function (): void {
        $arr = new Arr([1, [2, 3], [4, [5]]])->flatten();
        expect($arr->get())->toBe([1, 2, 3, 4, [5]]);
    });

    it('can flatten array with depth', function (): void {
        $arr = new Arr([1, [2, [3, [4]]]])->flatten(2);
        expect($arr->get())->toBe([1, 2, 3, [4]]);
    });

    it('can get minimum value', function (): void {
        expect(new Arr([3, 1, 2])->min())->toBe(1);
    });

    it('throws exception for min on empty array', function (): void {
        expect(fn(): mixed => new Arr([])->min())
            ->toThrow(InvalidArgumentException::class, 'Cannot get minimum of empty array');
    });

    it('can get maximum value', function (): void {
        expect(new Arr([3, 1, 2])->max())->toBe(3);
    });

    it('throws exception for max on empty array', function (): void {
        expect(fn(): mixed => new Arr([])->max())
            ->toThrow(InvalidArgumentException::class, 'Cannot get maximum of empty array');
    });

    it('can get sum', function (): void {
        expect(new Arr([1, 2, 3])->sum())->toBe(6);
        expect(new Arr([])->sum())->toBe(0);
    });

    it('can get average', function (): void {
        expect(new Arr([1, 2, 3, 4])->average())->toBe(2.5);
        expect(new Arr([])->average())->toBe(0.0);
    });

    // Enhanced Methods Tests

    it('can partition array', function (): void {
        $arr = new Arr([1, 2, 3, 4, 5, 6]);
        $partitioned = $arr->partition(fn($n): bool => $n % 2 === 0);
        expect($partitioned->get())->toBe([
            [2, 4, 6],  // pass
            [1, 3, 5],  // fail
        ]);
    });

    it('can zip arrays together', function (): void {
        $arr = new Arr([1, 2, 3]);
        $zipped = $arr->zip(['a', 'b', 'c'], ['x', 'y', 'z']);
        expect($zipped->get())->toBe([
            [1, 'a', 'x'],
            [2, 'b', 'y'],
            [3, 'c', 'z'],
        ]);
    });

    it('can zip arrays with different lengths', function (): void {
        $arr = new Arr([1, 2]);
        $zipped = $arr->zip(['a', 'b', 'c']);
        expect($zipped->get())->toBe([
            [1, 'a'],
            [2, 'b'],
            [null, 'c'],
        ]);
    });

    it('can unzip array of tuples', function (): void {
        $arr = new Arr([
            [1, 'a', 'x'],
            [2, 'b', 'y'],
            [3, 'c', 'z'],
        ]);
        $unzipped = $arr->unzip();
        expect($unzipped->get())->toBe([
            [1, 2, 3],
            ['a', 'b', 'c'],
            ['x', 'y', 'z'],
        ]);
    });

    it('can get difference between arrays', function (): void {
        $arr = new Arr([1, 2, 3, 4]);
        $diff = $arr->difference([2, 3], [4]);
        expect($diff->get())->toContain(1);
        expect($diff->count())->toBe(1);
    });

    it('can get intersection between arrays', function (): void {
        $arr = new Arr([1, 2, 3, 4]);
        $intersection = $arr->intersection([2, 3, 5], [3, 4]);
        expect($intersection->get())->toContain(3);
    });

    it('can get union of arrays', function (): void {
        $arr = new Arr([1, 2, 3]);
        $union = $arr->union([3, 4, 5], [5, 6, 7]);
        $values = array_values($union->get());
        expect(count($values))->toBe(7);
        expect($values)->toContain(1);
        expect($values)->toContain(7);
    });

    it('can sort by multiple keys', function (): void {
        $arr = new Arr([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ]);
        $sorted = $arr->sortByKeys(['age' => 'asc', 'name' => 'asc']);
        $values = array_values($sorted->get());
        expect($values[0]['name'])->toBe('Jane');
        expect($values[1]['name'])->toBe('Bob');
        expect($values[2]['name'])->toBe('John');
    });

    it('can sort by keys in descending order', function (): void {
        $arr = new Arr([
            ['value' => 10],
            ['value' => 30],
            ['value' => 20],
        ]);
        $sorted = $arr->sortByKeys(['value' => 'desc']);
        $values = array_values($sorted->get());
        expect($values[0]['value'])->toBe(30);
        expect($values[2]['value'])->toBe(10);
    });

    it('can pluck values from array of arrays', function (): void {
        $arr = new Arr([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ]);
        $names = $arr->pluck('name');
        expect($names->get())->toBe(['John', 'Jane', 'Bob']);
    });

    it('can pluck values from array of objects', function (): void {
        $obj1 = new \stdClass();
        $obj1->name = 'John';

        $obj2 = new \stdClass();
        $obj2->name = 'Jane';

        $arr = new Arr([$obj1, $obj2]);
        $names = $arr->pluck('name');
        expect($names->get())->toBe(['John', 'Jane']);
    });
});
