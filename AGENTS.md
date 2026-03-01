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
- `pop(): Option<T>` - Remove and return last element (Some) or None if empty
- `shift(): Option<T>` - Remove and return first element (Some) or None if empty
- `popWithRemainder(): array{Option<T>, Arr<T>}` - Pop with remaining array
- `shiftWithRemainder(): array{Option<T>, Arr<T>}` - Shift with remaining array
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
- `dirname(): Path` - Get directory path as Path object
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
- `resolve(): Path` - Resolve to real path (no parameters)

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

### Num Class

**Creation:**
- `num(int|float $value): Num` - Helper function to create Num instance
- `new Num(int|float $value)` - Constructor

**Arithmetic:**
- `add(int|float $number): self` - Add a number
- `subtract(int|float $number): self` - Subtract a number
- `multiply(int|float $number): self` - Multiply by a number
- `divide(int|float $number): self` - Divide by a number
- `mod(int|float $number): self` - Modulo operation
- `pow(int|float $exponent): self` - Power operation
- `sqrt(): self` - Square root
- `abs(): self` - Get absolute value

**Rounding:**
- `round(int $precision = 0): self` - Round to precision
- `floor(): self` - Round down
- `ceil(): self` - Round up
- `clamp(int|float $min, int|float $max): self` - Clamp between min and max

**Formatting:**
- `format(int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): Str` - Format number
- `currency(string $currencySymbol = '$', int $decimals = 2): Str` - Format as currency

**Type Checks:**
- `isEven(): bool` / `isOdd(): bool` / `isPrime(): bool`
- `isPositive(): bool` / `isNegative(): bool` / `isZero(): bool`
- `isInt(): bool` / `isFloat(): bool`
- `inRange(int|float $min, int|float $max): bool` - Range check

**Comparison:**
- `equals(int|float $other): bool` / `greaterThan(int|float $other): bool` / `lessThan(int|float $other): bool`
- `greaterThanOrEqual(int|float $other): bool` / `lessThanOrEqual(int|float $other): bool`
- `min(int|float $other): self` / `max(int|float $other): self`

**Conversion:**
- `toHex(bool $prefix = false): Str` / `toBinary(bool $prefix = false): Str` / `toOctal(bool $prefix = false): Str`
- `fromHex(string $hex): self` / `fromBinary(string $binary): self` / `fromOctal(string $octal): self` *(static)*
- `toInt(): int` / `toFloat(): float` / `get(): int|float` / `__toString(): string`

---

### Json Class

**Creation:**
- `json(mixed $data): Json` - Helper function
- `Json::fromString(string $json): self` - Create from JSON string
- `Json::fromData(mixed $data): self` - Create from data

**Encoding/Decoding:**
- `encode(int $flags = 0, int $depth = 512): Result<Str, string>` - Encode to JSON
- `decode(bool $associative = true, int $depth = 512, int $flags = 0): Result<Arr, string>` - Decode to array
- `decodeObject(int $depth = 512, int $flags = 0): Result<object, string>` - Decode to object

**Validation:**
- `isValid(): bool` - Check if valid JSON
- `validate(): Result<self, string>` - Validate and return Result

**Formatting:**
- `pretty(int $flags = ...): self` - Pretty print
- `minify(): self` - Minify

**Path Queries:**
- `path(string $path): Result<mixed, string>` - Query by dot-path (e.g. "user.name")
- `hasPath(string $path): bool` - Check if path exists

**Manipulation:**
- `merge(Json $other): Result<self, string>` - Merge with another JSON
- `set(string $path, mixed $value): Result<self, string>` - Set value at path
- `remove(string $path): Result<self, string>` - Remove value at path

**Utility:**
- `size(): int` / `isEmpty(): bool`
- `toStr(): Str` / `toArr(): Result<Arr, string>`
- `get(): mixed` / `__toString(): string`

---

### DateTime Class

