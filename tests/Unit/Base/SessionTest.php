<?php

use PrettyPhp\Base\Session;
use PrettyPhp\Base\Arr;

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
