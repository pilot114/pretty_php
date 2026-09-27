<?php

use PrettyPhp\Base\ArraySessionStorage;
use PrettyPhp\Base\Session;
use PrettyPhp\Base\Arr;

mutates(\PrettyPhp\Base\Session::class, \PrettyPhp\Base\ArraySessionStorage::class, \PrettyPhp\Base\NativeSessionStorage::class);

describe('Session', function (): void {
    beforeEach(function (): void {
        // Clean up any existing session
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    afterEach(function (): void {
        // Clean up after each test
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    it('can start a session', function (): void {
        $result = Session::start();
        expect($result)->toBeTrue();
        expect(Session::isActive())->toBeTrue();
    });

    it('can check session status', function (): void {
        expect(Session::isNone())->toBeTrue();
        Session::start();
        expect(Session::isActive())->toBeTrue();
        expect(Session::isNone())->toBeFalse();
    });

    it('can set and get values', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(Session::get('name'))->toBe('John');
    });

    it('can get default value for missing key', function (): void {
        Session::start();
        expect(Session::get('missing', 'default'))->toBe('default');
    });

    it('can check if key exists', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(Session::has('name'))->toBeTrue();
        expect(Session::has('missing'))->toBeFalse();
    });

    it('can remove a key', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(Session::has('name'))->toBeTrue();
        Session::remove('name');
        expect(Session::has('name'))->toBeFalse();
    });

    it('can get all session data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::set('age', 30);
        $all = Session::all();
        expect($all)->toBeArray();
        expect($all['name'])->toBe('John');
        expect($all['age'])->toBe(30);
    });

    it('can clear all data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::set('age', 30);
        Session::clear();
        expect(Session::all())->toBe([]);
    });

    it('can replace all data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::replace(['city' => 'NYC', 'country' => 'USA']);
        expect(Session::has('name'))->toBeFalse();
        expect(Session::get('city'))->toBe('NYC');
        expect(Session::get('country'))->toBe('USA');
    });

    it('can pull a value (get and remove)', function (): void {
        Session::start();
        Session::set('name', 'John');
        $value = Session::pull('name');
        expect($value)->toBe('John');
        expect(Session::has('name'))->toBeFalse();
    });

    it('can increment numeric values', function (): void {
        Session::start();
        Session::set('counter', 5);
        $result = Session::increment('counter');
        expect($result)->toBe(6);
        expect(Session::get('counter'))->toBe(6);
    });

    it('can increment with custom amount', function (): void {
        Session::start();
        Session::set('counter', 5);
        Session::increment('counter', 3);
        expect(Session::get('counter'))->toBe(8);
    });

    it('can decrement numeric values', function (): void {
        Session::start();
        Session::set('counter', 5);
        $result = Session::decrement('counter');
        expect($result)->toBe(4);
        expect(Session::get('counter'))->toBe(4);
    });

    it('can push values to array', function (): void {
        Session::start();
        Session::set('items', ['a', 'b']);
        Session::push('items', 'c');
        expect(Session::get('items'))->toBe(['a', 'b', 'c']);
    });

    it('can push to non-existing key', function (): void {
        Session::start();
        Session::push('items', 'a');
        expect(Session::get('items'))->toBe(['a']);
    });

    it('can pop values from array', function (): void {
        Session::start();
        Session::set('items', ['a', 'b', 'c']);
        $value = Session::pop('items');
        expect($value)->toBe('c');
        expect(Session::get('items'))->toBe(['a', 'b']);
    });

    it('can set flash data', function (): void {
        Session::start();
        Session::flash('message', 'Success!');
        expect(Session::hasFlash('message'))->toBeTrue();
    });

    it('can get flash data', function (): void {
        Session::start();
        Session::flash('message', 'Success!');
        $value = Session::getFlash('message');
        expect($value)->toBe('Success!');
        expect(Session::hasFlash('message'))->toBeFalse();
    });

    it('can regenerate session id', function (): void {
        Session::start();
        $oldId = Session::id();
        Session::regenerateId();
        $newId = Session::id();
        expect($newId)->not->toBe($oldId);
    });

    it('can get session name', function (): void {
        Session::start();
        $name = Session::name();
        expect($name)->toBeString();
        expect($name)->not->toBe('');
    });

    it('can get cookie params', function (): void {
        Session::start();
        $params = Session::getCookieParams();
        expect($params)->toBeArray();
        expect($params)->toHaveKey('lifetime');
    });

    it('can convert to Arr', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::set('age', 30);
        $arr = Session::toArr();
        expect($arr)->toBeInstanceOf(Arr::class);
        expect($arr->count())->toBeGreaterThanOrEqual(2);
    });

    it('can check if empty', function (): void {
        Session::start();
        Session::clear();
        expect(Session::isEmpty())->toBeTrue();
        Session::set('name', 'John');
        expect(Session::isEmpty())->toBeFalse();
    });

    it('can check if not empty', function (): void {
        Session::start();
        Session::clear();
        expect(Session::isNotEmpty())->toBeFalse();
        Session::set('name', 'John');
        expect(Session::isNotEmpty())->toBeTrue();
    });

    it('helper function works without arguments', function (): void {
        Session::start();
        Session::set('name', 'John');
        $all = session();
        expect($all)->toBeArray();
        expect(Session::get('name'))->toBe('John');
    });

    it('helper function works with get', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(session('name'))->toBe('John');
    });

    it('helper function works with set', function (): void {
        Session::start();
        session('name', 'Jane');
        expect(Session::get('name'))->toBe('Jane');
    });

    it('can encode session data', function (): void {
        Session::start();
        Session::set('name', 'John');
        $encoded = Session::encode();
        expect($encoded)->toBeString();
        expect($encoded)->not->toBe('');
    });

    it('can decode session data', function (): void {
        Session::start();
        Session::set('name', 'John');
        $encoded = Session::encode();
        expect($encoded)->toBeString();
        if ($encoded !== false) {
            Session::clear();
            $result = Session::decode($encoded);
            expect($result)->toBeTrue();
            expect(Session::get('name'))->toBe('John');
        }
    });
});

