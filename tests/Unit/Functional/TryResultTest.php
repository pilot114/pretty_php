<?php

use PrettyPhp\Functional\Option;
use PrettyPhp\Functional\Result;
use PrettyPhp\Functional\TryResult;

describe('TryResult', function (): void {
    describe('construction', function (): void {
        it('can create a success', function (): void {
            $try = TryResult::success(42);
            expect($try->isSuccess())->toBeTrue();
            expect($try->isFailure())->toBeFalse();
            expect($try->get())->toBe(42);
        });

        it('can create a failure', function (): void {
            $try = TryResult::failure(new RuntimeException('error'));
            expect($try->isFailure())->toBeTrue();
            expect($try->isSuccess())->toBeFalse();
            expect($try->getError())->toBeInstanceOf(RuntimeException::class);
        });

        it('can create from a successful callable', function (): void {
            $try = TryResult::of(fn (): int => 42);
            expect($try->isSuccess())->toBeTrue();
            expect($try->get())->toBe(42);
        });

        it('can create from a failing callable', function (): void {
            $try = TryResult::of(function (): void {
                throw new RuntimeException('boom');
            });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('boom');
        });

        it('can create with helper function', function (): void {
            $try = tryCall(fn (): int => 42);
            expect($try->isSuccess())->toBeTrue();
            expect($try->get())->toBe(42);
        });

        it('helper function catches exceptions', function (): void {
            $try = tryCall(function (): void {
                throw new InvalidArgumentException('bad arg');
            });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError())->toBeInstanceOf(InvalidArgumentException::class);
        });
    });

    describe('map (safe)', function (): void {
        it('maps success values', function (): void {
            $try = TryResult::success(5)
                ->map(fn ($x): int => $x * 2);
            expect($try->isSuccess())->toBeTrue();
            expect($try->get())->toBe(10);
        });

        it('does not map failure values', function (): void {
            $try = TryResult::failure(new RuntimeException('error'))
                ->map(fn ($x) => $x * 2);
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('error');
        });

        it('catches exceptions thrown in map callback', function (): void {
            $try = TryResult::success(5)
                ->map(function ($x): never {
                    throw new RuntimeException('map failed');
                });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('map failed');
        });

        it('can chain multiple safe maps', function (): void {
            $try = TryResult::success(10)
                ->map(fn ($x): int => $x * 2)
                ->map(fn ($x): int => $x + 5)
                ->map(fn ($x): string => "result: $x");
            expect($try->get())->toBe('result: 25');
        });

        it('short-circuits on failure in chain', function (): void {
            $callCount = 0;
            $try = TryResult::success(10)
                ->map(function ($x) use (&$callCount): never {
                    $callCount++;
                    throw new RuntimeException('fail');
                })
                ->map(function ($x) use (&$callCount): int {
                    $callCount++;
                    return $x * 2;
                });
            expect($try->isFailure())->toBeTrue();
            expect($callCount)->toBe(1);
        });
    });

    describe('flatMap', function (): void {
        it('flatMaps success values', function (): void {
            $try = TryResult::success(5)
                ->flatMap(fn ($x): TryResult => TryResult::success($x * 2));
            expect($try->get())->toBe(10);
        });

        it('does not flatMap failure values', function (): void {
            $try = TryResult::failure(new RuntimeException('error'))
                ->flatMap(fn ($x): TryResult => TryResult::success($x * 2));
            expect($try->isFailure())->toBeTrue();
        });

        it('catches exceptions in flatMap callback', function (): void {
            $try = TryResult::success(5)
                ->flatMap(function ($x): never {
                    throw new RuntimeException('flatMap failed');
                });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('flatMap failed');
        });

        it('can produce failure from flatMap', function (): void {
            $try = TryResult::success(0)
                ->flatMap(fn ($x): TryResult => $x === 0
                    ? TryResult::failure(new RuntimeException('division by zero'))
                    : TryResult::success(10 / $x));
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('division by zero');
        });
    });

    describe('recover', function (): void {
        it('does not change success', function (): void {
            $try = TryResult::success(42)
                ->recover(fn ($e): int => 0);
            expect($try->get())->toBe(42);
        });

        it('recovers from failure', function (): void {
            $try = TryResult::failure(new RuntimeException('error'))
                ->recover(fn ($e): string => 'recovered: ' . $e->getMessage());
            expect($try->isSuccess())->toBeTrue();
            expect($try->get())->toBe('recovered: error');
        });

        it('catches exceptions in recover callback', function (): void {
            $try = TryResult::failure(new RuntimeException('original'))
                ->recover(function ($e): never {
                    throw new RuntimeException('recover also failed');
                });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('recover also failed');
        });
    });

    describe('recoverWith', function (): void {
        it('does not change success', function (): void {
            $try = TryResult::success(42)
                ->recoverWith(fn ($e): TryResult => TryResult::success(0));
            expect($try->get())->toBe(42);
        });

        it('recovers with another TryResult', function (): void {
            $try = TryResult::failure(new RuntimeException('error'))
                ->recoverWith(fn ($e): TryResult => TryResult::success(99));
            expect($try->isSuccess())->toBeTrue();
            expect($try->get())->toBe(99);
        });

        it('can recover into another failure', function (): void {
            $try = TryResult::failure(new RuntimeException('original'))
                ->recoverWith(fn ($e): TryResult => TryResult::failure(new RuntimeException('still failed')));
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('still failed');
        });

        it('catches exceptions in recoverWith callback', function (): void {
            $try = TryResult::failure(new RuntimeException('original'))
                ->recoverWith(function ($e): never {
                    throw new RuntimeException('recoverWith threw');
                });
            expect($try->isFailure())->toBeTrue();
            expect($try->getError()?->getMessage())->toBe('recoverWith threw');
        });
    });

    describe('get and getOrElse', function (): void {
        it('get returns value for success', function (): void {
            expect(TryResult::success(42)->get())->toBe(42);
        });

        it('get throws for failure', function (): void {
            $try = TryResult::failure(new RuntimeException('error'));
            expect(fn (): mixed => $try->get())->toThrow(RuntimeException::class, 'Called get on a Failure');
        });

        it('get preserves original exception as previous', function (): void {
            $original = new InvalidArgumentException('original error');
            $try = TryResult::failure($original);
            try {
                $try->get();
            } catch (RuntimeException $e) {
                expect($e->getPrevious())->toBe($original);
            }
        });

        it('getOrElse returns value for success', function (): void {
            expect(TryResult::success(42)->getOrElse(0))->toBe(42);
        });

        it('getOrElse returns default for failure', function (): void {
            $try = TryResult::failure(new RuntimeException('error'));
            expect($try->getOrElse(99))->toBe(99);
        });
    });

    describe('getError', function (): void {
        it('returns null for success', function (): void {
            expect(TryResult::success(42)->getError())->toBeNull();
        });

        it('returns error for failure', function (): void {
            $error = new RuntimeException('test');
            $try = TryResult::failure($error);
            expect($try->getError())->toBe($error);
        });
    });

    describe('fold', function (): void {
        it('applies onSuccess for success', function (): void {
            $result = TryResult::success(5)->fold(
                fn ($x): string => "ok: $x",
                fn ($e): string => "err: " . $e->getMessage()
            );
            expect($result)->toBe('ok: 5');
        });

        it('applies onFailure for failure', function (): void {
            $result = TryResult::failure(new RuntimeException('boom'))->fold(
                fn ($x): string => "ok: $x",
                fn ($e): string => "err: " . $e->getMessage()
            );
            expect($result)->toBe('err: boom');
        });
    });

    describe('toResult', function (): void {
        it('converts success to Ok', function (): void {
            $result = TryResult::success(42)->toResult();
            expect($result)->toBeInstanceOf(Result::class);
            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe(42);
        });

        it('converts failure to Err', function (): void {
            $error = new RuntimeException('test');
            $result = TryResult::failure($error)->toResult();
            expect($result)->toBeInstanceOf(Result::class);
            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toBe($error);
        });
    });

    describe('toOption', function (): void {
        it('converts success to Some', function (): void {
            $option = TryResult::success(42)->toOption();
            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isSome())->toBeTrue();
            expect($option->unwrap())->toBe(42);
        });

        it('converts failure to None', function (): void {
            $option = TryResult::failure(new RuntimeException('test'))->toOption();
            expect($option)->toBeInstanceOf(Option::class);
            expect($option->isNone())->toBeTrue();
        });
    });

    describe('complex scenarios', function (): void {
        it('can handle JSON parsing safely', function (): void {
            $parse = fn (string $json): TryResult => TryResult::of(
                fn (): mixed => json_decode($json, true, 512, JSON_THROW_ON_ERROR)
            );

            $valid = $parse('{"name":"John"}');
            expect($valid->isSuccess())->toBeTrue();
            expect($valid->get())->toBe(['name' => 'John']);

            $invalid = $parse('{invalid}');
            expect($invalid->isFailure())->toBeTrue();
            expect($invalid->getError())->toBeInstanceOf(\JsonException::class);
        });

        it('can chain safe operations with recovery', function (): void {
            $result = TryResult::of(fn (): int => 10)
                ->map(fn ($x): float => $x / 2)
                ->map(fn ($x): string => "value: $x")
                ->recover(fn ($e): string => 'default');

            expect($result->get())->toBe('value: 5');
        });

        it('can recover from division by zero', function (): void {
            $result = TryResult::of(fn (): int => 10)
                ->map(fn ($x): float|int => intdiv($x, 0))
                ->recover(fn ($e): int => 0);

            expect($result->isSuccess())->toBeTrue();
            expect($result->get())->toBe(0);
        });

        it('can convert through Result and Option', function (): void {
            $try = TryResult::success(42);

            $result = $try->toResult();
            expect($result->unwrap())->toBe(42);

            $option = $try->toOption();
            expect($option->unwrap())->toBe(42);
        });
    });
});
