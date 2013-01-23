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
* Simple config implementation
*
* @namespace at.mkweb
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Config {

    const TYPE_STR  = 'string';
    const TYPE_INT  = 'int';
    const TYPE_ENUM = 'enum';

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

    private static $values = array();

    /**
    * Load file content to config storage
    *
    * @static
    * @access public
    *
    * @param string $file   Filename
    */
    public static function init($file) {
    
        self::initValues();

        self::$file = $file;

        $config = array();

        if(!file_exists($file)) {

            self::writeFile();
        }

        self::$config = parse_ini_file($file);
    }

    private static function writeFile() {

        $lines = array();

        foreach(self::$values as $code => $data) {

            $name       = $data->name;
            $type       = $data->type;
            $desc       = (isset($data->desc) ? $data->desc : null);
            $default    = (isset($data->default) ? $data->default : null);
            $values     = (isset($data->values) ? $data->values : null);

            $lines[] = '; ' . $data->name;
                    
            if(!is_null($desc)) {

                $lines[] = '; ' . $desc;
            }
        
            switch($type) {

                case self::TYPE_ENUM:

                    $lines[] = '; Possible values:';
                    foreach($values as $key => $name) {

                        $lines[] = ';   ' . $key . ' = ' . $name; 
                    }
                    break;

                case self::TYPE_STR:
                case self::TYPE_INT:

                    $lines[] = '; Value: ' . $type;
                    break;
            }

            if(!is_null($default)) {

                $lines[] = '; Default: ' . $default;
            }

            $lines[] = $code . ' = ' . $default;
            
            $lines[] = '';
        }

        $content = trim(join("\n", $lines));

        file_put_contents(self::$file, $content);
    }

    private static function initValues() {

        // Fill here because method calls (gettext) are not valid in property declaration
        self::$values = array(
            'auth_method' => (Object)array(
                'name'      => _('Authentication method'),
                'type'      => self::TYPE_ENUM,
                'default'   => 'none',
                'values'    => array(
                    'none' => _('No authentication'), 
                    'file' => _('Read users from file')
                )
            ),
            'auth_file' => (Object)array(
                'name'      => _('Auth file'),
                'desc'      => _('If "file" is selected as auth_method this file is used as user store'),
                'type'      => self::TYPE_STR,
                'default'   => '.htpasswd'
            ),
            'log_level' => (Object)array(
                'name'      => _('Log level'),
                'type'      => self::TYPE_ENUM,
                'default'   => 1,
                'values'    => array(
                    '0' => _('Disable logging'),
                    '1' => _('Info'),
                    '2' => _('Warning'),
                    '3' => _('Debug')
                )
            ),
            'log_dir' => (Object)array(
                'name'      => _('Log directory'),
                'desc'      => _('If "file" is selected as auth_method this file is used as user store'),
                'type'      => self::TYPE_STR,
                'default'   => './logs'
            ),
        );
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
