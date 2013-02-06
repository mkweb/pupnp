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

function UPnPFileManager(id) {

    this.deviceUid   = null;
    this.containerId = 0;
    this.breadcrumps = {};
    this.files       = {};

    this.load = function(deviceUid, containerId) {

        this.deviceUid = deviceUid;

        if(undefined != containerId) {

            this.containerId = containerId;
        } else {

            this.breadcrumps = {};
            this.breadcrumps[this.containerId] = upnp.gui.i18n('Root');
        }

        $('#p-src').empty().html('<center><img src="res/images/icons/ajax-loader.gif" /></center>').slideDown();
        this.files = {};

        upnp.backend.call(this.deviceUid, 'getChilds', 'ObjectID=' + this.containerId, function(res) {

            if(res.NumberReturned > 0) {

                var files = res.Result;

                for(var i in files) {

                    var file = files[i];

                    upnp.filemanager.files[file.id] = new UPnPFile(file);
                }
            }

            upnp.gui.drawFiletable();
        });
    }

    this.getFiles = function() {

        return this.files;
    }

    this.open = function(id) {

        // Load from current filelist
        if(undefined != this.files[id]) {

            var file = this.files[id];
            this.breadcrumps[file.getId()] = file.getTitle();
        
            this.load(this.deviceUid, id);

        } else if(undefined != this.breadcrumps[id]) {

            var newcrumps = {};
            for(var uid in this.breadcrumps) {

                newcrumps[uid] = this.breadcrumps[uid];
                if(uid == id) {

                    break;
                }
            }
            
            this.breadcrumps = newcrumps;

            this.load(this.deviceUid, id);
        }
    }

    this.details = function(id) {

        if(undefined != this.files[id]) {

            upnp.gui.showFileInfo(this.deviceUid, id);
        }
    }

    this.play = function(srcDeviceId, objectId, playlist, callback) {

        if(undefined == playlist) playlist = false;

        $('#player-loading').show();

        upnp.backend.call(srcDeviceId, 'getMetaData', 'ObjectID=' + objectId, function(res) {

            for(var title in res.Result) {

                var data = res.Result[title];
                break;
            }

            var mime = (undefined == data ? null : data.mimeType.split(':')[2]);

            /*
            if(mime != null && !in_array(mime, upnp.gui.dstDevice.getProtocols())) {

                upnp.gui.setError(upnp.gui.i18n('Selected device does not support files with type ' + mime));
                upnp.gui.hideLoadingLayer();
            }
            */

            var artist = (undefined == data ? null : data.artist);
            var album = (undefined == data ? null : data.album);
            var originalTrackNumber = (undefined == data ? null : data.originalTrackNumber);
            var genre = (undefined == data ? null : data.genre);

            var url = (undefined == data ? null : data.res);
            var xml = (undefined == data ? null : res.Result_XML.split('<').join('&lt;').split('>').join('&gt;').split('&').join('amp;'));

            var action = (playlist ? 'StartPlayFromPlaylist' : 'StartPlay');

            upnp.backend.call(upnp.gui.dstDevice.getUid(), action, 'source=' + srcDeviceId + '&id=' + objectId, function(res) {

                $('#player-loading').hide();

                if(undefined != callback) {

                    callback();
                }
            });
        });
    }
}
