#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Benchmark Comparison Report Generator
 *
 * Transforms PHPBench XML/JSON output into a consistent, side-by-side comparison table.
 * Time columns (PDO, PhpDb, Laminas, Doctrine) are shown first, followed by memory columns.
 *
 * Usage:
 *   ./bin/compare-results.php results/benchmark_*.xml
 *   ./bin/compare-results.php results/benchmark_*.json
 *   ./bin/compare-results.php                           # auto-detect latest
 *   ./bin/compare-results.php --markdown                # output as markdown table
 *
 * Output Format:
 *   | Operation | PDO Time | PhpDb Time | Laminas Time | Doctrine Time | PDO Mem | PhpDb Mem | Laminas Mem | Doctrine Mem |
 */

// Parse command line arguments
$files = [];
$markdown = false;
$listFiles = false;
$showHelp = false;

foreach (array_slice($argv, 1) as $arg) {
    switch ($arg) {
        case '--markdown':
        case '-m':
            $markdown = true;
            break;
        case '--list':
        case '-l':
            $listFiles = true;
            break;
        case '--help':
        case '-h':
            $showHelp = true;
            break;
        default:
            if (!str_starts_with($arg, '-')) {
                $files[] = $arg;
            }
    }
}

if ($showHelp) {
    echo <<<HELP
Benchmark Comparison Report Generator

Transforms PHPBench results into a consistent, side-by-side comparison table.
Uses mode (most frequent value) for time measurements to ensure reproducibility.

USAGE:
  {$argv[0]} [options] [file1.xml] [file2.xml] ...

OPTIONS:
  -m, --markdown    Output as markdown table
  -l, --list        List available result files
  -h, --help        Show this help message

EXAMPLES:
  {$argv[0]}                                    # Use latest result file
  {$argv[0]} results/benchmark_*.xml            # Process multiple files
  {$argv[0]} --markdown > comparison.md         # Export as markdown
  {$argv[0]} --list                             # Show available results

OUTPUT FORMAT:
  First 3 columns show execution time (mode) for PDO, PhpDb, Laminas
  Next 3 columns show peak memory usage
  Doctrine column included if data available

WORKFLOW:
  1. Run benchmarks: ./vendor/bin/phpbench run --dump-file=results/benchmark_\$(date +%Y%m%d_%H%M%S).xml
  2. Compare results: ./bin/compare-results.php

HELP;
    exit(0);
}

if ($listFiles) {
    $dir = __DIR__ . '/../results';
    if (!is_dir($dir)) {
        echo "No results directory found.\n";
        exit(1);
    }

    $resultFiles = array_merge(
        glob($dir . '/benchmark_*.xml') ?: [],
        glob($dir . '/benchmark_*.json') ?: []
    );

    if (empty($resultFiles)) {
        echo "No benchmark result files found in results/\n";
        exit(1);
    }

    // Sort by modification time, newest first
    usort($resultFiles, fn($a, $b) => filemtime($b) - filemtime($a));

    echo "Available benchmark results (newest first):\n\n";
    foreach ($resultFiles as $file) {
        $basename = basename($file);
        $mtime = date('Y-m-d H:i:s', filemtime($file));
        $size = round(filesize($file) / 1024, 1);
        echo "  $basename  ($mtime, {$size}KB)\n";
    }
    echo "\n";
    exit(0);
}

if (empty($files)) {
    $latestFile = findLatestResultFile();
    if ($latestFile) {
        $files = [$latestFile];
        echo "Using latest result file: $latestFile\n\n";
    } else {
        echo "Usage: {$argv[0]} <benchmark-result.xml|json> [another-result...]\n";
        echo "   or: {$argv[0]} (automatically uses latest results/*)\n";
        echo "   Options: --markdown, -m  Output as markdown table\n";
        echo "            --list, -l      List available result files\n";
        echo "            --help, -h      Show help\n";
        exit(1);
    }
}

// Framework detection patterns
const FRAMEWORKS = [
    'PhpDb' => ['PhpDb'],
    'Laminas' => ['Laminas'],
    'Doctrine' => ['Doctrine'],
];

function findLatestResultFile(): ?string
{
    $dir = __DIR__ . '/../results';
    if (!is_dir($dir)) {
        return null;
    }

    // Check for XML files first (phpbench dump format), then JSON
    $files = array_merge(
        glob($dir . '/benchmark_*.xml') ?: [],
        glob($dir . '/benchmark_*.json') ?: []
    );

    if (empty($files)) {
        return null;
    }

    // Sort by modification time, newest first
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

    return $files[0];
}

function detectFramework(string $subject, string $benchmark = ''): ?string
{
    // Check benchmark class name first (e.g., PhpDbBench, LaminasBench, DoctrineBench)
    if ($benchmark) {
        if (str_contains($benchmark, 'PhpDb')) {
            return 'PhpDb';
        }
        if (str_contains($benchmark, 'Laminas')) {
            return 'Laminas';
        }
        if (str_contains($benchmark, 'Doctrine')) {
            return 'Doctrine';
        }
    }

    // Then check subject/method name
    foreach (FRAMEWORKS as $name => $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match("/{$pattern}/", $subject)) {
                return $name;
            }
        }
    }
    return null;
}

