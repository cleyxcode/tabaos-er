<?php

/**
 * Fix Filament v5 breaking change:
 * Forms\Get -> \Filament\Schemas\Components\Utilities\Get
 * Forms\Set -> \Filament\Schemas\Components\Utilities\Set
 */
function processDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            $modified = false;

            // Fix type hints in closure arguments: Forms\Get $get and Forms\Set $set
            if (strpos($content, 'Forms\\Get') !== false || strpos($content, 'Forms\\Set') !== false) {
                $content = str_replace('Forms\\Get', '\\Filament\\Schemas\\Components\\Utilities\\Get', $content);
                $content = str_replace('Forms\\Set', '\\Filament\\Schemas\\Components\\Utilities\\Set', $content);
                $modified = true;
            }

            if ($modified) {
                file_put_contents($path, $content);
                echo "Fixed Get/Set in $path\n";
            }
        }
    }
}

processDir(__DIR__ . '/app/Filament/Resources');
echo "All done.\n";
