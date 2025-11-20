<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\Json;

#[BeforeMethods('setUp')]
class JsonBench
{
    private array $data;

    private string $jsonString;

    private Json $jsonFromData;

    private Json $jsonFromString;

    public function setUp(): void
    {
        $this->data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'country' => 'USA',
            ],
            'interests' => ['programming', 'reading', 'hiking'],
        ];

        $this->jsonString = '{"name":"John Doe","email":"john@example.com","age":30,"address":{"street":"123 Main St","city":"New York","country":"USA"},"interests":["programming","reading","hiking"]}';

        $this->jsonFromData = Json::fromData($this->data);
        $this->jsonFromString = Json::fromString($this->jsonString);
    }

    // ==================== Encoding/Decoding ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonEncode(): void
    {
        $this->jsonFromData->encode();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeEncode(): void
    {
        json_encode($this->data);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonDecode(): void
    {
        $this->jsonFromString->decode();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeDecode(): void
    {
        json_decode($this->jsonString, true);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonDecodeObject(): void
    {
        $this->jsonFromString->decodeObject();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeDecodeObject(): void
    {
        json_decode($this->jsonString, false);
    }

    // ==================== Validation ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonIsValid(): void
    {
        $this->jsonFromString->isValid();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeIsValid(): void
    {
        json_decode($this->jsonString);
        json_last_error() === JSON_ERROR_NONE;
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonValidate(): void
    {
        $this->jsonFromString->validate();
    }

    // ==================== Formatting ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonPretty(): void
    {
        $this->jsonFromData->pretty();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativePretty(): void
    {
        json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonMinify(): void
    {
        $this->jsonFromString->minify();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeMinify(): void
    {
        $decoded = json_decode($this->jsonString, true);
        json_encode($decoded);
    }

    // ==================== JSON Path Queries ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonPathSimple(): void
    {
        $this->jsonFromData->path('name');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativePathSimple(): void
    {
        $this->data['name'] ?? null;
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonPathNested(): void
    {
        $this->jsonFromData->path('address.city');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativePathNested(): void
    {
        $this->data['address']['city'] ?? null;
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonHasPath(): void
    {
        $this->jsonFromData->hasPath('address.city');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeHasPath(): void
    {
        isset($this->data['address']['city']);
    }

    // ==================== Manipulation ====================

    #[Revs(3000)]
    #[Iterations(10)]
    public function benchJsonMerge(): void
    {
        $other = Json::fromData(['phone' => '555-1234']);
        $this->jsonFromData->merge($other);
    }

    #[Revs(3000)]
    #[Iterations(10)]
    public function benchNativeMerge(): void
    {
        $other = ['phone' => '555-1234'];
        array_merge_recursive($this->data, $other);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonSet(): void
    {
        $this->jsonFromData->set('phone', '555-1234');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeSet(): void
    {
        $data = $this->data;
        $data['phone'] = '555-1234';
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonRemove(): void
    {
        $this->jsonFromData->remove('age');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeRemove(): void
    {
        $data = $this->data;
        unset($data['age']);
    }

    // ==================== Utility ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchJsonSize(): void
    {
        $this->jsonFromData->size();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeSize(): void
    {
        count($this->data);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchJsonIsEmpty(): void
    {
        $this->jsonFromData->isEmpty();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeIsEmpty(): void
    {
        empty($this->data);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchJsonToString(): void
    {
        (string) $this->jsonFromData;
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeToString(): void
    {
        json_encode($this->data);
    }
}
