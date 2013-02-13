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

function UPnPFavorites() {

    this.favorites = {};

    this.getAll = function() {

        upnp.backend.call(null, 'getFavorites', null, function(favorites) {

            var cnt = Object.keys(favorites).length;

            if(cnt > 0) {

                var added = 0;
                var dropdown = $('<select><option value="">-- ' + upnp.gui.i18n('Please choose') + ' --</option></select>');

                for(var uid in favorites) {

                    upnp.favorites.favorites[uid] = favorites[uid];

                    if(undefined != upnp.gui.devices[favorites[uid].deviceId]) {

                        var option = $('<option value="' + uid + '">' + favorites[uid].deviceName + ' - ' + favorites[uid].path + '</option>');

                        $(dropdown).append(option);
                        added ++;
                    }
                }

                if(added > 0) {

                    $('#favorites').html('<label class="control-label" for="device-src">' + upnp.gui.i18n('Favorites') + ':</label> ');
                    $('#favorites').append(dropdown);
                    $('#favorites').slideDown();

                    $('#favorites select').change(function() {

                        var uid = $(this).val();

                        if(undefined != upnp.favorites.favorites[uid]) {

                            var deviceId, objectId;
                            with(upnp.favorites.favorites[uid]) {

                                upnp.filemanager.breadcrumps = eval('(' + breadcrumps + ')');

                                $('#device-src').val(deviceId);
                                upnp.filemanager.load($('#device-src').val(), objectId);

                                upnp.gui.srcDevice = upnp.gui.devices[$('#device-src').val()];

                                $(this).val('');
                            }
                        }
                    });
                } else {

                    $('#favorites').slideUp();
                }
            } else {

                $('#favorites').slideUp();
            }
        });
    }

    this.isFavorite = function(deviceUid, objectId) {

        var uid = upnp.filemanager.deviceUid + '---' + upnp.filemanager.containerId;

        return !(undefined == this.favorites[uid]);
    }

    this.toggle = function() {

        if(this.isFavorite()) {

            $('a#favorite').removeClass('active');
            this.remove();
        } else {

            $('a#favorite').addClass('active');
            this.add();
        }
    }

    this.add = function() {

        var device = upnp.gui.srcDevice;
        var uid = device.getUid() + '---' + upnp.filemanager.containerId;

        var path = '';
        for(var i in upnp.filemanager.breadcrumps) {

            path += '/' + upnp.filemanager.breadcrumps[i];
        }

        var data = 'deviceId=' + device.getUid() + '&deviceName=' + device.getName() + '&objectId=' + upnp.filemanager.containerId + '&path=' + path + '&breadcrumps=' + json_encode(upnp.filemanager.breadcrumps).split('&').join('%26');

        upnp.backend.call(null, 'addFavorite', data, function(res) {

            upnp.favorites.getAll();
        });
    }

    this.remove = function() {

        var uid = upnp.gui.srcDevice.getUid() + '---' + upnp.filemanager.containerId;

        upnp.backend.call(null, 'removeFavorite', 'uid=' + uid, function(res) {

            upnp.favorites.getAll();
        });
    }
}
