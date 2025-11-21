<?php

use PrettyPhp\Functional\Option;
use PrettyPhp\Functional\Result;

describe('Result', function (): void {
    describe('construction', function (): void {
        it('can create Ok value', function (): void {
            $result = Result::ok(42);
            expect($result->isOk())->toBeTrue();
            expect($result->isErr())->toBeFalse();
            expect($result->unwrap())->toBe(42);
        });

        it('can create Err value', function (): void {
            $result = Result::err('error');
            expect($result->isErr())->toBeTrue();
            expect($result->isOk())->toBeFalse();
            expect($result->unwrapErr())->toBe('error');
        });

        it('can create from callable', function (): void {
            $ok = Result::from(fn(): int => 42);
            expect($ok->isOk())->toBeTrue();
            expect($ok->unwrap())->toBe(42);

            $err = Result::from(function (): void {
                throw new \RuntimeException('error');
            });
            expect($err->isErr())->toBeTrue();
            expect($err->unwrapErr())->toBeInstanceOf(\RuntimeException::class);
        });

        it('can create Ok with helper function', function (): void {
            $result = ok('success');
            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe('success');
        });

        it('can create Err with helper function', function (): void {
            $result = err('failure');
            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toBe('failure');
        });
    });

    describe('map', function (): void {
        it('maps Ok values', function (): void {
            $result = Result::ok(5);
            $mapped = $result->map(fn($x): int => $x * 2);

            expect($mapped->isOk())->toBeTrue();
            expect($mapped->unwrap())->toBe(10);
        });

        it('does not map Err values', function (): void {
            $result = Result::err('error');
            $mapped = $result->map(fn($x) => $x * 2);

            expect($mapped->isErr())->toBeTrue();
            expect($mapped->unwrapErr())->toBe('error');
        });

        it('can chain multiple maps', function (): void {
            $result = Result::ok(5)
                ->map(fn($x): int => $x * 2)
                ->map(fn($x): int => $x + 3)
                ->map(fn($x): string => (string) $x);

            expect($result->unwrap())->toBe('13');
        });
    });

    describe('mapErr', function (): void {
        it('maps Err values', function (): void {
            $result = Result::err('error');
            $mapped = $result->mapErr(fn($e) => strtoupper($e));

            expect($mapped->isErr())->toBeTrue();
            expect($mapped->unwrapErr())->toBe('ERROR');
        });

        it('does not map Ok values', function (): void {
            $result = Result::ok(42);
            $mapped = $result->mapErr(fn($e) => strtoupper($e));

            expect($mapped->isOk())->toBeTrue();
            expect($mapped->unwrap())->toBe(42);
        });
    });

    describe('andThen', function (): void {
        it('chains Ok values', function (): void {
            $result = Result::ok(5);
            $chained = $result->andThen(fn($x): \PrettyPhp\Functional\Result => Result::ok($x * 2));

            expect($chained->isOk())->toBeTrue();
            expect($chained->unwrap())->toBe(10);
        });

        it('does not chain Err values', function (): void {
            $result = Result::err('error');
            $chained = $result->andThen(fn($x): \PrettyPhp\Functional\Result => Result::ok($x * 2));

            expect($chained->isErr())->toBeTrue();
            expect($chained->unwrapErr())->toBe('error');
        });

        it('can short-circuit on error', function (): void {
            $divide = fn($x, $y): \PrettyPhp\Functional\Result => $y === 0
                ? Result::err('division by zero')
                : Result::ok($x / $y);

            $result = Result::ok(10)
                ->andThen(fn($x): \PrettyPhp\Functional\Result => $divide($x, 2))
                ->andThen(fn($x): \PrettyPhp\Functional\Result => $divide($x, 0));

            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toBe('division by zero');
        });
    });

    describe('orElse', function (): void {
        it('returns Ok if it has value', function (): void {
            $result = Result::ok(5);
            $alternative = $result->orElse(Result::ok(10));

            expect($alternative->unwrap())->toBe(5);
        });

        it('returns alternative if Err', function (): void {
            $result = Result::err('error');
            $alternative = $result->orElse(Result::ok(10));

            expect($alternative->unwrap())->toBe(10);
        });
    });

    describe('unwrap', function (): void {
        it('returns value for Ok', function (): void {
            $result = Result::ok(42);
            expect($result->unwrap())->toBe(42);
        });

        it('throws exception for Err', function (): void {
            $result = Result::err('error');
            expect(fn(): mixed => $result->unwrap())->toThrow(\RuntimeException::class);
        });

        it('includes error message in exception', function (): void {
            $result = Result::err('custom error');
            try {
                $result->unwrap();
                expect(true)->toBeFalse(); // Should not reach here
            } catch (\RuntimeException $runtimeException) {
                expect($runtimeException->getMessage())->toContain('custom error');
            }
        });
    });

    describe('unwrapErr', function (): void {
        it('returns error for Err', function (): void {
            $result = Result::err('error');
            expect($result->unwrapErr())->toBe('error');
        });

        it('throws exception for Ok', function (): void {
            $result = Result::ok(42);
            expect(fn(): mixed => $result->unwrapErr())->toThrow(\RuntimeException::class);
        });
    });

    describe('unwrapOr', function (): void {
        it('returns value for Ok', function (): void {
            $result = Result::ok(42);
            expect($result->unwrapOr(99))->toBe(42);
        });

        it('returns default for Err', function (): void {
            $result = Result::err('error');
            expect($result->unwrapOr(99))->toBe(99);
        });
    });

    describe('unwrapOrElse', function (): void {
        it('returns value for Ok', function (): void {
            $result = Result::ok(42);
            expect($result->unwrapOrElse(fn($e): int => 99))->toBe(42);
        });

        it('computes default from error for Err', function (): void {
            $result = Result::err('error');
            expect($result->unwrapOrElse(fn($e): int => strlen($e)))->toBe(5);
        });
    });

    describe('expect', function (): void {
        it('returns value for Ok', function (): void {
            $result = Result::ok(42);
            expect($result->expect('error'))->toBe(42);
        });

        it('throws exception with custom message for Err', function (): void {
            $result = Result::err('original');
            expect(fn(): mixed => $result->expect('custom error'))
                ->toThrow(\RuntimeException::class, 'custom error');
        });
    });

    describe('expectErr', function (): void {
        it('returns error for Err', function (): void {
            $result = Result::err('error');
            expect($result->expectErr('message'))->toBe('error');
        });

        it('throws exception with custom message for Ok', function (): void {
            $result = Result::ok(42);
            expect(fn(): mixed => $result->expectErr('custom error'))
                ->toThrow(\RuntimeException::class, 'custom error');
        });
    });

    describe('toOption and toErrOption converters', function (): void {
        it('converts Ok to Some Option', function (): void {
            $result = Result::ok(42);
            $option = $result->toOption();

            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isSome())->toBeTrue();
            expect($option->unwrap())->toBe(42);
        });

        it('converts Err to None Option', function (): void {
            $result = Result::err('error');
            $option = $result->toOption();

            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isNone())->toBeTrue();
        });

        it('converts Err to Some Option of error', function (): void {
            $result = Result::err('error');
            $option = $result->toErrOption();

            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isSome())->toBeTrue();
            expect($option->unwrap())->toBe('error');
        });

        it('converts Ok to None Option of error', function (): void {
            $result = Result::ok(42);
            $option = $result->toErrOption();

            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isNone())->toBeTrue();
        });
    });

    describe('mapOr', function (): void {
        it('applies function to Ok', function (): void {
            $result = Result::ok(5);
            $value = $result->mapOr(0, fn($x): int => $x * 2);

            expect($value)->toBe(10);
        });

        it('returns default for Err', function (): void {
            $result = Result::err('error');
            $value = $result->mapOr(0, fn($x) => $x * 2);

            expect($value)->toBe(0);
        });
    });

    describe('mapOrElse', function (): void {
        it('applies function to Ok', function (): void {
            $result = Result::ok(5);
            $value = $result->mapOrElse(fn($e): int => 0, fn($x): int => $x * 2);

            expect($value)->toBe(10);
        });

        it('computes default from error for Err', function (): void {
            $result = Result::err('error');
            $value = $result->mapOrElse(fn($e): int => strlen($e), fn($x) => $x * 2);

            expect($value)->toBe(5);
        });
    });

    describe('inspect and inspectErr', function (): void {
        it('calls function for Ok with inspect', function (): void {
            $called = false;
            $value = null;

            Result::ok(42)->inspect(function ($x) use (&$called, &$value): void {
                $called = true;
                $value = $x;
            });

            expect($called)->toBeTrue();
            expect($value)->toBe(42);
        });

        it('does not call inspect for Err', function (): void {
            $called = false;

            Result::err('error')->inspect(function ($x) use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('calls function for Err with inspectErr', function (): void {
            $called = false;
            $error = null;

            Result::err('failure')->inspectErr(function ($e) use (&$called, &$error): void {
                $called = true;
                $error = $e;
            });

            expect($called)->toBeTrue();
            expect($error)->toBe('failure');
        });

        it('does not call inspectErr for Ok', function (): void {
            $called = false;

            Result::ok(42)->inspectErr(function ($e) use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('returns self for chaining', function (): void {
            $result = Result::ok(42)
                ->inspect(fn(): null => null)
                ->map(fn($x): int => $x * 2);

            expect($result->unwrap())->toBe(84);
        });
    });

    describe('and and or', function (): void {
        it('and returns second result if both Ok', function (): void {
            $result = Result::ok(5)->and(Result::ok(10));
            expect($result->unwrap())->toBe(10);
        });

        it('and returns first error if first is Err', function (): void {
            $result = Result::err('error1')->and(Result::ok(10));
            expect($result->unwrapErr())->toBe('error1');
        });

        it('and returns second error if second is Err', function (): void {
            $result = Result::ok(5)->and(Result::err('error2'));
            expect($result->unwrapErr())->toBe('error2');
        });

        it('or returns first result if first is Ok', function (): void {
            $result = Result::ok(5)->or(Result::ok(10));
            expect($result->unwrap())->toBe(5);
        });

        it('or returns second result if first is Err', function (): void {
            $result = Result::err('error')->or(Result::ok(10));
            expect($result->unwrap())->toBe(10);
        });
    });

    describe('complex scenarios', function (): void {
        it('can handle division with error handling', function (): void {
            $divide = fn($x, $y): \PrettyPhp\Functional\Result => $y === 0
                ? Result::err('division by zero')
                : Result::ok($x / $y);

            $result1 = $divide(10, 2);
            expect($result1->isOk())->toBeTrue();
            expect($result1->unwrap())->toBe(5);

            $result2 = $divide(10, 0);
            expect($result2->isErr())->toBeTrue();
            expect($result2->unwrapErr())->toBe('division by zero');
        });

        it('can parse JSON safely', function (): void {
            $parseJson = fn(string $json): \PrettyPhp\Functional\Result => Result::from(fn(): mixed => json_decode($json, true, 512, JSON_THROW_ON_ERROR));

            $valid = $parseJson('{"name":"John"}');
            expect($valid->isOk())->toBeTrue();
            expect($valid->unwrap())->toBe(['name' => 'John']);

            $invalid = $parseJson('{invalid}');
            expect($invalid->isErr())->toBeTrue();
            expect($invalid->unwrapErr())->toBeInstanceOf(\JsonException::class);
        });

        it('can chain file operations safely', function (): void {
            $readConfig = fn(): \PrettyPhp\Functional\Result => Result::ok(['timeout' => 30]);
            $validateTimeout = fn($config): \PrettyPhp\Functional\Result => isset($config['timeout']) && $config['timeout'] > 0
                ? Result::ok($config['timeout'])
                : Result::err('invalid timeout');
            $applyTimeout = fn($timeout): \PrettyPhp\Functional\Result => Result::ok($timeout * 1000);

            $result = $readConfig()
                ->andThen($validateTimeout)
                ->andThen($applyTimeout);

            expect($result->unwrap())->toBe(30000);
        });

        it('can handle multiple error types with mapErr', function (): void {
            $result = Result::from(function (): void {
                throw new \InvalidArgumentException('bad arg');
            })->mapErr(fn(\Throwable $e): array => [
                'type' => $e::class,
                'message' => $e->getMessage(),
            ]);

            expect($result->isErr())->toBeTrue();
            $error = $result->unwrapErr();
            expect($error['type'])->toBe(\InvalidArgumentException::class);
            expect($error['message'])->toBe('bad arg');
        });
    });
});
