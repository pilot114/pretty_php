<?php

use PrettyPhp\Base\Json;
use PrettyPhp\Base\Str;
use PrettyPhp\Base\Arr;

describe('Json', function (): void {
    it('can be constructed from string', function (): void {
        $json = Json::fromString('{"name":"John","age":30}');
        expect($json->get())->toBe('{"name":"John","age":30}');
    });

    it('can be constructed from data', function (): void {
        $json = Json::fromData(['name' => 'John', 'age' => 30]);
        expect($json->get())->toBe(['name' => 'John', 'age' => 30]);
    });

    it('implements Stringable', function (): void {
        $json = Json::fromData(['name' => 'John']);
        expect((string) $json)->toBe('{"name":"John"}');

        $json2 = Json::fromString('{"name":"John"}');
        expect((string) $json2)->toBe('{"name":"John"}');
    });

    it('can use json() helper from string', function (): void {
        $result = json('{"name":"John"}');
        expect($result)->toBeInstanceOf(Json::class);
        expect($result->get())->toBe('{"name":"John"}');
    });

    it('can use json() helper from data', function (): void {
        $result = json(['name' => 'John']);
        expect($result)->toBeInstanceOf(Json::class);
        expect($result->get())->toBe(['name' => 'John']);
    });

    // ==================== Encoding/Decoding ====================

    describe('encode', function (): void {
        it('can encode data to JSON string', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            $result = $json->encode();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBeInstanceOf(Str::class);
            expect($result->unwrap()->get())->toBe('{"name":"John","age":30}');
        });

        it('returns encoded string if already encoded', function (): void {
            $json = Json::fromString('{"name":"John"}');
            $result = $json->encode();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap()->get())->toBe('{"name":"John"}');
        });

        it('can encode with flags', function (): void {
            $json = Json::fromData(['name' => 'John', 'url' => 'https://example.com']);
            $result = $json->encode(JSON_UNESCAPED_SLASHES);

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap()->get())->toContain('https://example.com');
        });

        it('handles encoding errors', function (): void {
            $json = Json::fromData(['invalid' => "\xB1\x31"]);
            $result = $json->encode();

            expect($result->isErr())->toBeTrue();
        });
    });

    describe('decode', function (): void {
        it('can decode JSON string to array', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            $result = $json->decode();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBeInstanceOf(Arr::class);
            expect($result->unwrap()->get())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('can decode JSON array', function (): void {
            $json = Json::fromString('[1, 2, 3, 4, 5]');
            $result = $json->decode();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap()->get())->toBe([1, 2, 3, 4, 5]);
        });

        it('returns error for non-encoded value', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->decode();

            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toContain('not a JSON string');
        });

        it('handles decoding errors', function (): void {
            $json = Json::fromString('{"invalid": }');
            $result = $json->decode();

            expect($result->isErr())->toBeTrue();
        });
    });

    describe('decodeObject', function (): void {
        it('can decode JSON string to object', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            $result = $json->decodeObject();

            expect($result->isOk())->toBeTrue();
            $obj = $result->unwrap();
            expect($obj)->toBeObject();
            expect($obj->name)->toBe('John');
            expect($obj->age)->toBe(30);
        });

        it('returns error for non-encoded value', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->decodeObject();

            expect($result->isErr())->toBeTrue();
        });
    });

    // ==================== Validation ====================

    describe('isValid', function (): void {
        it('can validate valid JSON string', function (): void {
            $json = Json::fromString('{"name":"John"}');
            expect($json->isValid())->toBeTrue();
        });

        it('can detect invalid JSON string', function (): void {
            $json = Json::fromString('{"invalid": }');
            expect($json->isValid())->toBeFalse();
        });

        it('can validate encodable data', function (): void {
            $json = Json::fromData(['name' => 'John']);
            expect($json->isValid())->toBeTrue();
        });

        it('validates array data', function (): void {
            $json = Json::fromData([1, 2, 3]);
            expect($json->isValid())->toBeTrue();
        });
    });

    describe('validate', function (): void {
        it('returns Ok for valid JSON', function (): void {
            $json = Json::fromString('{"name":"John"}');
            $result = $json->validate();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe($json);
        });

        it('returns Err for invalid JSON', function (): void {
            $json = Json::fromString('{"invalid": }');
            $result = $json->validate();

            expect($result->isErr())->toBeTrue();
        });
    });

    // ==================== Formatting ====================

    describe('pretty', function (): void {
        it('can pretty print JSON', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            $pretty = $json->pretty();

            $expected = json_encode(['name' => 'John', 'age' => 30], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            expect((string) $pretty)->toBe($expected);
        });

        it('can pretty print from data', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            $pretty = $json->pretty();

            expect((string) $pretty)->toContain("\n");
            expect((string) $pretty)->toContain('    ');
        });

        it('can pretty print with custom flags', function (): void {
            $json = Json::fromData(['url' => 'https://example.com']);
            $pretty = $json->pretty(JSON_PRETTY_PRINT);

            expect((string) $pretty)->toContain("\n");
        });

        it('returns self on error', function (): void {
            $json = Json::fromString('{"invalid": }');
            $pretty = $json->pretty();

            expect($pretty)->toBe($json);
        });
    });

    describe('minify', function (): void {
        it('can minify pretty-printed JSON', function (): void {
            $prettyJson = "{\n    \"name\": \"John\",\n    \"age\": 30\n}";
            $json = Json::fromString($prettyJson);
            $minified = $json->minify();

            expect((string) $minified)->toBe('{"name":"John","age":30}');
        });

        it('can minify from data', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            $minified = $json->minify();

            expect((string) $minified)->toBe('{"name":"John","age":30}');
        });

        it('returns self on error', function (): void {
            $json = Json::fromString('{"invalid": }');
            $minified = $json->minify();

            expect($minified)->toBe($json);
        });
    });

    // ==================== JSON Path Queries ====================

    describe('path', function (): void {
        it('can query simple path', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            $result = $json->path('name');

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe('John');
        });

        it('can query nested path', function (): void {
            $json = Json::fromData([
                'user' => [
                    'name' => 'John',
                    'address' => [
                        'city' => 'New York'
                    ]
                ]
            ]);
            $result = $json->path('user.address.city');

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe('New York');
        });

        it('can query array indices', function (): void {
            $json = Json::fromData([
                'users' => [
                    ['name' => 'John'],
                    ['name' => 'Jane']
                ]
            ]);
            $result = $json->path('users.0.name');

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe('John');
        });

        it('returns error for non-existent path', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->path('age');

            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toContain('Path not found');
        });

        it('works with encoded JSON', function (): void {
            $json = Json::fromString('{"user":{"name":"John"}}');
            $result = $json->path('user.name');

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBe('John');
        });
    });

    describe('hasPath', function (): void {
        it('can check if path exists', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);

            expect($json->hasPath('name'))->toBeTrue();
            expect($json->hasPath('age'))->toBeTrue();
            expect($json->hasPath('email'))->toBeFalse();
        });

        it('can check nested paths', function (): void {
            $json = Json::fromData([
                'user' => [
                    'name' => 'John'
                ]
            ]);

            expect($json->hasPath('user.name'))->toBeTrue();
            expect($json->hasPath('user.age'))->toBeFalse();
        });
    });

    // ==================== Manipulation ====================

    describe('merge', function (): void {
        it('can merge two JSON objects', function (): void {
            $json1 = Json::fromData(['name' => 'John', 'age' => 30]);
            $json2 = Json::fromData(['email' => 'john@example.com', 'age' => 31]);

            $result = $json1->merge($json2);

            expect($result->isOk())->toBeTrue();
            $merged = $result->unwrap();
            expect($merged->path('name')->unwrap())->toBe('John');
            expect($merged->path('email')->unwrap())->toBe('john@example.com');
        });

        it('can merge encoded JSON strings', function (): void {
            $json1 = Json::fromString('{"name":"John"}');
            $json2 = Json::fromString('{"age":30}');

            $result = $json1->merge($json2);

            expect($result->isOk())->toBeTrue();
            $merged = $result->unwrap();
            expect($merged->path('name')->unwrap())->toBe('John');
            expect($merged->path('age')->unwrap())->toBe(30);
        });

        it('returns error for invalid JSON', function (): void {
            $json1 = Json::fromString('{"invalid": }');
            $json2 = Json::fromData(['age' => 30]);

            $result = $json1->merge($json2);

            expect($result->isErr())->toBeTrue();
        });

        it('returns error when merging non-arrays', function (): void {
            $json1 = Json::fromData('string');
            $json2 = Json::fromData(['age' => 30]);

            $result = $json1->merge($json2);

            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toContain('must be arrays/objects');
        });
    });

    describe('set', function (): void {
        it('can set a simple value', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->set('age', 30);

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->path('age')->unwrap())->toBe(30);
        });

        it('can set a nested value', function (): void {
            $json = Json::fromData(['user' => ['name' => 'John']]);
            $result = $json->set('user.age', 30);

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->path('user.age')->unwrap())->toBe(30);
        });

        it('can create nested paths', function (): void {
            $json = Json::fromData([]);
            $result = $json->set('user.address.city', 'New York');

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->path('user.address.city')->unwrap())->toBe('New York');
        });

        it('works with encoded JSON', function (): void {
            $json = Json::fromString('{"name":"John"}');
            $result = $json->set('age', 30);

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->path('age')->unwrap())->toBe(30);
        });
    });

    describe('remove', function (): void {
        it('can remove a simple value', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            $result = $json->remove('age');

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->hasPath('name'))->toBeTrue();
            expect($updated->hasPath('age'))->toBeFalse();
        });

        it('can remove a nested value', function (): void {
            $json = Json::fromData([
                'user' => [
                    'name' => 'John',
                    'age' => 30
                ]
            ]);
            $result = $json->remove('user.age');

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->hasPath('user.name'))->toBeTrue();
            expect($updated->hasPath('user.age'))->toBeFalse();
        });

        it('returns error for non-existent path', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->remove('age');

            expect($result->isErr())->toBeTrue();
            expect($result->unwrapErr())->toContain('Path not found');
        });

        it('works with encoded JSON', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            $result = $json->remove('age');

            expect($result->isOk())->toBeTrue();
            $updated = $result->unwrap();
            expect($updated->hasPath('age'))->toBeFalse();
        });
    });

    // ==================== Utility ====================

    describe('size', function (): void {
        it('can get size of JSON object', function (): void {
            $json = Json::fromData(['name' => 'John', 'age' => 30]);
            expect($json->size())->toBe(2);
        });

        it('can get size of JSON array', function (): void {
            $json = Json::fromData([1, 2, 3, 4, 5]);
            expect($json->size())->toBe(5);
        });

        it('returns 0 for empty object', function (): void {
            $json = Json::fromData([]);
            expect($json->size())->toBe(0);
        });

        it('works with encoded JSON', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            expect($json->size())->toBe(2);
        });

        it('returns 0 for invalid JSON', function (): void {
            $json = Json::fromString('{"invalid": }');
            expect($json->size())->toBe(0);
        });
    });

    describe('isEmpty', function (): void {
        it('can check if JSON is empty', function (): void {
            $json = Json::fromData([]);
            expect($json->isEmpty())->toBeTrue();

            $json2 = Json::fromData(['name' => 'John']);
            expect($json2->isEmpty())->toBeFalse();
        });
    });

    describe('toStr', function (): void {
        it('can convert to Str', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $str = $json->toStr();

            expect($str)->toBeInstanceOf(Str::class);
            expect($str->get())->toBe('{"name":"John"}');
        });
    });

    describe('toArr', function (): void {
        it('can convert to Arr', function (): void {
            $json = Json::fromString('{"name":"John","age":30}');
            $result = $json->toArr();

            expect($result->isOk())->toBeTrue();
            expect($result->unwrap())->toBeInstanceOf(Arr::class);
            expect($result->unwrap()->get())->toBe(['name' => 'John', 'age' => 30]);
        });

        it('returns error for non-encoded value', function (): void {
            $json = Json::fromData(['name' => 'John']);
            $result = $json->toArr();

            expect($result->isErr())->toBeTrue();
        });
    });
});