function extractOperation(string $subject): string
{
    // Remove "bench" prefix
    $operation = preg_replace('/^bench/', '', $subject);

    // Remove numeric prefix with letter suffix (e.g., "1a_", "10b_", "2c_")
    $operation = preg_replace('/^\d+[a-z]?_/', '', $operation);

    // Remove framework identifier
    $operation = preg_replace('/^(RawPdo|Raw|PhpDb|Laminas|Doctrine)/', '', $operation);

    return $operation;
}

function formatTime(float $microseconds, int $width = 12): string
{
    if ($microseconds == 0) {
        return str_repeat(' ', $width - 1) . '-';
    }
    if ($microseconds >= 1000000) {
        $str = sprintf('%.2fs', $microseconds / 1000000);
    } elseif ($microseconds >= 1000) {
        $str = sprintf('%.2fms', $microseconds / 1000);
    } else {
        $str = sprintf('%.2fus', $microseconds);
    }
    $padding = $width - strlen($str);
    return str_repeat(' ', max(0, $padding)) . $str;
}

function formatMemory(float $bytes, int $width = 10): string
{
    if ($bytes == 0) {
        return str_pad('-', $width, ' ', STR_PAD_LEFT);
    }
    if ($bytes >= 1073741824) {
        $str = sprintf('%.2fgb', $bytes / 1073741824);
    } elseif ($bytes >= 1048576) {
        $str = sprintf('%.2fmb', $bytes / 1048576);
    } elseif ($bytes >= 1024) {
        $str = sprintf('%.2fkb', $bytes / 1024);
    } else {
        $str = sprintf('%.0fb', $bytes);
    }
    return str_pad($str, $width, ' ', STR_PAD_LEFT);
}

function calculateMode(array $values): float
{
    if (empty($values)) {
        return 0.0;
    }

    // Round to 6 decimal places for mode calculation
    $rounded = array_map(fn($v) => round((float)$v, 6), $values);
    $counts = array_count_values(array_map('strval', $rounded));
    arsort($counts);

    return (float) array_key_first($counts);
}

function parseFile(string $file): array
{
    $content = file_get_contents($file);

    // Detect XML by checking first characters
    if (str_starts_with(trim($content), '<?xml') || str_starts_with(trim($content), '<phpbench')) {
        return parseXmlContent($content, $file);
    }

    // Try JSON
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid format in $file (not valid XML or JSON)");
    }

    return extractBenchmarkDataFromJson($data);
}

function parseXmlContent(string $content, string $file): array
{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);

    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        throw new RuntimeException("Invalid XML in $file: " . ($errors[0]->message ?? 'Unknown error'));
    }

    return extractBenchmarkDataFromXml($xml);
}

function extractBenchmarkDataFromXml(SimpleXMLElement $xml): array
{
    $results = [];

    foreach ($xml->suite as $suite) {
        foreach ($suite->benchmark as $benchmark) {
            $benchmarkClass = (string) $benchmark['class'];
            // Extract short name from FQCN
            $benchmarkName = substr($benchmarkClass, strrpos($benchmarkClass, '\\') + 1);

            foreach ($benchmark->subject as $subject) {
                $subjectName = (string) $subject['name'];

                foreach ($subject->variant as $variant) {
                    // PHPBench provides pre-calculated stats in <stats> element
                    $stats = $variant->stats;
                    if ($stats) {
                        // Use mode directly from stats - this is the most consistent value
                        $timeMode = (float) $stats['mode'];
                        // Calculate average memory from all iterations (mem-final = memory after operation)
                        $memValues = [];
                        foreach ($variant->iteration as $iteration) {
                            $memValues[] = (float) $iteration['mem-final'];
                        }
                        $memAvg = empty($memValues) ? 0 : array_sum($memValues) / count($memValues);

                        $results[] = [
                            'benchmark' => $benchmarkName,
                            'subject' => $subjectName,
                            'time_mode' => $timeMode,
                            'mem_avg' => $memAvg,
                        ];
                    }
                }
            }
        }
    }

    return $results;
}

function extractBenchmarkDataFromJson(array $jsonData): array
{
    $results = [];

    foreach ($jsonData['suites'] ?? [] as $suite) {
        foreach ($suite['benchmarks'] ?? [] as $benchmark) {
            $benchmarkName = $benchmark['name'] ?? 'Unknown';

            foreach ($benchmark['subjects'] ?? [] as $subject) {
                $subjectName = $subject['name'] ?? 'Unknown';

                foreach ($subject['variants'] ?? [] as $variant) {
                    $times = [];
                    $mems = [];

                    foreach ($variant['iterations'] ?? [] as $iteration) {
                        foreach ($iteration['results'] ?? [] as $result) {
                            if (isset($result['time'])) {
                                $times[] = $result['time']['avg'] ?? 0;
                            }
                            if (isset($result['mem'])) {
                                $mems[] = $result['mem']['peak'] ?? 0;
                            }
                        }
                    }

                    $results[] = [
                        'benchmark' => $benchmarkName,
                        'subject' => $subjectName,
                        'time_mode' => calculateMode($times),
                        'mem_avg' => empty($mems) ? 0 : array_sum($mems) / count($mems),
                    ];
                }
            }
        }
    }

    return $results;
}

