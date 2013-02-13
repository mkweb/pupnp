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
 * General Public License along with pUPnP. If not, see <http://www.gnu.org/licenses/>.
 */
use at\mkweb\upnp\Config;
use at\mkweb\upnp\frontend\AuthManager;

require_once('src/at/mkweb/upnp/init.php');

if(AuthManager::authEnabled()) {

    AuthManager::authenticate();
}

$javascript = array(
    '3rdparty/phpjs.js',
    'pupnp-helpers.js',
    'pupnp-backend.js',
    'pupnp-gui.js',
    'pupnp-device.js',
    'pupnp-playlist.js',
    'pupnp-favorites.js',
    'pupnp-file.js',
    'pupnp-filemanager.js',
    'pupnp.js',
    'bootstrap.min.js'
);

$css = array(
    'bootstrap.min.css',
    'style.css',
    'lightbox.css'
);
?>
<html>
<head>
	<title>UPnP Browser</title>

	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <? if(Config::read('minify_css')): ?>

        <link rel="stylesheet" type="text/css" href="resources.php?css=<?= join('|', $css) ?>" />
    <? else: ?>

        <? foreach($css as $cssfile): ?>

            <link rel="stylesheet" type="text/css" href="res/css/<?= $cssfile ?>" />
        <? endforeach ?>
    <? endif ?>

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>

    <? if(Config::read('minify_js')): ?>

        <script type="text/javascript" src="resources.php?js=<?= join('|', $javascript) ?>"></script>
    <? else: ?>

        <? foreach($javascript as $jsfile): ?>

            <script type="text/javascript" src="res/js/<?= $jsfile ?>"></script>
        <? endforeach ?>
    <? endif ?>
</head>
<body>

<div class="navbar navbar-fixed-top"> 
  <div class="navbar-inner"> 
    <div class="container"> 
      <div class="nav-collapse"> 
        <ul class="nav"> 
            <li><a href="index.php" class="active"><?= _('Workspace') ?></a></li>
            <li><a href="devicetest.php"><?= _('Debugging') ?></a></li>
        </ul> 
     </div> 
   </div> 
  </div> 
</div> 

<div id="error" class="hidden"></div>

<div id="wrapper-all" class="container">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span6" id="left">
                <h2><?= _('Source') ?></h2>

                <div class="deviceSelection" id="ds-src">
                    <img src="res/images/icons/ajax-loader-small.gif" /> <?= _('Loading devices') ?>
                </div>

                <div class="favorites" id="favorites"></div>

                <div class="desc" id="desc-src"></div>

                <div class="properties" id="p-src"></div>
            </div>
            <div class="span6" id="right">
                <h2><?= _('Destination') ?></h2>

                <div class="deviceSelection" id="ds-dst">
                    <img src="res/images/icons/ajax-loader-small.gif" /> <?= _('Loading devices') ?>
                </div>

                <div class="desc" id="desc-dst"></div>

                <div class="properties" id="p-dst"></div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
