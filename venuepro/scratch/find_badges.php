<?php
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'html'])) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'badge') !== false) {
        $lines = explode("\n", $content);
        $found = [];
        foreach ($lines as $num => $l) {
            if (stripos($l, 'badge') !== false) {
                $found[] = ($num + 1) . ': ' . trim($l);
            }
        }
        if (!empty($found)) {
            echo $file->getPathname() . " (" . count($found) . " occurrences):\n";
            foreach (array_slice($found, 0, 10) as $f) {
                echo "  " . $f . "\n";
            }
            echo "\n";
        }
    }
}
