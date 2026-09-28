<?php

/**
 * PHP Functions Reference Generator
 *
 * This script generates a comprehensive reference of all available PHP functions
 * grouped by their extensions/modules with detailed formatting.
 */

declare(strict_types=1);

/**
 * Prints a section header
 */
function printHeader(string $text, string $char = '='): void
{
    $length = strlen($text) + 4;
    echo "\n" . str_repeat($char, $length) . "\n";
    echo "  {$text}\n";
    echo str_repeat($char, $length) . "\n";
}

/**
 * Prints a subsection header
 */
function printSubHeader(string $text): void
{
    echo "\n{$text}\n";
    echo str_repeat('-', strlen($text)) . "\n";
}

/**
 * Formats function count with proper pluralization
 */
function formatCount(int $count): string
{
    return $count === 1 ? '1 function' : "{$count} functions";
}

/**
 * Gets all functions grouped by extension
 */
function getFunctionsByExtension(): array
{
    $extensions = get_loaded_extensions();
    sort($extensions);

    $result = [];

    foreach ($extensions as $extension) {
        $functions = get_extension_funcs($extension);

        if ($functions === false || empty($functions)) {
            continue;
        }

        sort($functions);
        $result[$extension] = $functions;
    }

    return $result;
}

/**
 * Gets internal (built-in) functions not tied to specific extensions
 */
function getInternalFunctions(): array
{
    $allFunctions = get_defined_functions();
    $internalFunctions = $allFunctions['internal'] ?? [];

    // Get all extension functions
    $extensionFunctions = [];
    foreach (get_loaded_extensions() as $extension) {
        $funcs = get_extension_funcs($extension);
        if ($funcs !== false) {
            $extensionFunctions = array_merge($extensionFunctions, $funcs);
        }
    }

    // Find functions that aren't in any extension
    $orphanFunctions = array_diff($internalFunctions, $extensionFunctions);
    sort($orphanFunctions);

    return $orphanFunctions;
}

/**
 * Prints function list in columns
 */
function printFunctionList(array $functions, int $columns = 3): void
{
    $maxLength = max(array_map('strlen', $functions));
    $columnWidth = $maxLength + 2;

    $chunks = array_chunk($functions, $columns);

    foreach ($chunks as $chunk) {
        echo "  ";
        foreach ($chunk as $index => $func) {
            echo str_pad($func, $columnWidth);
        }
        echo "\n";
    }
}

/**
 * Generates statistics about PHP functions
 */
function printStatistics(array $functionsByExtension, array $internalFunctions): void
{
    $totalExtensions = count($functionsByExtension);
    $totalFunctions = array_sum(array_map('count', $functionsByExtension));
    $totalInternal = count($internalFunctions);
    $grandTotal = $totalFunctions + $totalInternal;

    printHeader('PHP Functions Statistics');

    echo "  PHP Version:          " . PHP_VERSION . "\n";
    echo "  Total Extensions:     {$totalExtensions}\n";
    echo "  Extension Functions:  {$totalFunctions}\n";
    echo "  Core Functions:       {$totalInternal}\n";
    echo "  Grand Total:          {$grandTotal}\n";
}

/**
 * Prints top extensions by function count
 */
function printTopExtensions(array $functionsByExtension, int $limit = 10): void
{
    printSubHeader('Top Extensions by Function Count');

    $counts = array_map('count', $functionsByExtension);
    arsort($counts);
    $topExtensions = array_slice($counts, 0, $limit, true);

    $maxNameLength = max(array_map('strlen', array_keys($topExtensions)));

    foreach ($topExtensions as $extension => $count) {
        $bar = str_repeat('█', min(50, $count));
        printf("  %-{$maxNameLength}s  %4d  %s\n", $extension, $count, $bar);
    }
}

/**
 * Prints all functions grouped by extension
 */
function printAllFunctions(array $functionsByExtension, array $internalFunctions): void
{
    printHeader('PHP Core Functions');
    echo "  Total: " . formatCount(count($internalFunctions)) . "\n\n";

    if (!empty($internalFunctions)) {
        printFunctionList($internalFunctions, 4);
    }

    printHeader('Functions by Extension');

    foreach ($functionsByExtension as $extension => $functions) {
        printSubHeader("{$extension} (" . formatCount(count($functions)) . ")");
        printFunctionList($functions, 4);
    }
}

/**
 * Searches for functions by keyword
 */
