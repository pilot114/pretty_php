<?php

declare(strict_types=1);

use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;
use PrettyPhp\Exception\PathException;

mutates(Path::class, Str::class);

describe('Path details', function (): void {
    beforeEach(function (): void {
        $this->dir = sys_get_temp_dir() . '/pretty_php_path_details_' . uniqid();
        mkdir($this->dir . '/sub/deep', 0755, true);
        file_put_contents($this->dir . '/root.txt', '12345');
        file_put_contents($this->dir . '/sub/a.txt', '123');
        file_put_contents($this->dir . '/sub/deep/b.txt', '1');
        file_put_contents($this->dir . '/sub/deep/c.log', '12');
    });

    afterEach(function (): void {
        removeDirectory($this->dir);
    });

    it('recognises absolute and Windows paths', function (string $path, bool $absolute, bool $windows): void {
        expect(new Path($path)->isAbsolute())->toBe($absolute);
        expect(new Path($path)->isWindowsPath())->toBe($windows);
    })->with([
        ['', false, false],
        ['/usr', true, false],
        ['C:\\dir', true, true],
        ['C:', false, true],
        ['1:\\x', true, false],
        ['relative', false, false],
    ]);

    it('computes relative paths', function (): void {
        expect(new Path('/a/b/c')->relative('/a/b/c')->get())->toBe('.');
        expect(new Path('/a/b/c')->relative('/a/x')->get())->toBe('../../x');
        expect(new Path('/a/b')->relative('/x/y')->get())->toBe('../../x/y');
    });

    it('lists files with sequential keys', function (): void {
        expect(new Path($this->dir . '/sub')->listFiles()->get())->toBe(['a.txt', 'deep']);
        expect(fn (): \PrettyPhp\Base\Arr => new Path($this->dir . '/root.txt')->listFiles())
            ->toThrow(PathException::class, 'Path is not a directory: ' . $this->dir . '/root.txt');
    });

    it('sums sizes of files and directories', function (): void {
        expect(new Path($this->dir)->size())->toBe(11);
        expect(new Path($this->dir . '/root.txt')->size())->toBe(5);
        expect(fn (): int => new Path($this->dir . '/missing')->size())
            ->toThrow(PathException::class, 'Path does not exist: ' . $this->dir . '/missing');
    });

    it('matches recursive patterns including the root and a prefix', function (): void {
        $txt = new Path($this->dir)->globRecursive('**/*.txt')->get();
        sort($txt);
        expect($txt)->toBe([$this->dir . '/root.txt', $this->dir . '/sub/a.txt', $this->dir . '/sub/deep/b.txt']);

        $prefixed = new Path($this->dir)->globRecursive('sub/deep/**/*.log')->get();
        expect($prefixed)->toBe([$this->dir . '/sub/deep/c.log']);

        $all = new Path($this->dir . '/sub')->globRecursive('**')->get();
        sort($all);
        expect($all)->toBe([
            $this->dir . '/sub/a.txt',
            $this->dir . '/sub/deep',
            $this->dir . '/sub/deep/b.txt',
            $this->dir . '/sub/deep/c.log',
        ]);

        expect(new Path($this->dir)->globRecursive('missing/**')->get())->toBe([]);
        expect(new Path($this->dir)->globRecursive('*.none')->get())->toBe([]);
    });

    it('normalizes separators', function (): void {
        expect(new Path('a\\b/c')->normalizePathSeparators()->get())->toBe('a/b/c');
    });

    it('names the path in link and realpath errors', function (): void {
        expect(fn (): \PrettyPhp\Base\Path => new Path($this->dir)->readLink())
            ->toThrow(PathException::class, 'Path is not a symbolic link: ' . $this->dir);
        expect(fn (): \PrettyPhp\Base\Path => new Path($this->dir . '/missing')->realPath())
            ->toThrow(PathException::class, 'Unable to resolve real path: ' . $this->dir . '/missing');
    });
});

describe('Str details', function (): void {
    it('applies replacements in order', function (): void {
        expect(new Str('a1b')->replaceAll(['a' => 'b', 'b' => 'c', 1 => 'x'])->get())->toBe('cxc');
    });

    it('searches from the start by default', function (): void {
        expect(new Str('abc')->indexOf('a'))->toBe(0);
        expect(new Str('abc')->lastIndexOf('a'))->toBe(0);
        expect(new Str('abc')->indexOf('z'))->toBe(-1);
        expect(new Str('abc')->lastIndexOf('z'))->toBe(-1);
    });

    it('checks numbers', function (): void {
        expect(new Str('')->isNumeric())->toBeFalse();
        expect(new Str('1.5')->isNumeric())->toBeTrue();
    });

    it('splits into characters', function (): void {
        expect(new Str('añb')->toArray()->get())->toBe(['a', 'ñ', 'b']);
    });

    it('truncates exactly at the boundary', function (): void {
        expect(new Str('abcde')->truncate(5)->get())->toBe('abcde');
        expect(new Str('abcdef')->truncate(5)->get())->toBe('ab...');
        expect(new Str('abcdef')->truncate(2)->get())->toBe('...');
    });

    it('converts between cases', function (string $input, string $snake, string $kebab, string $camel, string $pascal): void {
        $str = new Str($input);
        expect($str->toSnakeCase()->get())->toBe($snake);
        expect($str->toKebabCase()->get())->toBe($kebab);
        expect($str->toCamelCase()->get())->toBe($camel);
        expect($str->toPascalCase()->get())->toBe($pascal);
    })->with([
        [' hello world ', 'hello_world', 'hello-world', 'helloWorld', 'HelloWorld'],
        ['-foo bar', 'foo_bar', 'foo-bar', 'fooBar', 'FooBar'],
        ['already_snake-case', 'already_snake_case', 'already-snake-case', 'alreadySnakeCase', 'AlreadySnakeCase'],
        ['', '', '', '', ''],
    ]);

    it('builds slugs', function (): void {
        expect(new Str('  Héllo, Wörld!  ')->slug()->get())->toBe('hello-world');
        expect(new Str('a b')->slug('_')->get())->toBe('a_b');
    });
});
