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
use at\mkweb\Config;

function pr($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}
require_once('src/at/mkweb/Config.php');

Config::init('config.php');

function sendAuthHeader($msg) {
    header('WWW-Authenticate: Basic realm="' . $msg . '"');
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

function loginValid() {

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

if(!isset($_SERVER['PHP_AUTH_USER']) || !loginValid()) {

    sendAuthHeader(_('Please Authenticate'));
}
?>
<html>
<head>
	<title>UPnP Browser</title>

	<link rel="stylesheet" type="text/css" href="res/css/style.css" />
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <link href="res/css/lightbox.css" rel="stylesheet" />

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>

    <script type="text/javascript" src="res/js/lightbox.js"></script>
	<script type="text/javascript" src="res/js/upnp-backend.js"></script>
</head>
<body>

<div id="error" class="hidden"></div>

<div id="wrapper-all">
	<div class="column" id="left">
		<h2><?= _('Source') ?></h2>

		<div class="deviceSelection" id="ds_left">
			<img src="res/images/icons/ajax-loader-small.gif" /> <?= _('Loading devices') ?>
		</div>

		<div class="favorites" id="favorites">
			<img src="res/images/icons/ajax-loader-small.gif" />
		</div>

		<div class="desc" id="desc-left"></div>

		<div class="properties" id="p_left"></div>
	</div>
	<div class="column" id="right">
		<h2><?= _('Destination') ?></h2>

		<div class="deviceSelection" id="ds_right">
			<img src="res/images/icons/ajax-loader-small.gif" /> <?= _('Loading devices') ?>
		</div>

		<div class="desc" id="desc-right">Pogo</div>

		<div class="properties" id="p_right"></div>
	</div>
</div>

</body>
</html>
