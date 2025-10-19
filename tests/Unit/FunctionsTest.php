<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\File;
use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;

describe('Helper Functions', function (): void {
    it('str() creates a Str instance', function (): void {
        $str = str('hello');
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('hello');
    });

    it('arr() creates an Arr instance with array', function (): void {
        $result = arr([1, 2, 3]);
        expect($result)->toBeInstanceOf(Arr::class);
        expect($result->get())->toBe([1, 2, 3]);
    });

    it('arr() creates an Arr instance with default empty array', function (): void {
        $result = arr();
        expect($result)->toBeInstanceOf(Arr::class);
        expect($result->get())->toBe([]);
    });

    it('arr() can accept iterables', function (): void {
        $generator = function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        };

        $result = arr($generator());
        expect($result)->toBeInstanceOf(Arr::class);
        expect($result->get())->toBe([1, 2, 3]);
    });

    it('file() function is not defined because PHP has built-in file()', function (): void {
        // The file() helper function won't be defined because PHP already has file()
        // So we test the File class directly
        expect(function_exists('file'))->toBeTrue(); // PHP's built-in
        $result = new File('/tmp/test.txt');
        expect($result)->toBeInstanceOf(File::class);
    });

    it('path() creates a Path instance', function (): void {
        $path = path('/tmp/test');
        expect($path)->toBeInstanceOf(Path::class);
    });
});
