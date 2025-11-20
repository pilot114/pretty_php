<?php

use PrettyPhp\Functional\Option;

describe('Option', function (): void {
    describe('construction', function (): void {
        it('can create Some value', function (): void {
            $option = Option::some(42);
            expect($option->isSome())->toBeTrue();
            expect($option->isNone())->toBeFalse();
            expect($option->unwrap())->toBe(42);
        });

        it('can create None value', function (): void {
            $option = Option::none();
            expect($option->isNone())->toBeTrue();
            expect($option->isSome())->toBeFalse();
        });

        it('can create from nullable value', function (): void {
            $some = Option::from(42);
            expect($some->isSome())->toBeTrue();
            expect($some->unwrap())->toBe(42);

            $none = Option::from(null);
            expect($none->isNone())->toBeTrue();
        });

        it('can create Some with helper function', function (): void {
            $option = some('hello');
            expect($option->isSome())->toBeTrue();
            expect($option->unwrap())->toBe('hello');
        });

        it('can create None with helper function', function (): void {
            $option = none();
            expect($option->isNone())->toBeTrue();
        });
    });

    describe('map', function (): void {
        it('maps Some values', function (): void {
            $option = Option::some(5);
            $result = $option->map(fn($x) => $x * 2);

            expect($result->isSome())->toBeTrue();
            expect($result->unwrap())->toBe(10);
        });

        it('does not map None values', function (): void {
            $option = Option::none();
            $result = $option->map(fn($x) => $x * 2);

            expect($result->isNone())->toBeTrue();
        });

        it('can chain multiple maps', function (): void {
            $result = Option::some(5)
                ->map(fn($x) => $x * 2)
                ->map(fn($x) => $x + 3)
                ->map(fn($x) => (string) $x);

            expect($result->unwrap())->toBe('13');
        });
    });

    describe('andThen', function (): void {
        it('chains Some values', function (): void {
            $option = Option::some(5);
            $result = $option->andThen(fn($x) => Option::some($x * 2));

            expect($result->isSome())->toBeTrue();
            expect($result->unwrap())->toBe(10);
        });

        it('does not chain None values', function (): void {
            $option = Option::none();
            $result = $option->andThen(fn($x) => Option::some($x * 2));

            expect($result->isNone())->toBeTrue();
        });

        it('can flatten nested Options', function (): void {
            $result = Option::some(5)
                ->andThen(fn($x) => $x > 3 ? Option::some($x * 2) : Option::none());

            expect($result->unwrap())->toBe(10);

            $none = Option::some(2)
                ->andThen(fn($x) => $x > 3 ? Option::some($x * 2) : Option::none());

            expect($none->isNone())->toBeTrue();
        });
    });

    describe('filter', function (): void {
        it('filters Some values that match predicate', function (): void {
            $option = Option::some(5);
            $result = $option->filter(fn($x) => $x > 3);

            expect($result->isSome())->toBeTrue();
            expect($result->unwrap())->toBe(5);
        });

        it('returns None when predicate fails', function (): void {
            $option = Option::some(2);
            $result = $option->filter(fn($x) => $x > 3);

            expect($result->isNone())->toBeTrue();
        });

        it('returns None for None values', function (): void {
            $option = Option::none();
            $result = $option->filter(fn($x) => true);

            expect($result->isNone())->toBeTrue();
        });
    });

    describe('orElse', function (): void {
        it('returns Some if it has value', function (): void {
            $option = Option::some(5);
            $result = $option->orElse(Option::some(10));

            expect($result->unwrap())->toBe(5);
        });

        it('returns alternative if None', function (): void {
            $option = Option::none();
            $result = $option->orElse(Option::some(10));

            expect($result->unwrap())->toBe(10);
        });
    });

    describe('unwrap', function (): void {
        it('returns value for Some', function (): void {
            $option = Option::some(42);
            expect($option->unwrap())->toBe(42);
        });

        it('throws exception for None', function (): void {
            $option = Option::none();
            expect(fn() => $option->unwrap())->toThrow(\RuntimeException::class);
        });
    });

    describe('unwrapOr', function (): void {
        it('returns value for Some', function (): void {
            $option = Option::some(42);
            expect($option->unwrapOr(99))->toBe(42);
        });

        it('returns default for None', function (): void {
            $option = Option::none();
            expect($option->unwrapOr(99))->toBe(99);
        });
    });

    describe('unwrapOrElse', function (): void {
        it('returns value for Some', function (): void {
            $option = Option::some(42);
            expect($option->unwrapOrElse(fn() => 99))->toBe(42);
        });

        it('computes default for None', function (): void {
            $option = Option::none();
            expect($option->unwrapOrElse(fn() => 99))->toBe(99);
        });
    });

    describe('expect', function (): void {
        it('returns value for Some', function (): void {
            $option = Option::some(42);
            expect($option->expect('error'))->toBe(42);
        });

        it('throws exception with custom message for None', function (): void {
            $option = Option::none();
            expect(fn() => $option->expect('custom error'))
                ->toThrow(\RuntimeException::class, 'custom error');
        });
    });

    describe('toNullable', function (): void {
        it('converts Some to value', function (): void {
            $option = Option::some(42);
            expect($option->toNullable())->toBe(42);
        });

        it('converts None to null', function (): void {
            $option = Option::none();
            expect($option->toNullable())->toBeNull();
        });
    });

    describe('mapOr', function (): void {
        it('applies function to Some', function (): void {
            $option = Option::some(5);
            $result = $option->mapOr(0, fn($x) => $x * 2);

            expect($result)->toBe(10);
        });

        it('returns default for None', function (): void {
            $option = Option::none();
            $result = $option->mapOr(0, fn($x) => $x * 2);

            expect($result)->toBe(0);
        });
    });

    describe('mapOrElse', function (): void {
        it('applies function to Some', function (): void {
            $option = Option::some(5);
            $result = $option->mapOrElse(fn() => 0, fn($x) => $x * 2);

            expect($result)->toBe(10);
        });

        it('computes default for None', function (): void {
            $option = Option::none();
            $result = $option->mapOrElse(fn() => 99, fn($x) => $x * 2);

            expect($result)->toBe(99);
        });
    });

    describe('xor', function (): void {
        it('returns Some when only left is Some', function (): void {
            $result = Option::some(5)->xor(Option::none());
            expect($result->unwrap())->toBe(5);
        });

        it('returns Some when only right is Some', function (): void {
            $result = Option::none()->xor(Option::some(10));
            expect($result->unwrap())->toBe(10);
        });

        it('returns None when both are Some', function (): void {
            $result = Option::some(5)->xor(Option::some(10));
            expect($result->isNone())->toBeTrue();
        });

        it('returns None when both are None', function (): void {
            $result = Option::none()->xor(Option::none());
            expect($result->isNone())->toBeTrue();
        });
    });

    describe('inspect', function (): void {
        it('calls function for Some', function (): void {
            $called = false;
            $value = null;

            Option::some(42)->inspect(function ($x) use (&$called, &$value): void {
                $called = true;
                $value = $x;
            });

            expect($called)->toBeTrue();
            expect($value)->toBe(42);
        });

        it('does not call function for None', function (): void {
            $called = false;

            Option::none()->inspect(function ($x) use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('returns self for chaining', function (): void {
            $result = Option::some(42)
                ->inspect(fn() => null)
                ->map(fn($x) => $x * 2);

            expect($result->unwrap())->toBe(84);
        });
    });

    describe('complex scenarios', function (): void {
        it('can parse and validate user input', function (): void {
            $parseAge = fn(?string $input) => Option::from($input)
                ->filter(fn($s) => $s !== '')
                ->map(fn($s) => (int) $s)
                ->filter(fn($age) => $age >= 0 && $age <= 150);

            expect($parseAge('25')->unwrap())->toBe(25);
            expect($parseAge(null)->isNone())->toBeTrue();
            expect($parseAge('')->isNone())->toBeTrue();
            expect($parseAge('200')->isNone())->toBeTrue();
            expect($parseAge('-5')->isNone())->toBeTrue();
        });

        it('can chain multiple operations safely', function (): void {
            $result = Option::some(['name' => 'John', 'age' => 30])
                ->map(fn($user) => $user['age'] ?? null)
                ->filter(fn($age) => $age !== null)
                ->map(fn($age) => $age * 2)
                ->unwrapOr(0);

            expect($result)->toBe(60);
        });
    });
});
