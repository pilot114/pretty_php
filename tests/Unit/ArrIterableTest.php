<?php

use PrettyPhp\Base\Arr;

describe('Arr with Iterable', function (): void {
    it('can create Arr from array', function (): void {
        $arr = new Arr([1, 2, 3]);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can create Arr from ArrayIterator', function (): void {
        $iterator = new ArrayIterator([1, 2, 3]);
        $arr = new Arr($iterator);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can create Arr from Generator', function (): void {
        $generator = function () {
            yield 1;
            yield 2;
            yield 3;
        };

        $arr = new Arr($generator());
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can create Arr from Generator with keys', function (): void {
        $generator = function () {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'c' => 3;
        };

        $arr = new Arr($generator());
        expect($arr->toArray())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
    });

    it('can create Arr from SplFixedArray', function (): void {
        $splArray = SplFixedArray::fromArray([1, 2, 3]);
        $arr = new Arr($splArray);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can merge with ArrayIterator', function (): void {
        $arr = new Arr([1, 2]);
        $iterator = new ArrayIterator([3, 4]);
        $merged = $arr->merge($iterator);
        expect($merged->toArray())->toBe([1, 2, 3, 4]);
    });

    it('can merge with Generator', function (): void {
        $arr = new Arr([1, 2]);
        $generator = function () {
            yield 3;
            yield 4;
        };

        $merged = $arr->merge($generator());
        expect($merged->toArray())->toBe([1, 2, 3, 4]);
    });

    it('can merge with associative Generator', function (): void {
        $arr = new Arr(['a' => 1, 'b' => 2]);
        $generator = function () {
            yield 'c' => 3;
            yield 'd' => 4;
        };

        $merged = $arr->merge($generator());
        expect($merged->toArray())->toBe(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
    });

    it('can use arr() function with ArrayIterator', function (): void {
        $iterator = new ArrayIterator([1, 2, 3]);
        $arr = arr($iterator);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('can use arr() function with Generator', function (): void {
        $generator = function () {
            yield 'x' => 10;
            yield 'y' => 20;
        };

        $arr = arr($generator());
        expect($arr->toArray())->toBe(['x' => 10, 'y' => 20]);
    });

    it('can use from() static method', function (): void {
        $iterator = new ArrayIterator([1, 2, 3]);
        $arr = Arr::from($iterator);
        expect($arr->toArray())->toBe([1, 2, 3]);
    });

    it('preserves keys from iterable', function (): void {
        $data = ['first' => 1, 'second' => 2, 'third' => 3];
        $iterator = new ArrayIterator($data);
        $arr = new Arr($iterator);

        expect($arr->toArray())->toBe($data);
        expect($arr->keys()->toArray())->toBe(['first', 'second', 'third']);
    });

    it('works with range as iterable', function (): void {
        // range() returns an array, but we can test it conceptually
        $arr = new Arr(range(1, 5));
        expect($arr->toArray())->toBe([1, 2, 3, 4, 5]);
    });
});
