<?php

$dir = __DIR__ . '/app/Filament/Resources';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $content = preg_replace(
        '/protected static \?string \$navigationIcon = (.*?);/',
        'protected static string | \BackedEnum | null $navigationIcon = $1;',
        $content
    );
    
    $content = preg_replace(
        '/protected static \?string \$navigationGroup = (.*?);/',
        'protected static string | \UnitEnum | null $navigationGroup = $1;',
        $content
    );

    file_put_contents($file, $content);
    echo "Fixed $file\n";
}

echo "All done.\n";
