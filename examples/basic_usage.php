<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/functions.php';

use PrettyPhp\PrettyPhp;

echo "=== Pretty PHP Examples ===\n\n";

// String manipulation examples
echo "String Examples:\n";
echo "---------------\n";

$text = str('  Hello, Beautiful World!  ');
echo "Original: '{$text}'\n";
echo "Trimmed & Upper: '{$text->trim()->upper()}'\n";
echo "Length: " . $text->trim()->length() . "\n";
echo "Contains 'World': " . ($text->contains('World') ? 'Yes' : 'No') . "\n";

$email = str('user@example.com');
echo "Email validation: " . ($email->contains('@') && $email->endsWith('.com') ? 'Valid' : 'Invalid') . "\n";

$csv = str('apple,banana,cherry,date');
$fruits = $csv->split(',');
echo "Fruits: " . $fruits->join(' | ') . "\n";

echo "\n";

// Array manipulation examples
echo "Array Examples:\n";
echo "--------------\n";

$numbers = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
echo "Original numbers: " . $numbers->join(', ') . "\n";

$evenDoubled = $numbers
    ->filter(fn($n): bool => $n % 2 === 0)
    ->map(fn($n): int => $n * 2);
echo "Even numbers doubled: " . $evenDoubled->join(', ') . "\n";

$scores = arr([85, 92, 78, 96, 88, 94]);
echo "Scores: " . $scores->join(', ') . "\n";
echo "Average: " . round($scores->average(), 1) . "\n";
echo "Highest: " . $scores->max() . "\n";

echo "\n";

// Complex chaining example
echo "Complex Processing Example:\n";
echo "--------------------------\n";

$processed = str('  THE,QUICK,BROWN,FOX,JUMPS  ')
    ->trim()
    ->lower()
    ->split(',')
    ->filter(fn($word): bool => strlen($word) > 3)
    ->map(fn($word): string => str($word)->capitalize()->get())
    ->sort()
    ->join(' ');

echo "Processed: '{$processed}'\n";

echo "\n";

// File operations example (creating a temp file)
echo "File Operations Example:\n";
echo "-----------------------\n";

$tempFile = PrettyPhp::path(sys_get_temp_dir())->join('pretty_php_example.txt');
$file = $tempFile->toFile();

$content = str("Hello from Pretty PHP!\nThis is a test file.\nWith multiple lines.");
$file->write($content->get());

echo sprintf('File created at: %s%s', $tempFile, PHP_EOL);
echo "File size: " . $file->size() . " bytes\n";
echo "File content:\n";

$lines = $file->readLines();
$lines->each(function ($line, $index): void {
    echo "Line " . ($index + 1) . sprintf(': %s%s', $line, PHP_EOL);
});

// Clean up
$file->delete();
echo "Temp file deleted.\n";

echo "\n=== Examples completed ===\n";
