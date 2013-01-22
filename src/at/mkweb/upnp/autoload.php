<?php
/**
 * pUPnP, an PHP UPnP MediaControl
 * 
 * Copyright (C) 2012 Mario Klug
 * 
 * This file is part of pUPnP.
 * 
 * pUPnP is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the
 * License, or (at your option) any later version.
 * 
 * pUPnP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * See the GNU General Public License for more details. You should have received a copy of the GNU
 * General Public License along with Mupen64PlusAE. If not, see <http://www.gnu.org/licenses/>.
 */
namespace at\mkweb\upnp;

/**
* Autoloader for at.mkweb.upnp namespace
*
* @package at.mkweb.upnp
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Autoloader {

    /**
    * Registers autoloader with spl_autoload_register
    *
    * @static
    * @access public
    */
    public static function register() {

        spl_autoload_register(__CLASS__ . '::autoload');
    }

    /**
    * Autoloading method
    * Searches for source file and includes it if found
    *
    * @static
    * @access public
    *
    * @param string $className
    */
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
