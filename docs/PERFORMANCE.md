# Performance Guide

Rules for writing fast code in Pretty PHP. Every rule comes from a measured change in this repository;
numbers are PHPBench `mode` values (PHP 8.5, Xdebug off) before → after.

## Results

| Benchmark | Before | After | Change | Rule |
|-----------|-------:|------:|-------:|------|
| `Binary::pack()` (TCP packet) | 7.95 μs | 0.90 μs | −89% | 1, 2 |
| `Binary::unpack()` (TCP packet) | 12.49 μs | 1.77 μs | −86% | 1, 2 |
| `Str::upper()`, 5.7 KB ASCII | 17.20 μs | 1.58 μs | −91% | 6 |
| `Str::lower()`, 5.7 KB ASCII | 14.98 μs | 1.58 μs | −89% | 6 |
| `Str::upper()`, 12 bytes | 0.117 μs | 0.130 μs | +11% | 6 (accepted trade-off) |
| `Arr::map()`, 10 000 items | 280 μs | 157 μs | −44% (= native) | 3, 5 |
| `Arr::map()`, 100 items | 3.04 μs | 1.85 μs | −39% | 3, 5 |
| `Arr::filter()`, 10 000 items | 319 μs | 253 μs | −21% (= native) | 3, 5 |
| `DateTime::parse()` | 0.73 μs | 0.65 μs | −10% | 7 |
| `Path::join()` | 0.98 μs | 0.95 μs | −4% | 3 |
| `Binary::pack()` (TCP packet) | 0.88 μs | 0.28 μs | −68% | 10, 11 |
| `Binary::unpack()` (TCP packet) | 1.86 μs | 0.94 μs | −50% | 10, 11 |
| `Json::path()`, string-backed, repeated | 1.12 μs | 0.29 μs | −74% | 7 |
| `Json::hasPath()` | 343 ns | 192 ns | −44% | 11 |
| `Json::isEmpty()` | 78 ns | 54 ns | −31% | 11 |
| `Json::size()` | 60 ns | 47 ns | −22% | 11 |
| `Json::pretty()` (creates a string-backed `Json`) | 421 ns | 480 ns | +14% | 7 (accepted trade-off: `JsonCache` allocation) |
| `Str::length()`, 5.7 KB ASCII | 2.65 μs | 1.28 μs | −52% | 6 |
| `Str::length()`, 12 bytes | 39 ns | 45 ns | +15% | 6 (accepted trade-off: threshold check) |
| `Result::ok()` | 91 ns | 80 ns | −12% | 12 |

## Rules

### 1. Compute metadata once per class, not once per call

Reflection, `getAttributes()` and `newInstance()` are expensive and their result never changes for a class.
Build a description once and keep it in a static cache (see `Binary::schema()` / `BinaryField`).
Precompute everything derived from attributes: pack formats, fixed sizes, validators, reflected
properties used by conditions.

### 2. Batch native calls and avoid copying buffers

- One `pack('nnNN…', ...$values)` call is much cheaper than one `pack()` per field: collect formats and
  values for consecutive fields and flush them together.
- Read at an offset instead of slicing: `unpack($format, $data, $offset)` rather than
  `unpack($format, substr($data, $offset))`; `ord($data[$offset])` rather than `unpack('C', substr(...))`.
- Advance a shared `&$offset` while parsing nested structures instead of re-measuring what was consumed.

### 3. Let the engine run the loop

Native array functions loop in C: `array_map`, `array_filter`, `array_find`, `array_find_key`,
`array_any`, `array_all`, `array_first`, `array_last`, `implode(..., [...$parts])`. A PHP `foreach`
that calls a closure per element is 25–45% slower on large arrays.

### 4. Never wrap a callback just to forward it

`array_any($a, fn ($v, $k) => $callback($v, $k))` performs two calls per element. Pass `$callback`
directly: PHP ignores extra arguments for closures that declare fewer parameters.

### 5. Pass the key only when the callback asks for it

`ARRAY_FILTER_USE_BOTH` is ~20% slower than the plain mode, and `array_map()` cannot pass keys at all.
Check the callback arity once (`ReflectionFunction::getNumberOfParameters()`, ~56 ns) and choose the fast
native path when the key is not needed. The check pays off from ~15 elements; describe both callback
shapes in PHPDoc (`(Closure(T): U)|(Closure(T, int|string): U)`) so PHPStan can narrow the type.

### 6. Add fast paths only where they win, with measured thresholds

`strtoupper()` is locale-independent since PHP 8.2 and ~40× faster than `mb_strtoupper()`, and gives the
same result for ASCII-only strings. Detecting ASCII (`preg_match('/[\x80-\xFF]/')`) costs ~1.5 μs per
5.7 KB, so it only pays off for strings of 64+ bytes (measured crossover). Rules of thumb:

- Measure the crossover point and gate the fast path by a cheap check first (`strlen($v) >= 64 && ...`).
- Inline the check in hot methods: a private method call costs ~8 ns, which is ~7% of a short `upper()`.
- Put the threshold where the fast path wins and the miss is negligible: `Str::length()` checks for ASCII
  only from 256 bytes (crossover ~150 bytes). A 5.7 KB ASCII string takes 1.3 μs instead of 2.6 μs; a
  non-ASCII string pays ~45 ns for the failed check (+30% at 256 bytes, +2% at 5.7 KB).

