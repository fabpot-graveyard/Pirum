<?php

$baseDir    = __dir__;
$buildDir   = $baseDir.'/builders';
$classesDir = $baseDir.'/classes';
$stubsDir   = $baseDir.'/stubs';
$standalone = $baseDir.'/pirum';

function resourceDir($dir) {
    return new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $dir,
            RecursiveDirectoryIterator::SKIP_DOTS
        )
    );
}

foreach (resourceDir($buildDir) as $file) {
    require_once $file;
}



foreach (array(
    new Standalone_Builder($standalone, $stubsDir, $classesDir),
    new PearPackage_Builder(),
) as $builder) {
    $builder->build();
}