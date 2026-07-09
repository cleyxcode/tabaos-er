<?php

function processDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            $content = str_replace(
                'public function form(Form $form): Form',
                'public function form(Schema $form): Schema',
                $content
            );
            
            file_put_contents($path, $content);
            echo "Fixed $path\n";
        }
    }
}

processDir(__DIR__ . '/app/Filament/Resources');
echo "All done.\n";
