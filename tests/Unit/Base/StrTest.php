<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Str;

describe('Str', function (): void {
    it('can be constructed and get value', function (): void {
        $str = new Str('hello');
        expect($str->get())->toBe('hello');
    });

    it('implements Stringable', function (): void {
        $str = new Str('hello');
        expect((string) $str)->toBe('hello');
    });

    it('can get length', function (): void {
        expect(new Str('hello')->length())->toBe(5);
        expect(new Str('')->length())->toBe(0);
        expect(new Str('тест')->length())->toBe(4); // multibyte
    });

    it('can check if empty', function (): void {
        expect(new Str('')->isEmpty())->toBeTrue();
        expect(new Str('hello')->isEmpty())->toBeFalse();
    });

    it('can check if not empty', function (): void {
        expect(new Str('')->isNotEmpty())->toBeFalse();
        expect(new Str('hello')->isNotEmpty())->toBeTrue();
    });

    it('can trim whitespace', function (): void {
        expect(new Str('  hello  ')->trim()->get())->toBe('hello');
        expect(new Str('xxhelloxx')->trim('x')->get())->toBe('hello');
    });

    it('can trim left', function (): void {
        expect(new Str('  hello  ')->ltrim()->get())->toBe('hello  ');
        expect(new Str('xxhelloxx')->ltrim('x')->get())->toBe('helloxx');
    });

    it('can trim right', function (): void {
        expect(new Str('  hello  ')->rtrim()->get())->toBe('  hello');
        expect(new Str('xxhelloxx')->rtrim('x')->get())->toBe('xxhello');
    });

    it('can convert to uppercase', function (): void {
        expect(new Str('hello')->upper()->get())->toBe('HELLO');
        expect(new Str('тест')->upper()->get())->toBe('ТЕСТ');
    });

    it('can convert to lowercase', function (): void {
        expect(new Str('HELLO')->lower()->get())->toBe('hello');
        expect(new Str('ТЕСТ')->lower()->get())->toBe('тест');
    });

    it('can capitalize', function (): void {
        expect(new Str('hello world')->capitalize()->get())->toBe('Hello World');
    });

    it('can check if contains substring', function (): void {
        expect(new Str('hello world')->contains('world'))->toBeTrue();
        expect(new Str('hello world')->contains('foo'))->toBeFalse();
    });

    it('can check if starts with', function (): void {
        expect(new Str('hello world')->startsWith('hello'))->toBeTrue();
        expect(new Str('hello world')->startsWith('world'))->toBeFalse();
    });

    it('can check if ends with', function (): void {
        expect(new Str('hello world')->endsWith('world'))->toBeTrue();
        expect(new Str('hello world')->endsWith('hello'))->toBeFalse();
    });

    it('can replace text', function (): void {
        expect(new Str('hello world')->replace('world', 'universe')->get())
            ->toBe('hello universe');
    });

    it('can replace all text from array', function (): void {
        $str = new Str('hello world');
        $result = $str->replaceAll(['hello' => 'hi', 'world' => 'universe']);
        expect($result->get())->toBe('hi universe');
    });

    it('can split string', function (): void {
        $arr = new Str('a,b,c')->split(',');
        expect($arr)->toBeInstanceOf(Arr::class);
        expect($arr->get())->toBe(['a', 'b', 'c']);
    });

    it('can split string with limit', function (): void {
        $arr = new Str('a,b,c,d')->split(',', 2);
        expect($arr->get())->toBe(['a', 'b,c,d']);
    });

    it('throws exception for empty delimiter', function (): void {
        expect(fn(): \PrettyPhp\Base\Arr => new Str('test')->split(''))
            ->toThrow(InvalidArgumentException::class, 'Delimiter cannot be empty');
    });

    it('can get substring', function (): void {
        expect(new Str('hello')->substring(1)->get())->toBe('ello');
        expect(new Str('hello')->substring(1, 3)->get())->toBe('ell');
        expect(new Str('hello')->substring(10)->get())->toBe('');
    });

    it('can find index of substring', function (): void {
        expect(new Str('hello world')->indexOf('world'))->toBe(6);
        expect(new Str('hello world')->indexOf('foo'))->toBe(-1);
        expect(new Str('hello world')->indexOf('o', 5))->toBe(7);
    });

    it('can find last index of substring', function (): void {
        expect(new Str('hello world')->lastIndexOf('o'))->toBe(7);
        expect(new Str('hello world')->lastIndexOf('foo'))->toBe(-1);
    });

    it('can repeat string', function (): void {
        expect(new Str('ab')->repeat(3)->get())->toBe('ababab');
        expect(new Str('test')->repeat(0)->get())->toBe('');
    });

    it('can reverse string', function (): void {
        expect(new Str('hello')->reverse()->get())->toBe('olleh');
    });

    it('can pad left', function (): void {
        expect(new Str('5')->padLeft(3, '0')->get())->toBe('005');
        expect(new Str('hello')->padLeft(8)->get())->toBe('   hello');
    });

    it('can pad right', function (): void {
        expect(new Str('5')->padRight(3, '0')->get())->toBe('500');
        expect(new Str('hello')->padRight(8)->get())->toBe('hello   ');
    });

    it('can pad both sides', function (): void {
        expect(new Str('hi')->padBoth(6)->get())->toBe('  hi  ');
        expect(new Str('test')->padBoth(8, '-')->get())->toBe('--test--');
    });

    it('can check if alphabetic', function (): void {
        expect(new Str('hello')->isAlpha())->toBeTrue();
        expect(new Str('hello123')->isAlpha())->toBeFalse();
        expect(new Str('')->isAlpha())->toBeFalse();
    });

    it('can check if numeric', function (): void {
        expect(new Str('123')->isNumeric())->toBeTrue();
        expect(new Str('12.34')->isNumeric())->toBeTrue();
        expect(new Str('hello')->isNumeric())->toBeFalse();
        expect(new Str('')->isNumeric())->toBeFalse();
    });

    it('can check if alphanumeric', function (): void {
        expect(new Str('hello123')->isAlphaNumeric())->toBeTrue();
        expect(new Str('hello-123')->isAlphaNumeric())->toBeFalse();
        expect(new Str('')->isAlphaNumeric())->toBeFalse();
    });

    it('can convert to array', function (): void {
        $arr = new Str('abc')->toArray();
        expect($arr)->toBeInstanceOf(Arr::class);
        expect($arr->get())->toBe(['a', 'b', 'c']);
    });

    it('can convert empty string to array', function (): void {
        $arr = new Str('')->toArray();
        expect($arr->get())->toBe([]);
    });

    it('can match regex pattern', function (): void {
        $result = new Str('hello123')->match('/\d+/');
        expect($result)->toBeInstanceOf(Arr::class);
        expect($result->get())->toBe(['123']);
    });

    it('returns null for non-matching pattern', function (): void {
        $result = new Str('hello')->match('/\d+/');
        expect($result)->toBeNull();
    });

    it('can match all regex patterns', function (): void {
        $arr = new Str('hello123world456')->matchAll('/\d+/');
        expect($arr)->toBeInstanceOf(Arr::class);
        expect($arr->get())->toBe([['123'], ['456']]);
    });

    it('returns empty array for no matches in matchAll', function (): void {
        $arr = new Str('hello')->matchAll('/\d+/');
        expect($arr->get())->toBe([]);
    });

    // Enhanced Methods Tests

    it('can normalize unicode string', function (): void {
        $str = new Str('café');
        $normalized = $str->normalizeUnicode();
        expect($normalized)->toBeInstanceOf(Str::class);
        expect($normalized->get())->toBe('café');
    });

    it('can generate URL slug', function (): void {
        expect(new Str('Hello World')->slug()->get())->toBe('hello-world');
        expect(new Str('Hello   World')->slug()->get())->toBe('hello-world');
        expect(new Str('Hello_World')->slug()->get())->toBe('hello-world');
        expect(new Str('café résumé')->slug()->get())->toBe('cafe-resume');
        expect(new Str('Hello World')->slug('_')->get())->toBe('hello_world');
    });

    it('can truncate string with ellipsis', function (): void {
        expect(new Str('Hello World')->truncate(5)->get())->toBe('He...');
        expect(new Str('Hello')->truncate(10)->get())->toBe('Hello');
        expect(new Str('Hello World')->truncate(8, '...')->get())->toBe('Hello...');
        expect(new Str('Hello World')->truncate(8, '…')->get())->toBe('Hello W…');
    });

    it('can convert to snake_case', function (): void {
        expect(new Str('HelloWorld')->toSnakeCase()->get())->toBe('hello_world');
        expect(new Str('helloWorld')->toSnakeCase()->get())->toBe('hello_world');
        expect(new Str('hello-world')->toSnakeCase()->get())->toBe('hello_world');
        expect(new Str('hello world')->toSnakeCase()->get())->toBe('hello_world');
        expect(new Str('HTTPResponse')->toSnakeCase()->get())->toBe('h_t_t_p_response');
    });

    it('can convert to camelCase', function (): void {
        expect(new Str('hello_world')->toCamelCase()->get())->toBe('helloWorld');
        expect(new Str('hello-world')->toCamelCase()->get())->toBe('helloWorld');
        expect(new Str('hello world')->toCamelCase()->get())->toBe('helloWorld');
        expect(new Str('HelloWorld')->toCamelCase()->get())->toBe('helloworld');
    });

    it('can convert to PascalCase', function (): void {
        expect(new Str('hello_world')->toPascalCase()->get())->toBe('HelloWorld');
        expect(new Str('hello-world')->toPascalCase()->get())->toBe('HelloWorld');
        expect(new Str('hello world')->toPascalCase()->get())->toBe('HelloWorld');
        expect(new Str('helloWorld')->toPascalCase()->get())->toBe('Helloworld');
    });

    it('can convert to kebab-case', function (): void {
        expect(new Str('HelloWorld')->toKebabCase()->get())->toBe('hello-world');
        expect(new Str('helloWorld')->toKebabCase()->get())->toBe('hello-world');
        expect(new Str('hello_world')->toKebabCase()->get())->toBe('hello-world');
        expect(new Str('hello world')->toKebabCase()->get())->toBe('hello-world');
        expect(new Str('HTTPResponse')->toKebabCase()->get())->toBe('h-t-t-p-response');
    });

    it('can calculate levenshtein distance', function (): void {
        expect(new Str('kitten')->levenshtein('sitting'))->toBe(3);
        expect(new Str('hello')->levenshtein('hello'))->toBe(0);
        expect(new Str('hello')->levenshtein('world'))->toBe(4);
    });

    it('can calculate string similarity', function (): void {
        $similarity = new Str('hello')->similarity('hello');
        expect($similarity)->toBe(100.0);

        $similarity = new Str('hello')->similarity('hallo');
        expect($similarity)->toBeGreaterThan(50.0);

        $similarity = new Str('hello')->similarity('world');
        expect($similarity)->toBeLessThan(50.0);
    });
});