function pivotByOperation(array $data): array
{
    $pivoted = [];

    foreach ($data as $row) {
        $subject = $row['subject'];
        $benchmark = $row['benchmark'];
        $framework = detectFramework($subject, $benchmark);
        $operation = extractOperation($subject);

        if (!$framework) {
            continue;
        }

        // For framework-specific benchmarks (PhpDbBench, LaminasBench, DoctrineBench),
        // group by operation only so they appear side-by-side
        if (preg_match('/^(PhpDb|Laminas|Doctrine)/', $benchmark)) {
            $key = 'FrameworkComparison::' . $operation;
            $displayBenchmark = 'Framework Comparison';
        } else {
            $key = $benchmark . '::' . $operation;
            $displayBenchmark = $benchmark;
        }

        if (!isset($pivoted[$key])) {
            $pivoted[$key] = [
                'benchmark' => $displayBenchmark,
                'operation' => $operation,
                'frameworks' => [],
            ];
        }

        $pivoted[$key]['frameworks'][$framework] = [
            'time' => $row['time_mode'],
            'mem' => $row['mem_avg'],
        ];
    }

    ksort($pivoted);

    return $pivoted;
}

function printComparisonTable(array $pivoted): void
{
    $frameworkOrder = ['PhpDb', 'Laminas', 'Doctrine'];

    // Column widths
    $opWidth = 35;
    $timeWidth = 12;
    $memWidth = 10;

    // Header
    $header = sprintf("%-{$opWidth}s", 'Operation');
    foreach ($frameworkOrder as $fw) {
        $header .= ' | ' . str_pad($fw . ' Time', $timeWidth, ' ', STR_PAD_LEFT);
    }
    foreach ($frameworkOrder as $fw) {
        $header .= ' | ' . str_pad($fw . ' Mem', $memWidth, ' ', STR_PAD_LEFT);
    }

    $separator = str_repeat('-', mb_strlen($header));

    echo $separator . "\n";
    echo $header . "\n";
    echo $separator . "\n";

    $currentBenchmark = '';

    foreach ($pivoted as $row) {
        // Print benchmark separator if changed
        if ($row['benchmark'] !== $currentBenchmark) {
            if ($currentBenchmark !== '') {
                echo $separator . "\n";
            }
            $currentBenchmark = $row['benchmark'];
            echo "# {$currentBenchmark}\n";
        }

        $line = sprintf("%-{$opWidth}s", $row['operation']);

        // Time columns
        foreach ($frameworkOrder as $fw) {
            $time = $row['frameworks'][$fw]['time'] ?? 0;
            $line .= ' | ' . formatTime($time, $timeWidth);
        }

        // Memory columns
        foreach ($frameworkOrder as $fw) {
            $mem = $row['frameworks'][$fw]['mem'] ?? 0;
            $line .= ' | ' . formatMemory($mem, $memWidth);
        }

        echo $line . "\n";
    }

    echo $separator . "\n";
}

function printMarkdownTable(array $pivoted): void
{
    $frameworkOrder = ['PhpDb', 'Laminas', 'Doctrine'];

    echo "| Operation | PhpDb Time | Laminas Time | Doctrine Time | PhpDb Mem | Laminas Mem | Doctrine Mem |\n";
    echo "|-----------|------------|--------------|---------------|-----------|-------------|---------------|\n";

    $currentBenchmark = '';

    foreach ($pivoted as $row) {
        if ($row['benchmark'] !== $currentBenchmark) {
            if ($currentBenchmark !== '') {
                echo "| | | | | | | |\n";
            }
            $currentBenchmark = $row['benchmark'];
            echo "| **{$currentBenchmark}** | | | | | | |\n";
        }

        $line = "| {$row['operation']}";

        foreach ($frameworkOrder as $fw) {
            $time = $row['frameworks'][$fw]['time'] ?? 0;
            $line .= " | " . trim(formatTime($time));
        }

        foreach ($frameworkOrder as $fw) {
            $mem = $row['frameworks'][$fw]['mem'] ?? 0;
            $line .= " | " . trim(formatMemory($mem));
        }

        echo $line . " |\n";
    }
}

// Main execution
$allData = [];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Warning: File not found: $file\n";
        continue;
    }

    try {
        $benchmarkData = parseFile($file);
        $allData = array_merge($allData, $benchmarkData);
    } catch (RuntimeException $e) {
        echo "Error processing $file: {$e->getMessage()}\n";
    }
}

if (empty($allData)) {
    echo "No benchmark data found.\n";
    exit(1);
}

$pivoted = pivotByOperation($allData);

if ($markdown) {
    printMarkdownTable($pivoted);
} else {
    printComparisonTable($pivoted);
}

echo "\n";
echo "Legend:\n";
echo "  - Time values use mode (most frequent value) for consistency between runs\n";
echo "  - Memory shows average memory after operation (mem-final)\n";
echo "  - '-' indicates no data available for that framework/operation\n";