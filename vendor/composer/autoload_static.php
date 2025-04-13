<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb7cf34a248b97b263071103a9f0b190d
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'andreskrey\\Readability\\' => 23,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'O' => 
        array (
            'Ozh\\Log\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'andreskrey\\Readability\\' => 
        array (
            0 => __DIR__ . '/..' . '/andreskrey/readability.php/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Ozh\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/ozh/log/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb7cf34a248b97b263071103a9f0b190d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb7cf34a248b97b263071103a9f0b190d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb7cf34a248b97b263071103a9f0b190d::$classMap;

        }, null, ClassLoader::class);
    }
}
