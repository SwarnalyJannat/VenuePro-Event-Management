<?php
$rootDir = realpath(__DIR__ . '/..');
$dir = new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dir);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $p = str_replace('\\', '/', $file->getPathname());
        if (strpos($p, '/scratch/') !== false || strpos($p, '/vendor/') !== false || strpos($p, '/.git/') !== false) {
            continue;
        }
        $phpFiles[] = $p;
    }
}

sort($phpFiles);
echo "Auditing " . count($phpFiles) . " PHP files for hardcoded mock data...\n\n";

$hardcodedPatterns = [
    // Static percentages or metrics
    'hardcoded_percentage' => '/class=["\'][^"\']*(?:stat-value|card-value|metric)[^"\']*["\'][^>]*>\s*([0-9]+(?:\.[0-9]+)?%)\s*</i',
    'hardcoded_currency' => '/class=["\'][^"\']*(?:stat-value|card-value|metric)[^"\']*["\'][^>]*>\s*(\$[0-9,]+(?:\.[0-9]{2})?)\s*</i',
    'hardcoded_count' => '/class=["\'][^"\']*(?:stat-value|card-value|metric)[^"\']*["\'][^>]*>\s*([0-9]{2,5})\s*</i',
    // Static dates like 'December 14, 2026' or '2026-12-14'
    'hardcoded_date' => '/(December \d{1,2}, \d{4}|Nov(?:ember)? \d{1,2}, \d{4}|Oct(?:ober)? \d{1,2}, \d{4})/i',
    // Static dummy names
    'dummy_names' => '/(Sarah Jenkins|Marcus Vance|Elena Rostova|Alex Thompson|David Miller|Michael Chang|Robert Chen)/i',
    // Static table rows with pure HTML text (multiple trs without php echo)
    'static_table_rows' => '/<tbody>\s*(?:<tr>\s*<td>(?!<\?=)(?!<\?php).*?<\/tr>\s*){2,}/is',
    // Static review text or quote
    'static_review' => '/(?:class=["\'][^"\']*(?:review|testimonial|feedback|comment)[^"\']*["\'][^>]*>)/i',
    // Hardcoded arrays
    'hardcoded_array' => '/(\$[a-zA-Z0-9_]+\s*=\s*\[\s*\[[^\]]+\]\s*\];)/s'
];

$findings = [];

foreach ($phpFiles as $file) {
    $rel = str_replace($rootDir . '/', '', str_replace('\\', '/', $file));
    if (strpos($rel, 'database/') === 0 && strpos($rel, 'seed') !== false) continue;
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    // 1. Check for hardcoded metrics
    if (preg_match_all($hardcodedPatterns['hardcoded_percentage'], $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $match) {
            $val = $match[0];
            $offset = $match[1];
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
            $lineContent = $lines[$lineNum - 1] ?? '';
            if (strpos($lineContent, '<?') === false) {
                $findings[$rel][] = ["line" => $lineNum, "type" => "hardcoded_stat_pct", "val" => $val, "snippet" => trim($lineContent)];
            }
        }
    }

    if (preg_match_all($hardcodedPatterns['hardcoded_currency'], $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $match) {
            $val = $match[0];
            $offset = $match[1];
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
            $lineContent = $lines[$lineNum - 1] ?? '';
            if (strpos($lineContent, '<?') === false) {
                $findings[$rel][] = ["line" => $lineNum, "type" => "hardcoded_currency", "val" => $val, "snippet" => trim($lineContent)];
            }
        }
    }

    // Check for hardcoded dates
    if (preg_match_all($hardcodedPatterns['hardcoded_date'], $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $match) {
            $val = $match[0];
            $offset = $match[1];
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
            $lineContent = $lines[$lineNum - 1] ?? '';
            if (strpos($lineContent, '<?') === false && strpos($lineContent, 'copyright') === false && strpos($lineContent, 'min=') === false) {
                $findings[$rel][] = ["line" => $lineNum, "type" => "hardcoded_date", "val" => $val, "snippet" => trim($lineContent)];
            }
        }
    }

    // Check for static table rows (table tbody without <? inside the first tr)
    if (preg_match_all('/<tbody>\s*<tr>(.*?)<\/tr>/is', $content, $tableMatches, PREG_OFFSET_CAPTURE)) {
        foreach ($tableMatches[1] as $idx => $tMatch) {
            $trContent = $tMatch[0];
            $offset = $tMatch[1];
            if (strpos($trContent, '<?') === false && strpos($trContent, 'No ') === false && strpos($trContent, 'Nothing') === false && strlen(trim($trContent)) > 20) {
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
                $findings[$rel][] = ["line" => $lineNum, "type" => "static_table_row", "val" => "Static <tr> without PHP", "snippet" => trim(preg_replace('/\s+/', ' ', substr($trContent, 0, 120)))];
            }
        }
    }
}

foreach ($findings as $file => $fileFindings) {
    echo "========================================================\n";
    echo "FILE: $file (" . count($fileFindings) . " potential hardcodes)\n";
    echo "========================================================\n";
    foreach ($fileFindings as $f) {
        echo "  [Line {$f['line']}] ({$f['type']}): {$f['val']}\n";
        echo "    Code: " . substr($f['snippet'], 0, 100) . "\n";
    }
    echo "\n";
}
