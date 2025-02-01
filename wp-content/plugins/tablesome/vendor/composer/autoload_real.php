<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitb00e354d3cde47b0672784f36ce94887
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitb00e354d3cde47b0672784f36ce94887', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitb00e354d3cde47b0672784f36ce94887', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitb00e354d3cde47b0672784f36ce94887::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
