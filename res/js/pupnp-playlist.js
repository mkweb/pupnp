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

function UPnPPlaylist() {

    this.current = null;
    this.items = {};

    this.load = function(callback) {

        upnp.backend.call(upnp.gui.dstDevice.getUid(), 'getPlaylist', null, function(res) {

            upnp.playlist.current = res.current;
            upnp.playlist.items   = res.items;

            $('#playlist table').empty();

            if(Object.keys(upnp.playlist.items).length > 0) {

                var table = $('#playlist table');

                for(var uid in upnp.playlist.items) {

                    var item = upnp.playlist.items[uid];

                    upnp.playlist.items[uid] = item;

                    var removeLink  = '<a href="javascript:upnp.playlist.remove(\'' + item.id + '\');" title="Remove"><img src="res/images/icons/remove.png" alt="Remove"></a>';
                    var playLink    = '<a href="javascript:upnp.playlist.start(\'' + item.id + '\');" title="Play"><img src="res/images/icons/play.png" alt="Play"></a>';

                    var row = $('<tr' + (upnp.playlist.current == item.id ? ' class="active"' : '') + '></tr>');
                    $(row).append($('<td width="16">' + removeLink + '</td>'));
                    $(row).append($('<td width="16"><img src="res/images/icons/mime/' + item.classname + '.png" alt="' + item.classname + '" alt="' + item.classname + '" /></td>'));
                    $(row).append($('<td>[' + item.devicename + ']</td>'));
                    $(row).append($('<td>' + item.name + '</td>'));
                    $(row).append($('<td width="16">' + playLink + '</td>'));

                    $(table).append(row);
                }

                $('#playlist').fadeIn();
            } else {

                $('#playlist').fadeOut();
            }

            if(undefined != callback) {

                callback();
            }
        });
    }

    this.appendAndStart = function(deviceUid, objectId, name, classname) {

        var doIt = false;
        if(Object.keys(this.items).length == 0 || confirm(upnp.gui.i18n('This will clear your entire playlist. Proceed?'))) {

            doIt = true;
        }

        if(doIt) {

            for(var uid in this.items) {

                this.remove(uid, true);
            }

            this.items = {};

            this.append(deviceUid, objectId, name, classname, function(uid) {

                upnp.playlist.start(uid);
            });
        }
    }

    this.append = function(deviceUid, objectId, name, classname, callback) {

        var device = upnp.gui.devices[deviceUid];

        var item = {
            'device'     : device.getUid(),
            'devicename' : device.getName(),
            'objectId'   : objectId,
            'name'       : name,
            'classname'  : classname
        };

        var data = '';
        for(var key in item) {

            data += '&item[' + key + ']=' + item[key];
        }

        upnp.backend.call(upnp.gui.dstDevice.getUid(), 'addToPlaylist', data, function(res) {

            upnp.playlist.load(function() {

                var uid = res;

                if(undefined != callback) {

                    callback(uid);
                }
            });
        });
    }

    this.appendAlbum = function(deviceUid, albumId) {

        $('#player-loading').show();
        upnp.backend.call(deviceUid, 'getChilds', 'ObjectID=' + albumId, function(res) {

            if(res.NumberReturned > 0) {

                var files = res.Result;

                for(var i in files) {

                    var file = files[i];

                    upnp.playlist.append(deviceUid, file.id, file.title, file.class);
                }
        
                $('#player-loading').hide();
            }
        });
    }

    this.remove = function(uid, force) {

        doIt = false;
        if(undefined == force) force = false;

        if(force == true || confirm(upnp.gui.i18n('Do you really want to remove this item from your playlist?'))) {

            doIt = true;
        }

        if(doIt) {

            upnp.backend.call(upnp.gui.dstDevice.getUid(), 'removeFromPlaylist', 'ItemID=' + uid, function(res) {

                upnp.playlist.load();
            });
        }
    }

    this.start = function(uid) {

        if(upnp.playlist.items[uid] != undefined) {

            var item = upnp.playlist.items[uid];

            this.current = uid;

            upnp.filemanager.play(item.device, item.id, true, function() {

                upnp.playlist.load();
            });
        }
    }

    this.toggle = function() {

        var current = parseInt($('#playlist').css('margin-top'));

        $('#playlist').fadeIn();

        if(current < 0) {

            $('#playlist').animate({
                'margin-top' : 0,
                'opacity'    : 1
            });
        } else {

            $('#playlist').animate({
                'margin-top' : '-480px',
                'opacity'    : 0.5
            });
        }
    }

    this.getNext = function() {

        if(this.current == null) {

            if(this.items.length > 0) {

                return true;
            } else {

                return false;
            }
        }

        var next = null;
        var found = false;
        for(var id in this.items) {

            if(id == this.current) {

                found = true;
                continue;
            }

            if(found) {

                next = id;
                break;
            }
        }

        return next;
    }

    this.getPrevious = function() {

        if(this.current == null) {

            if(this.items.length > 0) {

                return true;
            } else {

                return false;
            }
        }

        var ids = Object.keys(this.items);
        ids.reverse();

        var next = null;
        var found = false;
        for(var i in ids) {

            var id = ids[i];

            if(id == this.current) {

                found = true;
                continue;
            }

            if(found) {

                next = id;
                break;
            }
        }

        return next;
    }

    this.hasNext = function() {

        return (this.getNext() == null);
    }

    this.hasPrevious = function() {

        return (this.getPrevious() == null);
    }
}
