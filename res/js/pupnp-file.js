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

function UPnPFile(obj) {

    this.id         = obj.id;
    this.parentId   = obj.parentId;
    this.title      = obj.title;
    this.type       = obj.type;
    this.childCount = obj.childCount;
    this.className  = obj.class;

    if(undefined != obj['res-protocolInfo']) {

        this.mimeType   = obj['res-protocolInfo'].split(':')[2];
    }

    this.getId = function() {

        return this.id;
    }

    this.getTitle = function() {

        return this.title;
    }

    this.getClass = function() {

        return this.className;
    }

    this.getType = function() {

        return this.type;
    }

    this.getChildCount = function() {

        return this.childCount;
    }

    this.getMimeType = function() {

        return this.mimeType;
    }
}
