<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\Base\Arr;

echo "=== Arr with Iterable Support Examples ===\n\n";

// Example 1: Using with ArrayIterator
echo "1. Working with ArrayIterator:\n";
$iterator = new ArrayIterator([1, 2, 3, 4, 5]);
$arr = new Arr($iterator);
echo "Original: " . implode(', ', $arr->get()) . "\n";
echo "Doubled: " . implode(', ', $arr->map(fn($x) => $x * 2)->get()) . "\n";
echo "Filtered (even): " . implode(', ', $arr->filter(fn($x) => $x % 2 === 0)->get()) . "\n\n";

// Example 2: Using with Generator
echo "2. Working with Generator:\n";
$generator = function () {
    for ($i = 1; $i <= 5; $i++) {
        yield "item_$i" => $i * 10;
    }
};

$arr = new Arr($generator());
echo "From generator: " . json_encode($arr->get()) . "\n";
echo "Keys: " . implode(', ', $arr->keys()->get()) . "\n";
echo "Values: " . implode(', ', $arr->values()->get()) . "\n\n";

// Example 3: Using arr() function with different iterables
echo "3. Using arr() function with different iterables:\n";

// With SplFixedArray
$splArray = SplFixedArray::fromArray([10, 20, 30]);
$arr1 = arr($splArray);
echo "From SplFixedArray: " . implode(', ', $arr1->get()) . "\n";

// With range (returns array but demonstrates the concept)
$arr2 = arr(range(1, 5));
echo "From range: " . implode(', ', $arr2->get()) . "\n";

// With generator
$fibonacci = function ($n) {
    $a = $b = 1;
    for ($i = 0; $i < $n; $i++) {
        yield $a;
        [$a, $b] = [$b, $a + $b];
    }
};

$arr3 = arr($fibonacci(8));
echo "Fibonacci sequence: " . implode(', ', $arr3->get()) . "\n\n";

// Example 4: Merging with iterables
echo "4. Merging with different iterable types:\n";
$base = arr([1, 2, 3]);
$iterator = new ArrayIterator([4, 5, 6]);
$generator = function () {
    yield 7;
    yield 8;
    yield 9;
};

$merged = $base
    ->merge($iterator)
    ->merge($generator());

echo "Merged result: " . implode(', ', $merged->get()) . "\n\n";

// Example 5: Using static from() method
echo "5. Using static from() method:\n";
$data = new ArrayObject(['x' => 100, 'y' => 200, 'z' => 300]);
$arr = Arr::from($data);
echo "From ArrayObject: " . json_encode($arr->get()) . "\n";
echo "Sum: " . $arr->sum() . "\n";
echo "Average: " . $arr->average() . "\n";

echo "\n=== All examples completed! ===\n";