**Creation:**
- `datetime(string|int|null $value = null): DateTime` - Helper function
- `DateTime::now()` / `DateTime::today()` / `DateTime::tomorrow()` / `DateTime::yesterday()`
- `DateTime::fromTimestamp(int $timestamp)` / `DateTime::fromFormat(string $format, string $datetime)`
- `DateTime::parse(string $datetime)` / `DateTime::fromImmutable(\DateTimeImmutable $dt)`

**Formatting:**
- `format(string $format): Str` / `toIso8601(): Str` / `toRfc2822(): Str` / `toRfc3339(): Str`
- `toDateString(): Str` / `toTimeString(): Str` / `toDateTimeString(): Str`
- `timestamp(): int`

**Comparison:**
- `equals(...)` / `isBefore(...)` / `isAfter(...)` / `isBetween(...)`
- `isPast()` / `isFuture()` / `isToday()` / `isYesterday()` / `isTomorrow()`
- `isWeekend()` / `isWeekday()` / `isLeapYear()`

**Manipulation:**
- `addYears(int)` / `addMonths(int)` / `addDays(int)` / `addHours(int)` / `addMinutes(int)` / `addSeconds(int)`
- `subYears(int)` / `subMonths(int)` / `subDays(int)` / `subHours(int)` / `subMinutes(int)` / `subSeconds(int)`
- `modify(string $modifier): self` / `add(\DateInterval $interval): self` / `sub(\DateInterval $interval): self`
- `setYear(int)` / `setMonth(int)` / `setDay(int)` / `setTime(int, int, int)` / `setDate(int, int, int)`
- `startOfDay()` / `endOfDay()` / `startOfMonth()` / `endOfMonth()` / `startOfYear()` / `endOfYear()`
- `startOfWeek()` / `endOfWeek()`

**Difference:**
- `diff(...)` / `diffInYears(...)` / `diffInMonths(...)` / `diffInDays(...)` / `diffInHours(...)` / `diffInMinutes(...)` / `diffInSeconds(...)`
- `ago(): Str` / `until(): Str` / `diffForHumans(): Str`

**Components:**
- `year()` / `month()` / `day()` / `hour()` / `minute()` / `second()` / `microsecond()`
- `dayOfWeek()` / `dayOfYear()` / `weekOfYear()` / `daysInMonth()` / `quarter()`

**Timezone:**
- `timezone()` / `tz(): Timezone` / `timezoneName(): Str`
- `toTimezone(...)` / `toUtc()` / `timezoneOffset(): int`

---

### Date Class (Static Utility)

**Current Time:**
- `Date::time(): int` / `Date::now(): DateTime`
- `Date::year()` / `Date::month()` / `Date::day()` / `Date::hour()` / `Date::minute()` / `Date::second()`
- `Date::today()` / `Date::yesterday()` / `Date::tomorrow()`

**Factory:**
- `Date::create(string $datetime): DateTime`
- `Date::createFromFormat(string $format, string $datetime): DateTime`
- `Date::createFromTimestamp(int $timestamp): DateTime`

**Validation:**
- `Date::checkdate(int $month, int $day, int $year): bool`
- `Date::isValid(int $year, int $month, int $day): bool`

**Formatting:**
- `Date::format(string $format, ?int $timestamp = null): Str`
- `Date::gmdate(string $format, ?int $timestamp = null): Str`

**Parsing:**
- `Date::parse(string $datetime): array`
- `Date::parseFromFormat(string $format, string $datetime): array`
- `Date::strtotime(string $datetime): int|false`

---

### Session Class

**Lifecycle:**
- `Session::start(array $options = []): bool` / `Session::destroy(): bool`
- `Session::close(): void` / `Session::commit(): void` / `Session::abort(): bool`
- `Session::reset(): bool` / `Session::regenerateId(bool $deleteOld = false): bool`

**Status:**
- `Session::isActive(): bool` / `Session::isDisabled(): bool` / `Session::isNone(): bool`
- `Session::status(): int`

