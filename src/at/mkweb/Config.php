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
namespace at\mkweb;

/**
* Simple config implementation
*
* @namespace at.mkweb
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Config {

    /**
    * Path to config file
    *
    * @static
    * @access private
    * @var string
    */
    private static $file;

    /**
    * Storage for config array
    *
    * @static
    * @access private
    * @var array
    */
    private static $config = array();

    /**
    * Load file content to config storage
    *
    * @static
    * @access public
    *
    * @param string $file   Filename
    */
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

    /**
    * Wrapper for at.mkweb.Config::get()
    *
    * @static
    * @access public
    * @param string $key
    *
    * @return string    or null if not found
    */
    public static function read($key) {

        return self::get($key);
    }

    /**
    * Get config value if exists
    *
    * @static
    * @access public
    * @param string $key
    *
    * @return string    or null if not found
    */
    public static function get($key) {

        return (isset(self::$config[$key]) ? self::$config[$key] : null);
    }
}