### 7. Reuse immutable internal objects

`new \DateTimeZone('UTC')` parses the tz database entry every time (~74 ns vs ~26 ns cached). Objects
without mutators (`\DateTimeZone`) can be shared through a static cache (`TimezoneCache`). Readonly
classes cannot declare static properties, so put caches in a small `@internal` helper class.

The same applies to per-instance caches: a readonly property cannot be filled lazily without breaking
PHPStan's readonly rules, so `Json::fromString()` owns a mutable `@internal` `JsonCache` that keeps the
decoded string. Path queries and manipulation then decode once per instance instead of once per call
(`path()` on a string-backed `Json`: 1.12 μs → 0.29 μs; `size()`: 0.92 μs → 0.08 μs).

### 8. Remove redundant passes

Merge regular expressions that post-process each other's output, e.g. replacing `/[\s-]+/` and then
collapsing `/_+/` is one `preg_replace('/[\s_-]+/', '_', ...)`.

### 9. Use the pipe operator only with first-class callables

`$s |> trim(...) |> strtoupper(...)` compiles to direct calls (36 ns, same as `strtoupper(trim($s))`).
Wrapping stages in closures, `|> (fn ($v) => trim($v))`, is 3.3× slower (120 ns). Multi-argument
functions (`preg_replace`, `sprintf`) stay as regular statements on hot paths.

### 10. Compile flat binary structures into one pack() / unpack() call

`Binary` builds a `FlatLayout` per class when every field is a regular fixed-size field (optionally with
a trailing `A*`) and the class has no parent: one `pack($format, ...array_values((array) $object))` and
one named `unpack('nsourcePort/ndestinationPort/…', $data, $offset)` replace the field-by-field loop.
Anything else — bit fields, conditions, nested structures, uninitialized properties, too short data —
takes the generic path, which also produces the exact error messages.

- `(array) $object` reads all properties in ~56 ns; `get_object_vars()` in a class-bound closure takes
  ~150 ns.
- Properties are written with `ReflectionProperty::setValue()`: assigning `$object->{$name}` from a bound
  closure is ~85 ns faster per TCP packet, but it is forbidden by PHPStan strict rules and applies strict
  types instead of the coercion the generic path uses.

### 11. Import functions the engine compiles to opcodes

In namespaced code an unqualified `strlen()` is resolved at runtime (namespace first, then global), so
the compiler cannot turn it into a dedicated opcode: ~10 ns per call (`strlen` 19 ns vs 10 ns;
a two-key path lookup with `is_array` + `array_key_exists` 173 ns vs 146 ns). Hot files import them with
`use function strlen;` (also `count`, `is_array`, `is_string`, `is_int`, `is_object`, `array_key_exists`,
`in_array`, `ord`, `chr`, ...). Other functions gain almost nothing: their lookup is cached after the first call.

### 12. Keep hot value objects small and read their own properties

`Result` stores the Ok value and the Err error in one property (2 properties instead of 3: `Result::ok()`
~91 ns → ~80 ns), and its methods read `$this->isOk` instead of calling `$this->isOk()` (~10 ns per call).

### 13. Accept the inherent cost of value objects

A wrapper call is a method call plus an object allocation (~50–70 ns for `Num::add()` vs 7 ns for `+`).
This cannot be optimized away; in tight numeric loops call `get()` once and use native operators.

## Benchmarking Hygiene

- Run with Xdebug off: `XDEBUG_MODE=off composer bench`. The `compare` report
  (`tests/benchmarks/Report/NativeComparisonGenerator.php`) prints each Pretty PHP subject next to its native
  counterpart: native and Pretty PHP mode time, absolute overhead, ratio and a log-scale slowdown bar
  (green ≤ ×1.1, yellow ≤ ×2, red above), followed by a summary (median and geometric mean ratio).
- Save a baseline before changing code and compare against it:
  `composer bench -- --tag=baseline` then `composer bench -- --ref=baseline`; with `--ref` the report adds
  a `vs ref` column (change of the Pretty PHP subject against the baseline). The raw PHPBench table is still
  available with `vendor/bin/phpbench run --report=aggregate`.
- Use `--retry-threshold=5` to reduce noise; always check that native reference benchmarks did not
  move — if they did, the machine drifted, not the code.
- Every `benchX` needs an equivalent `benchNativeX` doing the same work (an empty native benchmark
  makes the comparison meaningless). The report pairs them by name: `benchNativeX` is the reference for
  `benchX` or `bench<Class>X` (`benchStrUpperShort` in `StrBench` ↔ `benchNativeUpperShort`); a subject
  without a pair is shown with `—`.
- Methods marked `#[\NoDiscard]` must be called as `(void) $obj->method()` in benchmarks, otherwise each
  call emits a warning and the error handling dominates the measurement. The cast itself costs ~2–5 ns,
  which is visible (+5–10%) on nanosecond-scale benchmarks such as `NumBench`; compare results only
  within the same calling form.
