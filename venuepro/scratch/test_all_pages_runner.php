<?php
$phpFiles = [];
$dir = new RecursiveDirectoryIterator(__DIR__ . '/..');
$it = new RecursiveIteratorIterator($dir);
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        if (strpos($path, 'vendor') === false) {
            $phpFiles[] = $path;
        }
    }
}

echo "Total PHP files found: " . count($phpFiles) . "\n";
$errors = 0;
foreach ($phpFiles as $file) {
    $output = [];
    $returnVar = 0;
    exec("C:\\xampp\\php\\php.exe -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
    if ($returnVar !== 0) {
        echo "Syntax Error in {$file}:\n" . implode("\n", $output) . "\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "SUCCESS: All " . count($phpFiles) . " PHP files have 0 syntax errors!\n";
} else {
    echo "FAILED: Found {$errors} files with syntax errors.\n";
}
