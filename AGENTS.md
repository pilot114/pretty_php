# Pretty PHP - Complete API Documentation

> For project overview, installation, and quick start guide, see [README.md](README.md)

This document provides comprehensive API documentation and detailed usage examples for Pretty PHP.

## Table of Contents

- [Usage Examples](#usage-examples)
  - [String Manipulation](#string-manipulation)
  - [Array Manipulation](#array-manipulation)
  - [File Operations](#file-operations)
  - [Path Manipulation](#path-manipulation)
  - [Method Chaining](#method-chaining-examples)
  - [Integration with Native PHP](#integration-with-existing-code)
- [Binary System](#binary-data-operations)
- [API Reference](#api-reference)
  - [Str Class](#str-class)
  - [Arr Class](#arr-class)
  - [File Class](#file-class)
  - [Path Class](#path-class)

---

## Usage Examples

### String Manipulation

```php
use function PrettyPhp\str;

// Create a string wrapper
$text = str('  Hello World  ');

// Chain operations
$result = $text
    ->trim()
    ->upper()
    ->replace('WORLD', 'PHP')
    ->substring(0, 10);

echo $result; // "HELLO PHP"

// String analysis
$email = str('user@example.com');
if ($email->contains('@') && $email->endsWith('.com')) {
    echo "Valid email format";
}

// String splitting and manipulation
$csv = str('apple,banana,cherry');
$fruits = $csv->split(',');
echo $fruits->join(' | '); // "apple | banana | cherry"
```

### Array Manipulation

```php
use function PrettyPhp\arr;

// Create array wrapper
$numbers = arr([1, 2, 3, 4, 5]);

// Functional programming style
$result = $numbers
    ->filter(fn($n) => $n % 2 === 0)
    ->map(fn($n) => $n * 2)
    ->join(', ');

echo $result; // "4, 8"

// Array utilities
$data = arr(['apple', 'banana', 'apple', 'cherry']);
$unique = $data->unique()->sort();
echo $unique->join(', '); // "apple, banana, cherry"

// Statistical functions
$scores = arr([85, 92, 78, 96, 88]);
echo $scores->average(); // 87.8
echo $scores->max();     // 96
```

### File Operations

```php
use function PrettyPhp\file;

// File reading and writing
$file = file('/path/to/file.txt');

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
use function PrettyPhp\path;

// Path operations
$path = path('/home/user/documents');

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
$configPath = path('config.json');
$backupPath = $configPath->withExtension('backup.json');
echo $backupPath; // "config.backup.json"
```

### Method Chaining Examples

```php
use function PrettyPhp\{str, arr};

// Complex string processing
$result = str('  HELLO,WORLD,HOW,ARE,YOU  ')
    ->trim()
    ->lower()
    ->split(',')
    ->map(fn($word) => ucfirst($word))
    ->join(' ')
    ->replace('Hello', 'Hi');

echo $result; // "Hi World How Are You"

// Data processing pipeline
$data = arr([
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
use function PrettyPhp\{str, arr};

// Pretty PHP objects can be easily converted back to native PHP types
$prettyArray = arr([1, 2, 3, 4]);
$nativeArray = $prettyArray->get(); // Returns native PHP array

$prettyString = str('Hello');
$nativeString = $prettyString->get(); // Returns native PHP string
$nativeString = (string) $prettyString; // Also works with type casting
```

---

## Binary Data Operations

The Binary system provides attribute-based tools for working with binary data structures, particularly useful for network protocols and packet manipulation.

### Basic Example

```php
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\ICMPPacket;

// Create an ICMP packet structure
$icmpPacket = new ICMPPacket(
    type: 8,                          // Echo Request
    code: 0,                          // Standard code
    identifier: random_int(0, 65535), // Random ID
    sequenceNumber: 1,                // Sequence number
    data: str_repeat("\x00", 32)      // 32 bytes of data
);

// Pack to binary format
$binaryData = Binary::pack($icmpPacket);
echo bin2hex($binaryData); // Raw binary data as hex

// Unpack from binary format
$parsedPacket = Binary::unpack($binaryData, ICMPPacket::class);
```

### Advanced Example: IP Packet with Nested ICMP

```php
use PrettyPhp\Binary\{Binary, IPPacket, ICMPPacket};

// Create ICMP packet
$icmpPacket = new ICMPPacket(
    type: 8,
    code: 0,
    identifier: random_int(0, 65535),
    sequenceNumber: 1,
    data: str_repeat("\x00", 32)
);

// Create IP packet with nested ICMP data
$version = 4;         // IPv4
$ihl = 5;             // Header length (20 bytes)
$flags = 2;           // Don't Fragment flag

$ipPacket = new IPPacket(
    versionAndHeaderLength: ($version << 4) | $ihl,
    typeOfService: 0,
    totalLength: 60,                       // Total packet length
    identification: random_int(0, 65535),
    flagsAndFragmentOffset: ($flags << 13),
    ttl: 64,                              // Time to live
    protocol: 1,                          // ICMP protocol
    sourceIp: (int) ip2long('192.168.1.1'),
    destinationIp: (int) ip2long('8.8.8.8'),
    data: Binary::pack($icmpPacket)       // Nested ICMP packet
);

$ipBinaryData = Binary::pack($ipPacket);
```

### Binary Format Attributes

Use these attributes on class properties to define binary structure:

- `#[Binary('C')]` or `#[Binary('8')]` - 8-bit unsigned integer (1 byte)
- `#[Binary('n')]` or `#[Binary('16')]` - 16-bit big-endian unsigned integer (2 bytes)
- `#[Binary('N')]` or `#[Binary('32')]` - 32-bit big-endian unsigned integer (4 bytes)
- `#[Binary('A*')]` - Variable-length string
- `#[Binary(ClassName::class)]` - Nested binary structure

### Automatic Checksum Calculation

Both IP and ICMP packets automatically calculate checksums using the `Checksum` trait when checksum is set to 0 in the constructor.

```php
$packet = new ICMPPacket(
    type: 8,
    code: 0,
    checksum: 0,  // Will be calculated automatically
    // ...
);
```

---

## API Reference

### Str Class

**Creation:**
- `str(string $value): Str` - Helper function to create Str instance
- `new Str(string $value)` - Constructor

**Length & Checks:**
- `length(): int` - Get string length (UTF-8 aware)
- `isEmpty(): bool` - Check if string is empty
- `isNotEmpty(): bool` - Check if string is not empty

**Trimming:**
- `trim(): self` - Remove whitespace from both ends
- `ltrim(): self` - Remove whitespace from start
- `rtrim(): self` - Remove whitespace from end

**Case Conversion:**
- `upper(): self` - Convert to uppercase
- `lower(): self` - Convert to lowercase
- `capitalize(): self` - Capitalize first letter

**Searching:**
- `contains(string $needle): bool` - Check if string contains substring
- `startsWith(string $needle): bool` - Check if string starts with substring
- `endsWith(string $needle): bool` - Check if string ends with substring
- `indexOf(string $needle): int|null` - Find first occurrence position
- `lastIndexOf(string $needle): int|null` - Find last occurrence position

**Manipulation:**
- `replace(string $search, string $replace): self` - Replace first occurrence
- `replaceAll(string $search, string $replace): self` - Replace all occurrences
- `split(string $delimiter): Arr` - Split into array
- `substring(int $start, ?int $length = null): self` - Extract substring
- `repeat(int $times): self` - Repeat string n times
- `reverse(): self` - Reverse string

**Padding:**
- `padLeft(int $length, string $pad = ' '): self` - Pad on the left
- `padRight(int $length, string $pad = ' '): self` - Pad on the right
- `padBoth(int $length, string $pad = ' '): self` - Pad on both sides

**Type Checking:**
- `isAlpha(): bool` - Check if contains only letters
- `isNumeric(): bool` - Check if contains only numbers
- `isAlphaNumeric(): bool` - Check if contains only letters and numbers

**Regular Expressions:**
- `match(string $pattern): ?Arr` - Match pattern (returns first match groups)
- `matchAll(string $pattern): Arr` - Match pattern (returns all matches)

**Conversion:**
- `get(): string` - Get native PHP string
- `__toString(): string` - Cast to string

---

### Arr Class

**Creation:**
- `arr(array $value): Arr` - Helper function to create Arr instance
- `new Arr(array $value)` - Constructor

**Length & Checks:**
- `count(): int` - Get array length
- `isEmpty(): bool` - Check if array is empty
- `isNotEmpty(): bool` - Check if array is not empty

**Access:**
- `first(): mixed` - Get first element
- `last(): mixed` - Get last element
- `get(): array` - Get native PHP array

**Stack/Queue Operations:**
- `push(mixed $value): self` - Add element to end
- `pop(): self` - Remove element from end
- `shift(): self` - Remove element from start
- `unshift(mixed $value): self` - Add element to start

**Searching:**
- `contains(mixed $value): bool` - Check if array contains value
- `indexOf(mixed $value): int|null` - Find first occurrence index

**Slicing:**
- `slice(int $start, ?int $length = null): self` - Extract slice
- `splice(int $start, ?int $length = null, array $replacement = []): self` - Remove/replace slice

**Manipulation:**
- `merge(array|Arr $array): self` - Merge with another array
- `unique(): self` - Remove duplicate values
- `reverse(): self` - Reverse array order
- `flatten(int $depth = PHP_INT_MAX): self` - Flatten nested arrays
- `chunk(int $size): self` - Split into chunks
- `flip(): self` - Exchange keys and values

**Functional Methods:**
- `sort(?callable $callback = null): self` - Sort array
- `filter(callable $callback): self` - Filter elements
- `map(callable $callback): self` - Transform each element
- `reduce(callable $callback, mixed $initial = null): mixed` - Reduce to single value

**Search Methods:**
- `find(callable $callback): mixed` - Find first matching element
- `findIndex(callable $callback): int|null` - Find first matching index
- `some(callable $callback): bool` - Check if any element matches
- `every(callable $callback): bool` - Check if all elements match

**Key/Value Operations:**
- `keys(): self` - Get all keys
- `values(): self` - Get all values

**Utility Methods:**
- `join(string $separator = ''): Str` - Join elements into string
- `groupBy(callable $callback): self` - Group elements by callback result

**Statistical Methods:**
- `min(): mixed` - Get minimum value
- `max(): mixed` - Get maximum value
- `sum(): int|float` - Sum all values
- `average(): int|float` - Calculate average

---

### File Class

**Creation:**
- `file(string $path): File` - Helper function to create File instance
- `new File(string $path)` - Constructor

**Checks:**
- `exists(): bool` - Check if file exists
- `isFile(): bool` - Check if path is a file
- `isDirectory(): bool` - Check if path is a directory
- `isReadable(): bool` - Check if file is readable
- `isWritable(): bool` - Check if file is writable

**Information:**
- `size(): int` - Get file size in bytes
- `lastModified(): int` - Get last modification timestamp
- `extension(): string` - Get file extension
- `basename(): string` - Get filename with extension
- `dirname(): string` - Get directory path
- `mimeType(): string` - Get MIME type
- `permissions(): int` - Get file permissions

**Reading:**
- `read(): Str` - Read entire file as Str
- `readLines(): Arr` - Read file as array of lines
- `readLinesGenerator(): \Generator` - Read file line by line (memory efficient)

**Writing:**
- `write(string $content): self` - Write content to file (overwrite)
- `append(string $content): self` - Append content to file

**Operations:**
- `delete(): bool` - Delete file
- `copy(string $destination): File` - Copy file to destination
- `move(string $destination): File` - Move file to destination
- `chmod(int $mode): self` - Change file permissions

---

### Path Class

**Creation:**
- `path(string $path): Path` - Helper function to create Path instance
- `new Path(string $path)` - Constructor

**Checks:**
- `exists(): bool` - Check if path exists
- `isAbsolute(): bool` - Check if path is absolute
- `isRelative(): bool` - Check if path is relative
- `isFile(): bool` - Check if path is a file
- `isDirectory(): bool` - Check if path is a directory

**Building:**
- `join(string ...$segments): Path` - Join path segments
- `normalize(): Path` - Normalize path (resolve `.` and `..`)
- `resolve(string $base): Path` - Resolve relative path against base

**Relationships:**
- `relative(string $to): Path` - Get relative path to target
- `parent(): Path` - Get parent directory

**Components:**
- `basename(): string` - Get filename with extension
- `extension(): string` - Get file extension

**Extension Manipulation:**
- `withoutExtension(): Path` - Remove file extension
- `withExtension(string $extension): Path` - Replace file extension

**Directory Operations:**
- `mkdir(int $mode = 0777, bool $recursive = true): bool` - Create directory
- `listFiles(): Arr` - List files in directory
- `glob(string $pattern): Arr` - Find files matching pattern

**Information:**
- `size(): int` - Get file/directory size
- `__toString(): string` - Get path as string

---

## See Also

- [README.md](README.md) - Project overview and quick start
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [ROADMAP.md](ROADMAP.md) - Future development plans
