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
namespace at\mkweb\upnp\frontend;

use at\mkweb\upnp\Config;

class AuthManager {

    public static function authEnabled() {

        $method = Config::get('auth_method');

        // 'none' is mapped to '' by parse_ini_file
        return ($method != '');
    }

    public static function authenticate() {

        $method = Config::get('auth_method');

        $valid = false;
        switch($method) {

            case 'file':

                if(isset($_SERVER['PHP_AUTH_USER']) && self::loginValid_file()) {

                    $valid = true;
                }
                break;
        }

        if(!$valid) {

            self::sendAuthHeader(_('Please Authenticate'));
        }
    }

    private static function sendAuthHeader($msg) {

        header('WWW-Authenticate: Basic realm="' . $msg . '"');
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }

    private static function loginValid_file() {

        $http_user = $_SERVER['PHP_AUTH_USER'];
        $http_pass = $_SERVER['PHP_AUTH_PW'];

        $method = Config::get('auth_method');

        switch($method) {

            case 'file':

                $file = Config::get('auth_file');

                if(file_exists($file)) {

                    $lines = file($file);

                    $users = array();
                    foreach($lines as $line) {

                        list($user, $hash) = explode(':', $line);

                        $users[trim($user)] = trim($hash);
                    }

                    if(array_key_exists($http_user, $users) && $users[$http_user] == $http_pass) {

                        return true;
                    }
                }
                break;

            default: 
                return true;
                break;
        }

        return false;
    }
}
