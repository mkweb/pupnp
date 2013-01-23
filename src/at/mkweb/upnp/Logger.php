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

use \Exception;

/**
* Simple Logger
*
* @namespace at.mkweb
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Logger {

    /**
    * File handler
    *
    * @static
    * @access private
    * @var resource
    */
    private static $handler;

    /**
    * Log directory
    *
    * @static
    * @access private
    * @var string
    */
    private static $logdir;

    /**
    * Configured log level
    *
    * @static
    * @access private
    * @var int
    */
    private static $level = 0;

    const LEVEL_NONE    = 0;
    const LEVEL_INFO    = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_DEBUG   = 3;

    /**
    * Intialize logger
    *
    * @static
    * @access public
    *
    * @throws \Exception        If log directory not exists or not writeable
    */
    public static function init() {

        self::$logdir = realpath(Config::read('log_dir'));
        self::$level  = Config::read('log_level');
    
        if(!file_exists(self::$logdir)) {

            throw new Exception('Log directory "' . self::$logdir . '" does not exist.');
        }

        if(!is_writeable(self::$logdir)) {

            throw new Exception('Log directory "' . self::$logdir . '" is not writeable.');
        }
    }

    /**
    * Catchall method which checks if the requested loglevel exists
    * and should be logged
    *
    * @static
    * @access public
    * @param string $method   Requested method (info, warning, debug)
    * @param array  $data     Method parameters
    *
    * @throws \Exception      If filename is not given
    */
    public static function __callStatic($method, $data) {

        self::init();

        if(count($data) != 2) {

            throw new Exception('Filename not given');
        }

        list($str, $filename) = $data;

        $constName = 'self::LEVEL_' . strtoupper($method);

        if(!is_null(constant($constName))) {

            $requestedLevel = constant($constName);

            if(self::$level >= $requestedLevel) {

                self::log(strtoupper($method), $str, $filename);
            }
        }
    }

    /**
    * Writing to the log file in todays log directory
    *
    * @static
    * @access private
    * @param string $level     Requested loglevel
    * @param mixed  $str       String, Array or Object to log
    * @param string $filename  Name of the logfile to create
    */
    private static function log($level, $str, $filename) {

        if(is_array($str) || is_object($str)) {

            $str = print_r($str, true);
        }

        $str = date('Y-m-d H:i:s') . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - [' . strtoupper($level) . '] ' . $str . "\n";

        $dir = rtrim(self::$logdir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');

        self::createDirectory($dir);

        $logfile = $dir . DIRECTORY_SEPARATOR . $filename . '.log';

        file_put_contents($logfile, $str, FILE_APPEND);
    }

    /**
    * To avoid wrong directories because of any mistake
    * this method created todays log directory from base logpath on
    *
    * @static
    * @access private
    * @param string $dir   Directory path
    */
    private static function createDirectory($dir) {

        if($dir[0] != DIRECTORY_SEPARATOR) $dir = realpath($dir);
        $dir = trim(substr($dir, strlen(self::$logdir)), DIRECTORY_SEPARATOR);

        $tmp = explode(DIRECTORY_SEPARATOR, $dir);

        $current = rtrim(self::$logdir, DIRECTORY_SEPARATOR);

        foreach($tmp as $directory) {

            $current .= DIRECTORY_SEPARATOR . $directory;

            if(!file_exists($current)) {

                mkdir($current, 0777);
            }
        }
    }
}
