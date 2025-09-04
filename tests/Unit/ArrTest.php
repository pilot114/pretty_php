<?php

use PrettyPhp\Arr;
use PrettyPhp\Str;

describe('Arr', function () {
    it('can be constructed and get value', function () {
        $arr = new Arr([1, 2, 3]);
        expect($arr->get())->toBe([1, 2, 3]);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can count elements', function () {
        expect((new Arr([1, 2, 3]))->count())->toBe(3);
        expect((new Arr([]))->count())->toBe(0);
    });

    it('can check if empty', function () {
        expect((new Arr([]))->isEmpty())->toBeTrue();
        expect((new Arr([1, 2, 3]))->isEmpty())->toBeFalse();
    });

    it('can check if not empty', function () {
        expect((new Arr([]))->isNotEmpty())->toBeFalse();
        expect((new Arr([1, 2, 3]))->isNotEmpty())->toBeTrue();
    });

    it('can get first element', function () {
        expect((new Arr([1, 2, 3]))->first())->toBe(1);
        expect((new Arr([]))->first())->toBeFalse();
    });

    it('can get last element', function () {
        expect((new Arr([1, 2, 3]))->last())->toBe(3);
        expect((new Arr([]))->last())->toBeFalse();
    });

    it('can push element', function () {
        $result = (new Arr([1, 2]))->push(3);
        expect($result->toArray())->toBe([1, 2, 3]);
        expect($result)->not->toBe(new Arr([1, 2])); // immutable
    });

    it('can pop element', function () {
        $result = (new Arr([1, 2, 3]))->pop();
        expect($result)->toBe(3);
    });

    it('can shift element', function () {
        $result = (new Arr([1, 2, 3]))->shift();
        expect($result)->toBe(1);
    });

    it('can unshift element', function () {
        $result = (new Arr([2, 3]))->unshift(1);
        expect($result->toArray())->toBe([1, 2, 3]);
    });

    it('can check if contains element', function () {
        expect((new Arr([1, 2, 3]))->contains(2))->toBeTrue();
        expect((new Arr([1, 2, 3]))->contains(4))->toBeFalse();
        expect((new Arr([1, 2, 3]))->contains('2'))->toBeFalse(); // strict
    });

    it('can find index of element', function () {
        expect((new Arr([1, 2, 3]))->indexOf(2))->toBe(1);
        expect((new Arr([1, 2, 3]))->indexOf(4))->toBe(-1);
    });

    it('can slice array', function () {
        $result = (new Arr([1, 2, 3, 4, 5]))->slice(1, 3);
        expect($result->toArray())->toBe([2, 3, 4]);
    });

    it('can slice array without length', function () {
        $result = (new Arr([1, 2, 3, 4, 5]))->slice(2);
        expect($result->toArray())->toBe([3, 4, 5]);
    });

    it('can splice array', function () {
        $result = (new Arr([1, 2, 3, 4]))->splice(1, 2, [10, 20]);
        expect($result->toArray())->toBe([1, 10, 20, 4]);
    });

    it('can merge arrays', function () {
        $result = (new Arr([1, 2]))->merge([3, 4]);
        expect($result->toArray())->toBe([1, 2, 3, 4]);
    });

    it('can get unique values', function () {
        $result = (new Arr([1, 2, 2, 3, 3]))->unique();
        expect($result->toArray())->toBe([0 => 1, 1 => 2, 3 => 3]);
    });

    it('can reverse array', function () {
        $result = (new Arr([1, 2, 3]))->reverse();
        expect($result->toArray())->toBe([3, 2, 1]);
    });

    it('can sort array', function () {
        $result = (new Arr([3, 1, 2]))->sort();
        expect($result->toArray())->toBe([1, 2, 3]);
    });

    it('can sort array with callback', function () {
        $result = (new Arr([3, 1, 2]))->sort(fn($a, $b) => $b <=> $a);
        expect($result->toArray())->toBe([3, 2, 1]);
    });

    it('can filter array', function () {
        $result = (new Arr([1, 2, 3, 4]))->filter(fn($x) => $x > 2);
        expect($result->toArray())->toBe([2 => 3, 3 => 4]);
    });

    it('can filter array with default filter', function () {
        $result = (new Arr([0, 1, '', 'hello', false, null, []]))->filter();
        expect($result->toArray())->toBe([1 => 1, 3 => 'hello']);
    });

    it('can map array', function () {
        $result = (new Arr([1, 2, 3]))->map(fn($x) => $x * 2);
        expect($result->toArray())->toBe([2, 4, 6]);
    });

    it('can map with key', function () {
        $result = (new Arr(['a' => 1, 'b' => 2]))->map(fn($value, $key) => $key . $value);
        expect($result->toArray())->toBe(['a' => 'a1', 'b' => 'b2']);
    });

    it('can reduce array', function () {
        $result = (new Arr([1, 2, 3, 4]))->reduce(fn($carry, $item) => $carry + $item, 0);
        expect($result)->toBe(10);
    });

    it('can iterate with each', function () {
        $result = [];
        (new Arr([1, 2, 3]))->each(function($value) use (&$result) {
            $result[] = $value * 2;
        });
        expect($result)->toBe([2, 4, 6]);
    });

    it('can find element', function () {
        $result = (new Arr([1, 2, 3, 4]))->find(fn($x) => $x > 2);
        expect($result)->toBe(3);
        
        $notFound = (new Arr([1, 2]))->find(fn($x) => $x > 5);
        expect($notFound)->toBeNull();
    });

    it('can find index', function () {
        $result = (new Arr([1, 2, 3, 4]))->findIndex(fn($x) => $x > 2);
        expect($result)->toBe(2);
        
        $notFound = (new Arr([1, 2]))->findIndex(fn($x) => $x > 5);
        expect($notFound)->toBe(-1);
    });

    it('can check some condition', function () {
        expect((new Arr([1, 2, 3]))->some(fn($x) => $x > 2))->toBeTrue();
        expect((new Arr([1, 2]))->some(fn($x) => $x > 5))->toBeFalse();
    });

    it('can check every condition', function () {
        expect((new Arr([2, 3, 4]))->every(fn($x) => $x > 1))->toBeTrue();
        expect((new Arr([1, 2, 3]))->every(fn($x) => $x > 1))->toBeFalse();
    });

    it('can get keys', function () {
        $result = (new Arr(['a' => 1, 'b' => 2]))->keys();
        expect($result->toArray())->toBe(['a', 'b']);
    });

    it('can get values', function () {
        $result = (new Arr(['a' => 1, 'b' => 2]))->values();
        expect($result->toArray())->toBe([1, 2]);
    });

    it('can flip array', function () {
        $result = (new Arr(['a' => 1, 'b' => 2]))->flip();
        expect($result->toArray())->toBe([1 => 'a', 2 => 'b']);
    });

    it('can flip array with mixed values', function () {
        $result = (new Arr(['a', 'b', 1.5, new stdClass()]))->flip();
        expect($result->toArray())->toBe(['a' => 0, 'b' => 1]); // only strings and ints
    });

    it('can chunk array', function () {
        $result = (new Arr([1, 2, 3, 4, 5]))->chunk(2);
        expect($result->toArray())->toBe([[1, 2], [3, 4], [5]]);
    });

    it('throws exception for invalid chunk size', function () {
        expect(fn() => (new Arr([1, 2, 3]))->chunk(0))
            ->toThrow(InvalidArgumentException::class, 'Chunk size must be at least 1');
    });

    it('can join elements', function () {
        $result = (new Arr(['a', 'b', 'c']))->join(',');
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('a,b,c');
    });

    it('can join without separator', function () {
        $result = (new Arr(['a', 'b', 'c']))->join();
        expect($result->get())->toBe('abc');
    });

    it('can group by key', function () {
        $arr = new Arr([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'veggie', 'name' => 'carrot']
        ]);
        
        $result = $arr->groupBy(fn($item) => $item['type']);
        $grouped = $result->toArray();
        
        expect($grouped['fruit'])->toHaveCount(2);
        expect($grouped['veggie'])->toHaveCount(1);
    });

    it('can flatten array', function () {
        $result = (new Arr([1, [2, 3], [4, [5]]]))->flatten();
        expect($result->toArray())->toBe([1, 2, 3, 4, [5]]);
    });

    it('can flatten array with depth', function () {
        $result = (new Arr([1, [2, [3, [4]]]]))->flatten(2);
        expect($result->toArray())->toBe([1, 2, 3, [4]]);
    });

    it('can get minimum value', function () {
        expect((new Arr([3, 1, 2]))->min())->toBe(1);
    });

    it('throws exception for min on empty array', function () {
        expect(fn() => (new Arr([]))->min())
            ->toThrow(InvalidArgumentException::class, 'Cannot get minimum of empty array');
    });

    it('can get maximum value', function () {
        expect((new Arr([3, 1, 2]))->max())->toBe(3);
    });

    it('throws exception for max on empty array', function () {
        expect(fn() => (new Arr([]))->max())
            ->toThrow(InvalidArgumentException::class, 'Cannot get maximum of empty array');
    });

    it('can get sum', function () {
        expect((new Arr([1, 2, 3]))->sum())->toBe(6);
        expect((new Arr([]))->sum())->toBe(0);
    });

    it('can get average', function () {
        expect((new Arr([1, 2, 3, 4]))->average())->toBe(2.5);
        expect((new Arr([]))->average())->toBe(0.0);
    });
});