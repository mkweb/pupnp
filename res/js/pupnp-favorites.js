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
var Favorites = {

    toggle : function() {

        var elem = $('a#favorite');
        
        var deviceId = $('#device-left').val();
        var uid = deviceId + '---' + currentObject.id;

        if($(elem).hasClass('active')) {

            this.remove(uid);
            $(elem).removeClass('active');
        } else {

            this.add();
            $(elem).addClass('active');
        }
    },

    add : function() {

        var deviceId = $('#device-left').val();
        var deviceName = $('#device-left').find('option[value="' + deviceId + '"]').text();
        var uid = deviceId + '---' + currentObject.id;

        var path = '';
        for(var i in breadcrumps) {

            path += '/' + breadcrumps[i];
        }

        var data = 'deviceId=' + deviceId + '&deviceName=' + deviceName + '&objectId=' + currentObject.id + '&path=' + path + '&breadcrumps=' + json_encode(breadcrumps).split('&').join('%26');

        UPnPBackend.call(null, 'addFavorite', data, function(res) {

            Favorites.update();
        });
    },

    remove : function(uid) {

        UPnPBackend.call(null, 'removeFavorite', 'uid=' + uid, function(res) {

            Favorites.update();
        });
    },

    update : function() {

        UPnPBackend.call(null, 'getFavorites', null, function(res) {

            if(Object.size(res) == 0) {

                $('#favorites').slideUp();
            } else {

                favorites = res;

                var div = $('<div>' + i18n('Favorites: ') + '</div>');
                var dropdown = $('<select><option value="">-- ' + i18n('Please select') + ' --</option></select>');

                for(var uid in res) {

                    var option = $('<option value="' + uid + '">' + utf8_decode(res[uid].deviceName + ' - ' + res[uid].path) + '</option>');
                    $(dropdown).append(option);
                }

                $(div).append(dropdown);

                $('#favorites').empty();
                $('#favorites').append(div);

                $('#favorites').slideDown();

                $('#favorites select').change(function() {

                    Gui.showLoadingLayer(i18n('Loading favorite'));

                    var uid = $(this).val();
                    $('#favorites select').val('');

                    if(uid != '') {

                        var tmp = uid.split('---');

                        var deviceId = tmp[0];
                        var objectId = tmp[1];

                        $('#device-left').val(deviceId);

                        currentObject = {
                            'id'   : objectId,
                            'name' : favorites[uid].objectName
                        };

                        var bc = favorites[uid].breadcrumps;
                        breadcrumps = eval('(' + bc + ')');

                        deviceSelected('left');
                        loadFiles(currentObject.id);
                    } else {

                        if($('#device-left').val() != '') {

                            loadFiles(currentObject.id);
                        }
                    }

                    Gui.hideLoadingLayer();
                });
            }
        });
    },

    isFavorite : function() {

        var deviceId = $('#device-left').val();
        var check_uid = deviceId + '---' + currentObject.id;

        for(var uid in favorites) {

            if(uid == check_uid) {

                return true;
            }
        }

        return false;
    }
}
