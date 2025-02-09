<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbb44921f66a64abe88b29e9e0016b68b
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Elliptic\\' => 9,
        ),
        'B' => 
        array (
            'BN\\' => 3,
            'BI\\' => 3,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Elliptic\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
        'BN\\' => 
        array (
            0 => __DIR__ . '/..' . '/simplito/bn-php/lib',
        ),
        'BI\\' => 
        array (
            0 => __DIR__ . '/..' . '/simplito/bigint-wrapper-php/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbb44921f66a64abe88b29e9e0016b68b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbb44921f66a64abe88b29e9e0016b68b::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
