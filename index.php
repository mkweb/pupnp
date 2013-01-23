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
use at\mkweb\upnp\Config;
use at\mkweb\upnp\frontend\AuthManager;

require_once('src/at/mkweb/upnp/init.php');

if(AuthManager::authEnabled()) {

    AuthManager::authenticate();
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
	<script type="text/javascript" src="res/js/phpjs.js"></script>
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