**Data Access:**
- `Session::get(string $key, mixed $default = null): mixed`
- `Session::set(string $key, mixed $value): void`
- `Session::has(string $key): bool` / `Session::remove(string $key): void`
- `Session::all(): array` / `Session::replace(array $data): void` / `Session::clear(): void`
- `Session::pull(string $key, mixed $default = null): mixed` - Get and remove
- `Session::increment(string $key, int $amount = 1): int`
- `Session::decrement(string $key, int $amount = 1): int`
- `Session::push(string $key, mixed $value): void` / `Session::pop(string $key): mixed`

**Flash Data:**
- `Session::flash(string $key, mixed $value): void`
- `Session::getFlash(string $key, mixed $default = null): mixed`
- `Session::hasFlash(string $key): bool`
- `Session::keepFlash(string|array $keys): void`
- `Session::ageFlashData(): void`

**Configuration:**
- `Session::id(?string $id = null): string|false`
- `Session::name(?string $name = null): string|false`
- `Session::savePath(?string $path = null): string|false`
- `Session::cacheLimiter(?string $limiter = null): string|false`
- `Session::cacheExpire(?int $expire = null): int|false`
- `Session::getCookieParams(): array` / `Session::setCookieParams(...): bool`
- `Session::getTimeout(): int` / `Session::setTimeout(int $seconds): void`

---

### Option Class

**Creation:**
- `some(mixed $value): Option` - Helper to create Some
- `none(): Option` - Helper to create None
- `Option::some(mixed $value): self` / `Option::none(): self` / `Option::from(mixed $value): self`

**Checking:**
- `isSome(): bool` / `isNone(): bool`

**Transformation:**
- `map(callable $fn): self` - Map contained value
- `andThen(callable $fn): self` - FlatMap (returns Option)
- `filter(callable $predicate): self` - Filter by predicate
- `flatten(): self` - Flatten nested Option
- `zip(Option $other): self` - Combine two Options into tuple

**Extraction:**
- `unwrap(): mixed` - Get value (throws if None)
- `unwrapOr(mixed $default): mixed` - Get value or default
- `unwrapOrElse(callable $fn): mixed` - Get value or compute
- `expect(string $message): mixed` - Get value with custom error

**Mapping with Default:**
- `mapOr(mixed $default, callable $fn): mixed`
- `mapOrElse(callable $default, callable $fn): mixed`

**Conversion:**
- `toNullable(): mixed` - Convert to T|null
- `toResult(mixed $error): Result` - Some→Ok, None→Err
- `orElse(Option $alt): self` / `xor(Option $other): self` / `inspect(callable $fn): self`

---

### Result Class

**Creation:**
- `ok(mixed $value): Result` - Helper to create Ok
- `err(mixed $error): Result` - Helper to create Err
- `Result::ok(mixed $value): self` / `Result::err(mixed $error): self`
- `Result::from(callable $fn): self` - Catch exceptions as Err

**Checking:**
- `isOk(): bool` / `isErr(): bool`

**Transformation:**
- `map(callable $fn): self` - Map Ok value
- `mapErr(callable $fn): self` - Map Err value
- `andThen(callable $fn): self` - FlatMap (returns Result)
- `flatten(): self` - Flatten nested Result
- `fold(callable $onOk, callable $onErr): mixed` - Handle both branches

**Extraction:**
- `unwrap(): mixed` / `unwrapErr(): mixed` - Get value/error (throws)
- `unwrapOr(mixed $default): mixed` - Get Ok value or default
- `unwrapOrElse(callable $fn): mixed` - Get Ok value or compute from error
- `expect(string $msg): mixed` / `expectErr(string $msg): mixed`

**Mapping with Default:**
- `mapOr(mixed $default, callable $fn): mixed`
- `mapOrElse(callable $default, callable $fn): mixed`

**Combination:**
- `and(Result $other): self` / `or(Result $other): self` / `orElse(Result $alt): self`

**Conversion:**
- `toOption(): Option` / `toErrOption(): Option`

**Inspection:**
- `inspect(callable $fn): self` / `inspectErr(callable $fn): self`

---

## See Also

- [README.md](README.md) - Project overview and quick start
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [ROADMAP.md](ROADMAP.md) - Future development plans
