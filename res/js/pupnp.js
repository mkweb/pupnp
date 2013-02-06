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

function UPnP() {

    this.KEYCODE_ENTER = 13;

    this.backend        = new UPnPBackend();
    this.gui            = new UPnPGUI();
    this.favorites      = new UPnPFavorites();
    this.filemanager    = new UPnPFileManager();
    this.playlist       = new UPnPPlaylist();
}

var upnp;

window.onload = function() {

    upnp = new UPnP();

    upnp.gui.prepare();
}
