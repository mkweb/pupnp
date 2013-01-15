<?php
namespace at\mkweb\upnp;

class Autoloader {

    public static function register() {

        spl_autoload_register(__CLASS__ . '::autoload');
    }

    public static function autoload($className) {

        $namespace = explode('\\', $className);

        $className = array_pop($namespace);

        $tmp = explode('\\', __NAMESPACE__);
        $dir = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));

        for($i = 0; $i < count($tmp); $i++) array_pop($dir);

        $dir = join(DIRECTORY_SEPARATOR, $dir) . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $namespace);
        $file = $dir . DIRECTORY_SEPARATOR . $className . '.php';

        if(file_exists($file)) {

            require_once($file);
        }
    }
}

?>