function searchFunctions(string $keyword, array $functionsByExtension, array $internalFunctions): void
{
    $keyword = strtolower($keyword);
    $results = [];

    // Search in core functions
    foreach ($internalFunctions as $func) {
        if (str_contains(strtolower($func), $keyword)) {
            $results['Core'][] = $func;
        }
    }

    // Search in extension functions
    foreach ($functionsByExtension as $extension => $functions) {
        foreach ($functions as $func) {
            if (str_contains(strtolower($func), $keyword)) {
                $results[$extension][] = $func;
            }
        }
    }

    if (empty($results)) {
        echo "\nNo functions found matching '{$keyword}'\n";
        return;
    }

    $totalMatches = array_sum(array_map('count', $results));
    printHeader("Search Results: '{$keyword}' ({$totalMatches} matches)");

    foreach ($results as $source => $functions) {
        printSubHeader("{$source} (" . formatCount(count($functions)) . ")");
        printFunctionList($functions, 4);
    }
}

/**
 * Exports functions to JSON format
 */
function exportToJson(array $functionsByExtension, array $internalFunctions, string $filename): void
{
    $data = [
        'php_version' => PHP_VERSION,
        'generated_at' => date('Y-m-d H:i:s'),
        'core_functions' => $internalFunctions,
        'extensions' => $functionsByExtension,
        'statistics' => [
            'total_extensions' => count($functionsByExtension),
            'total_extension_functions' => array_sum(array_map('count', $functionsByExtension)),
            'total_core_functions' => count($internalFunctions),
            'grand_total' => array_sum(array_map('count', $functionsByExtension)) + count($internalFunctions),
        ],
    ];

    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nExported to: {$filename}\n";
}

/**
 * Prints functions matching a keyword
 */
function runSearch(string $keyword, array $functionsByExtension, array $internalFunctions): void
{
    if ($keyword === '') {
        echo "Usage: php php_functions_reference.php search <keyword>\n";
        return;
    }

    searchFunctions($keyword, $functionsByExtension, $internalFunctions);
}

/**
 * Prints the functions of one extension, or the list of extensions when it is unknown
 */
function runExtension(string $extension, array $functionsByExtension): void
{
    if (!isset($functionsByExtension[$extension])) {
        echo "Available extensions:\n";
        foreach (array_keys($functionsByExtension) as $ext) {
            echo "  - {$ext}\n";
        }
        return;
    }

    $functions = $functionsByExtension[$extension];
    printHeader("{$extension} Extension (" . formatCount(count($functions)) . ")");
    printFunctionList($functions, 4);
}

/**
 * Prints usage information
 */
function printHelp(): void
{
    printHeader('PHP Functions Reference - Usage');
    echo "\nCommands:\n";
    echo "  all                             Show all functions (default)\n";
    echo "  stats                           Show statistics and top extensions\n";
    echo "  search <keyword>                Search functions by keyword\n";
    echo "  extension <name>                Show functions for specific extension\n";
    echo "  export [filename]               Export to JSON (default: php_functions.json)\n";
    echo "  help                            Show this help message\n";
    echo "\nExamples:\n";
    echo "  php php_functions_reference.php\n";
    echo "  php php_functions_reference.php stats\n";
    echo "  php php_functions_reference.php search array\n";
    echo "  php php_functions_reference.php extension json\n";
    echo "  php php_functions_reference.php export my_functions.json\n";
}

/**
 * Main function
 */
function main(array $argv): void
{
    $functionsByExtension = getFunctionsByExtension();
    $internalFunctions = getInternalFunctions();

    // Parse command line arguments
    $mode = $argv[1] ?? 'all';
    $argument = $argv[2] ?? '';

    match ($mode) {
        'stats' => (static function () use ($functionsByExtension, $internalFunctions): void {
            printStatistics($functionsByExtension, $internalFunctions);
            printTopExtensions($functionsByExtension, 15);
        })(),
        'search' => runSearch($argument, $functionsByExtension, $internalFunctions),
        'export' => exportToJson(
            $functionsByExtension,
            $internalFunctions,
            $argument !== '' ? $argument : 'php_functions.json'
        ),
        'extension' => runExtension($argument, $functionsByExtension),
        'all' => (static function () use ($functionsByExtension, $internalFunctions): void {
            printStatistics($functionsByExtension, $internalFunctions);
            printTopExtensions($functionsByExtension);
            printAllFunctions($functionsByExtension, $internalFunctions);
        })(),
        'help', '--help', '-h' => printHelp(),
        default => print "Unknown command: {$mode}\n"
            . "Run 'php php_functions_reference.php help' for usage information.\n",
    };
}

// Run the script
main($argv);
