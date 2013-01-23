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
error_reporting(E_ALL);
ini_set('display_errors', 0);

use at\mkweb\upnp\backend\AjaxHandler;
use at\mkweb\upnp\exception\UPnPException;

require_once('src/at/mkweb/upnp/init.php');

$action = $_GET['action']; unset($_GET['action']);

try {
	$handler = new AjaxHandler();
	$handler->call($action, $_GET);

} catch (UPnPException $e) {

	$handler = new AjaxHandler();

	$handler->respond(array(
		'error' => $e->getMessage()
	));
}
