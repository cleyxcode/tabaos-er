<?php

function processDir($dir) {
    $layoutComponents = [
        'Section',
        'Grid',
        'Group',
        'Tabs',
        'Wizard',
        'Split',
        'Fieldset'
    ];

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            $modified = false;
            
            foreach ($layoutComponents as $component) {
                if (strpos($content, "Forms\\Components\\$component::make") !== false) {
                    $content = str_replace(
                        "Forms\\Components\\$component",
                        "\\Filament\\Schemas\\Components\\$component",
                        $content
                    );
                    $modified = true;
                }
            }
            
            if ($modified) {
                file_put_contents($path, $content);
                echo "Fixed layouts in $path\n";
            }
        }
    }
}

processDir(__DIR__ . '/app/Filament/Resources');
echo "All done.\n";