describe('Session with ArraySessionStorage', function (): void {
    beforeEach(function (): void {
        Session::useStorage(new ArraySessionStorage());
    });

    afterEach(function (): void {
        Session::useStorage(null);
    });

    it('can set and get values', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(Session::get('name'))->toBe('John');
    });

    it('returns default for missing key', function (): void {
        Session::start();
        expect(Session::get('missing', 'default'))->toBe('default');
    });

    it('can check if key exists', function (): void {
        Session::start();
        Session::set('name', 'John');
        expect(Session::has('name'))->toBeTrue();
        expect(Session::has('missing'))->toBeFalse();
    });

    it('can remove a key', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::remove('name');
        expect(Session::has('name'))->toBeFalse();
    });

    it('can get all session data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::set('age', 30);
        $all = Session::all();
        expect($all['name'])->toBe('John');
        expect($all['age'])->toBe(30);
    });

    it('can clear all data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::clear();
        expect(Session::all())->toBe([]);
    });

    it('can replace all data', function (): void {
        Session::start();
        Session::set('name', 'John');
        Session::replace(['city' => 'NYC']);
        expect(Session::has('name'))->toBeFalse();
        expect(Session::get('city'))->toBe('NYC');
    });

    it('can flash and get flash data', function (): void {
        Session::start();
        Session::flash('message', 'Success!');
        expect(Session::hasFlash('message'))->toBeTrue();
        $value = Session::getFlash('message');
        expect($value)->toBe('Success!');
        expect(Session::hasFlash('message'))->toBeFalse();
    });

    it('can keep flash data', function (): void {
        Session::start();
        Session::flash('message', 'Hello');
        Session::ageFlashData();
        // After aging, _flash should move to _old_flash then be removed
        // But if we keep it, it should come back to _flash
        // Let's set up the scenario properly
        Session::useStorage(new ArraySessionStorage([
            '_old_flash' => ['message' => 'Hello'],
            '_flash' => [],
        ]));
        Session::keepFlash('message');
        expect(Session::hasFlash('message'))->toBeTrue();
    });

    it('can pull a value', function (): void {
        Session::start();
        Session::set('name', 'John');
        $value = Session::pull('name');
        expect($value)->toBe('John');
        expect(Session::has('name'))->toBeFalse();
    });

    it('can push and pop from arrays', function (): void {
        Session::start();
        Session::set('items', ['a', 'b']);
        Session::push('items', 'c');
        expect(Session::get('items'))->toBe(['a', 'b', 'c']);
        $value = Session::pop('items');
        expect($value)->toBe('c');
        expect(Session::get('items'))->toBe(['a', 'b']);
    });

    it('can increment and decrement', function (): void {
        Session::start();
        Session::set('counter', 5);
        expect(Session::increment('counter'))->toBe(6);
        expect(Session::decrement('counter', 2))->toBe(4);
    });

    it('can convert to Arr', function (): void {
        Session::start();
        Session::set('name', 'John');
        $arr = Session::toArr();
        expect($arr)->toBeInstanceOf(Arr::class);
    });

    it('can check empty state', function (): void {
        Session::start();
        expect(Session::isEmpty())->toBeTrue();
        Session::set('name', 'John');
        expect(Session::isEmpty())->toBeFalse();
        expect(Session::isNotEmpty())->toBeTrue();
    });
});

