<?php

$models = [
    'Wilayah' => 'wilayah',
    'Pengguna' => 'pengguna',
    'LaporanBencana' => 'laporan_bencana',
    'Relawan' => 'relawan',
    'PetugasEmergency' => 'petugas_emergency',
    'Faskes' => 'faskes',
    'Ambulans' => 'ambulans',
    'ZonaRawanBencana' => 'zona_rawan_bencana',
    'TitikEvakuasi' => 'titik_evakuasi',
    'Penugasan' => 'penugasan',
    'PedomanBhd' => 'pedoman_bhd',
];

foreach ($models as $model => $table) {
    $file = __DIR__ . "/app/Models/{$model}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if $table is already set
        if (strpos($content, 'protected $table') === false) {
            // Insert it after the class declaration or after use traits
            $replacement = "{\n    protected \$table = '{$table}';\n";
            $content = preg_replace('/\{\s+/', $replacement, $content, 1);
            
            file_put_contents($file, $content);
            echo "Added \$table to $model\n";
        } else {
            echo "Already set in $model\n";
        }
    }
}
