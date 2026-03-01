<?php

use PrettyPhp\Curl\HttpMethod;

describe('HttpMethod', function (): void {
    it('has all standard HTTP methods', function (): void {
        $cases = HttpMethod::cases();
        expect($cases)->toHaveCount(9);
    });

    it('has correct string values', function (): void {
        expect(HttpMethod::GET->value)->toBe('GET');
        expect(HttpMethod::POST->value)->toBe('POST');
        expect(HttpMethod::PUT->value)->toBe('PUT');
        expect(HttpMethod::DELETE->value)->toBe('DELETE');
        expect(HttpMethod::PATCH->value)->toBe('PATCH');
        expect(HttpMethod::HEAD->value)->toBe('HEAD');
        expect(HttpMethod::OPTIONS->value)->toBe('OPTIONS');
        expect(HttpMethod::TRACE->value)->toBe('TRACE');
        expect(HttpMethod::CONNECT->value)->toBe('CONNECT');
    });

    it('can be created from string value', function (): void {
        expect(HttpMethod::from('GET'))->toBe(HttpMethod::GET);
        expect(HttpMethod::from('POST'))->toBe(HttpMethod::POST);
    });

    it('returns null for invalid method with tryFrom', function (): void {
        expect(HttpMethod::tryFrom('INVALID'))->toBeNull();
    });
});
