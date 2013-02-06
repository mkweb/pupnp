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

function UPnPDevice(uid, obj) {

    this.uid        = uid;
    this.name       = obj.name;
    this.services   = obj.services;
    this.protocols  = obj.protocols;
    this.icons      = obj.icons;

    this.getName = function() {

        return this.name;
    }

    this.getUid = function() {

        return this.uid;
    }

    this.getServices = function() {

        return this.services;
    }

    this.hasService = function(service) {

        var result = false;
        for(var key in this.services) {

            if(this.services[key] == service) {

                result = true;
                break;
            }
        }

        return result;
    }

    this.hasProtocols = function() {

        return (this.protocols.length > 0);
    }

    this.getProtocols = function() {

        return this.protocols;
    }

    this.hasIcons = function() {

        return (this.icons.length > 0);
    }

    this.getIcons = function() {

        return this.icons;
    }
}
