<?php
namespace at\mkweb;

class Config {

    private static $file;
    private static $config = array();

    public static function init($file) {

        self::$file = $file;

        $config = array();

        if(file_exists($file)) {

            ob_start();            
            require_once($file);
            ob_clean();
        }

        self::$config = $config;
    }

    public static function get($key) {

        return (isset(self::$config[$key]) ? self::$config[$key] : null);
    }
}
