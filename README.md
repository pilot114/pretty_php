# Pretty PHP

A modern, object-oriented wrapper for PHP's standard library with consistent API design. Pretty PHP makes working with strings, arrays, files, and paths more intuitive and chainable.

## Requirements

- PHP 8.4+

## Installation

```bash
composer require prettyph/pretty-php
```

## Features

- **Consistent API**: All methods follow the same naming conventions
- **Immutable by design**: Operations return new instances instead of modifying existing ones
- **Chainable methods**: Fluent interface for clean, readable code
- **Type-safe**: Full PHP 8.4+ type declarations
- **Zero dependencies**: Pure PHP implementation

## Usage

### Auto-loading Functions

```php
// Include helper functions (optional)
require_once 'vendor/prettyph/pretty-php/src/functions.php';

// Now you can use global helper functions
$result = str('Hello World')->upper()->substring(0, 5);
echo $result; // "HELLO"
```

### String Manipulation

```php
use PrettyPhp\PrettyPhp;

// Create a string wrapper
$text = PrettyPhp::str('  Hello World  ');

// Chain operations
$result = $text
    ->trim()
    ->upper()
    ->replace('WORLD', 'PHP')
    ->substring(0, 10);

echo $result; // "HELLO PHP"

// String analysis
$email = PrettyPhp::str('user@example.com');
if ($email->contains('@') && $email->endsWith('.com')) {
    echo "Valid email format";
}

// String splitting and manipulation
$csv = PrettyPhp::str('apple,banana,cherry');
$fruits = $csv->split(',');
echo $fruits->join(' | '); // "apple | banana | cherry"
```

### Array Manipulation

```php
use PrettyPhp\PrettyPhp;

// Create array wrapper
$numbers = PrettyPhp::arr([1, 2, 3, 4, 5]);

// Functional programming style
$result = $numbers
    ->filter(fn($n) => $n % 2 === 0)
    ->map(fn($n) => $n * 2)
    ->join(', ');

echo $result; // "4, 8"

// Array utilities
$data = PrettyPhp::arr(['apple', 'banana', 'apple', 'cherry']);
$unique = $data->unique()->sort();
echo $unique->join(', '); // "apple, banana, cherry"

// Statistical functions
$scores = PrettyPhp::arr([85, 92, 78, 96, 88]);
echo $scores->average(); // 87.8
echo $scores->max();     // 96
```

### File Operations

```php
use PrettyPhp\PrettyPhp;

// File reading and writing
$file = PrettyPhp::file('/path/to/file.txt');

if ($file->exists()) {
    $content = $file->read();
    echo $content->upper(); // Content in uppercase
}

// Write to file
$file->write('Hello, Pretty PHP!');

// File information
echo $file->size();        // File size in bytes
echo $file->extension();   // File extension
echo $file->basename();    // Filename with extension

// File operations
$backup = $file->copy('/path/to/backup.txt');
$file->move('/path/to/new/location.txt');
```

### Path Manipulation

```php
use PrettyPhp\PrettyPhp;

// Path operations
$path = PrettyPhp::path('/home/user/documents');

// Build paths
$filePath = $path->join('projects', 'myapp', 'config.php');
echo $filePath; // "/home/user/documents/projects/myapp/config.php"

// Path analysis
if ($path->exists() && $path->isDirectory()) {
    $files = $path->listFiles();
    foreach ($files->get() as $file) {
        echo $file . "\n";
    }
}

// Path transformations
$configPath = PrettyPhp::path('config.json');
$backupPath = $configPath->withExtension('backup.json');
echo $backupPath; // "config.backup.json"
```

### Method Chaining Examples

```php
use PrettyPhp\PrettyPhp;

// Complex string processing
$result = PrettyPhp::str('  HELLO,WORLD,HOW,ARE,YOU  ')
    ->trim()
    ->lower()
    ->split(',')
    ->map(fn($word) => ucfirst($word))
    ->join(' ')
    ->replace('Hello', 'Hi');

echo $result; // "Hi World How Are You"

// Data processing pipeline
$data = PrettyPhp::arr([
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
    ['name' => 'Bob', 'age' => 20]
]);

$names = $data
    ->filter(fn($person) => $person['age'] >= 25)
    ->map(fn($person) => $person['name'])
    ->sort()
    ->join(', ');

echo $names; // "Jane, John"
```

### Integration with Existing Code

```php
// Pretty PHP objects can be easily converted back to native PHP types
$prettyArray = PrettyPhp::arr([1, 2, 3, 4]);
$nativeArray = $prettyArray->toArray(); // Returns native PHP array

$prettyString = PrettyPhp::str('Hello');
$nativeString = $prettyString->get(); // Returns native PHP string
$nativeString = (string) $prettyString; // Also works with type casting
```

## API Reference

### Str Class

- `length()`: Get string length
- `isEmpty()` / `isNotEmpty()`: Check if string is empty
- `trim()` / `ltrim()` / `rtrim()`: Remove whitespace
- `upper()` / `lower()` / `capitalize()`: Change case
- `contains()` / `startsWith()` / `endsWith()`: String searching
- `replace()` / `replaceAll()`: String replacement
- `split()`: Split into array
- `substring()`: Extract substring
- `indexOf()` / `lastIndexOf()`: Find position
- `repeat()` / `reverse()`: String manipulation
- `padLeft()` / `padRight()` / `padBoth()`: Padding
- `isAlpha()` / `isNumeric()` / `isAlphaNumeric()`: Type checking
- `match()` / `matchAll()`: Regular expressions

### Arr Class

- `count()`: Get array length
- `isEmpty()` / `isNotEmpty()`: Check if array is empty
- `first()` / `last()`: Get first/last element
- `push()` / `pop()` / `shift()` / `unshift()`: Stack/queue operations
- `contains()` / `indexOf()`: Element searching
- `slice()` / `splice()`: Array slicing
- `merge()` / `unique()` / `reverse()`: Array manipulation
- `sort()` / `filter()` / `map()` / `reduce()`: Functional methods
- `find()` / `findIndex()` / `some()` / `every()`: Search methods
- `keys()` / `values()` / `flip()`: Key/value operations
- `chunk()` / `join()` / `groupBy()` / `flatten()`: Utility methods
- `min()` / `max()` / `sum()` / `average()`: Statistical methods

### File Class

- `exists()` / `isFile()` / `isDirectory()`: File checking
- `isReadable()` / `isWritable()`: Permission checking
- `size()` / `lastModified()`: File information
- `read()` / `readLines()`: File reading
- `write()` / `append()`: File writing
- `delete()` / `copy()` / `move()`: File operations
- `extension()` / `basename()` / `dirname()`: Path components
- `mimeType()` / `permissions()` / `chmod()`: File metadata

### Path Class

- `exists()` / `isAbsolute()` / `isRelative()`: Path checking
- `join()` / `normalize()` / `resolve()`: Path building
- `relative()` / `parent()`: Path relationships
- `basename()` / `extension()`: Path components
- `withoutExtension()` / `withExtension()`: Extension manipulation
- `mkdir()` / `listFiles()` / `glob()`: Directory operations
- `isFile()` / `isDirectory()` / `size()`: Path information

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Code style check
composer style

# Static analysis
composer analyze
```

## License

MIT License