describe('Session lifecycle and configuration', function (): void {
    beforeEach(function (): void {
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    afterEach(function (): void {
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    it('reports disabled state', function (): void {
        expect(Session::isDisabled())->toBeFalse();
    });

    it('returns false for operations on inactive session', function (): void {
        expect(Session::destroy())->toBeFalse();
        expect(Session::abort())->toBeFalse();
        expect(Session::reset())->toBeFalse();
        expect(Session::regenerateId())->toBeFalse();
        expect(Session::encode())->toBeFalse();
        expect(Session::decode('a|i:1;'))->toBeFalse();
    });

    it('destroys, unsets, aborts, resets and commits active session', function (): void {
        Session::start();
        Session::set('a', 1);
        Session::unset();
        expect(Session::all())->toBe([]);
        expect(Session::reset())->toBeTrue();
        expect(Session::abort())->toBeTrue();

        Session::start();
        Session::commit();
        expect(Session::isActive())->toBeFalse();

        Session::start();
        expect(Session::destroy())->toBeTrue();
    });

    it('creates ids and changes id and name while inactive', function (): void {
        expect(Session::createId('pfx'))->toStartWith('pfx');

        $oldName = Session::name();
        expect(Session::name('CUSTOMSESS'))->toBe($oldName);
        expect(Session::name())->toBe('CUSTOMSESS');
        Session::name((string) $oldName);

        Session::id('abc123');
        expect(Session::id())->toBe('abc123');
        Session::id('');
    });

    it('gets and sets save path, module, cache limiter and cache expire', function (): void {
        $path = Session::savePath();
        expect(Session::savePath(sys_get_temp_dir()))->toBe($path);
        Session::savePath((string) $path);

        expect(Session::moduleName())->toBe('files');
        expect(Session::moduleName('files'))->toBe('files');

        $limiter = Session::cacheLimiter();
        expect(Session::cacheLimiter('private'))->toBe($limiter);
        expect(Session::cacheLimiter())->toBe('private');
        Session::cacheLimiter((string) $limiter);

        $expire = Session::cacheExpire();
        expect(Session::cacheExpire(60))->toBe($expire);
        expect(Session::cacheExpire())->toBe(60);
        Session::cacheExpire((int) $expire);
    });

    it('sets cookie params from scalars and options array', function (): void {
        $original = Session::getCookieParams();

        expect(Session::setCookieParams(100, '/app', 'example.com', true, true))->toBeTrue();
        expect(Session::getCookieParams())->toMatchArray([
            'lifetime' => 100, 'path' => '/app', 'domain' => 'example.com', 'secure' => true, 'httponly' => true,
        ]);

        expect(Session::setCookieParams(['lifetime' => 50, 'samesite' => 'Strict']))->toBeTrue();
        expect(Session::getCookieParams()['samesite'])->toBe('Strict');

        expect(Session::setCookieParams(0))->toBeTrue();
        Session::setCookieParams($original);
    });

    it('registers save handler and shutdown function', function (): void {
        $handler = new \SessionHandler();
        expect(Session::setSaveHandler($handler, false))->toBeTrue();
        Session::registerShutdown();
        Session::moduleName('files');
        expect(Session::moduleName())->toBe('files');
    });

    it('runs garbage collection on active session', function (): void {
        Session::start();
        expect(Session::gc())->toBeInt();
    });

    it('manages timeout and expiration', function (): void {
        $original = Session::getTimeout();
        Session::setTimeout(1234);
        expect(Session::getTimeout())->toBe(1234);
        Session::setTimeout($original);

        expect(Session::hasExpired())->toBeFalse();
        expect(Session::hasExpired())->toBeFalse();

        Session::set('_last_activity', time() - 100);
        expect(Session::hasExpired(10))->toBeTrue();

        Session::set('_last_activity', 'invalid');
        expect(Session::hasExpired(10))->toBeTrue();
    });
});

describe('Session auto-start and malformed data', function (): void {
    beforeEach(function (): void {
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    afterEach(function (): void {
        if (Session::isActive()) {
            Session::clear();
            Session::close();
        }
    });

    it('starts session automatically on access', function (string $method, array $args): void {
        expect(Session::isActive())->toBeFalse();
        Session::$method(...$args);
        expect(Session::isActive())->toBeTrue();
    })->with([
        ['get', ['key']],
        ['set', ['key', 1]],
        ['has', ['key']],
        ['remove', ['key']],
        ['all', []],
        ['replace', [[]]],
        ['clear', []],
        ['flash', ['key', 1]],
        ['getFlash', ['key']],
        ['hasFlash', ['key']],
        ['keepFlash', ['key']],
        ['ageFlashData', []],
    ]);

    it('recovers from non-array flash data', function (): void {
        Session::set('_flash', 'broken');
        expect(Session::getFlash('key', 'default'))->toBe('default');
        expect(Session::hasFlash('key'))->toBeFalse();

        Session::flash('key', 'value');
        expect(Session::get('_flash'))->toBe(['key' => 'value']);

        Session::set('_old_flash', ['key' => 'old']);
        Session::set('_flash', 'broken');
        Session::keepFlash('key');
        expect(Session::get('_flash'))->toBe(['key' => 'old']);

        Session::set('_old_flash', 'broken');
        Session::keepFlash(['key']);
        expect(Session::get('_old_flash'))->toBe('broken');
    });

    it('handles non-numeric and non-array values', function (): void {
        Session::set('counter', 'abc');
        expect(Session::increment('counter'))->toBe(1);

        Session::set('list', 'scalar');
        Session::push('list', 'new');
        expect(Session::get('list'))->toBe(['scalar', 'new']);

        expect(Session::pop('missing'))->toBeNull();
    });
});
