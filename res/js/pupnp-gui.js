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

function UPnPGUI() {

    // TODO set to default icon
    this.defaultIcon = null;

    this.devices = {};
    this.loader  = {};

    this.srcDevice = null;
    this.dstDevice = null;

    this.disableSliderUpdate = false;

    this.currentTransportState = null;

    this.prepare = function() {

        this.loadDevices();

        window.setInterval(function() {

            upnp.gui.updatePlayer();
        }, 1000);
    };

    this.updatePlayer = function() {

        if(null != this.dstDevice) {

            upnp.backend.call(this.dstDevice.getUid(), 'getTransportInfo', null, function(res) {

                switch(res.CurrentTransportState) {

                    case 'PLAYING':

                        upnp.gui.updatePlayerPosition();
                        break;
                }

                upnp.gui.setPlayerAction(res.CurrentTransportState);
            });
        }
    }

    this.setPlayerButtons = function(state) {

        switch(state) {

            case 'play':

                $('#pause').removeClass('hidden');
                $('#pause').show();
                $('#play').addClass('hidden');

                $('#pause').removeClass('disabled');
                $('#stop').removeClass('disabled');

                $('#slider').slider({ disabled: false });
                break;

            case 'pause':

                $('#pause').addClass('hidden');
                $('#play').removeClass('hidden');

                $('#pause').addClass('disabled');

                $('#play').removeClass('disabled');
                $('#stop').removeClass('disabled');
                
                $('#slider').slider({ disabled: false });
                break;

            case 'stop':

                $('#pause').hide();
                $('#play').removeClass('hidden');
                $('#play').addClass('disabled');
                $('#stop').addClass('disabled');

                $('#track-title').text(upnp.gui.i18n('- Nothing is playing - '));
                $('#track-data').hide().empty();

                $('#slider').slider({ disabled: true });
                break;
        }
    }

    this.setPlayerAction = function(action) {

        if(this.currentTransportState != action) {

            this.currentTransportState = action;

            switch(action) {

                case 'PLAYING':

                    this.setPlayerButtons('play');
                    break;

                case 'PAUSED_PLAYBACK':

                    this.setPlayerButtons('pause');
                    break;

                case 'STOPPED':

                    this.setPlayerButtons('stop');
                    break;
            }
        }
    }

    this.updatePlayerPosition = function() {

        upnp.backend.call(this.dstDevice.getUid(), 'getPositionInfo', null, function(res) {

            var data = res.TrackMetaData;

            if(undefined != data.title) {

                $('#track-title').text(data.title);

                if($('#track-data').children().length == 0) {

                    upnp.backend.call(upnp.gui.dstDevice.getUid(), 'getCurrentInfoHtml', '', function(res) {

                        $('#track-data').html(res);
                        $('#track-data').show();
                    });
                }

                if(undefined != res.AbsTime) {

                    $('#time-current').text(res.RelTime);
                    $('#time-all').text(res.TrackDuration);

                    var duration = (null == res.TrackDuration ? null : res.TrackDuration);
                    var currentTime = (null == res.RelTime ? null : res.RelTime);

                    var percentage = getPercentage(currentTime, duration);

                    if(!upnp.gui.disableSliderUpdate) {

                        $('#slider').slider({
                            max : 100,
                            value: percentage,
                            start: function(ev, ui) {

                                upnp.gui.disableSliderUpdate = true;
                            },
                            slide: function(ev, ui) {

                                upnp.gui.disableSliderUpdate = true;
                                var all = $('#time-all').html();

                                $('#slider-tooltip').html(getTimeByPercentage(all, ui.value));
                                $('#slider-tooltip').fadeIn();
                            },
                            stop: function(ev, ui) {

                                $('#player-loading').show();
                                $('#slider-tooltip').fadeOut();

                                var all = $('#time-all').text();

                                var time = getTimeByPercentage(all, ui.value);

                                upnp.backend.call(upnp.gui.dstDevice.getUid(), 'Seek', 'InstanceID=0&Unit=REL_TIME&Target=' + time, function() {

                                    $('#player-loading').hide();

                                    window.setTimeout(function() {

                                        upnp.gui.disableSliderUpdate = false;
                                    }, 3000);
                                });
                            }
                        });
                    }

                    $('#slider').hover(function(e) {

                        window.mouseXPos = e.pageX;
                        window.mouseYPos = e.pageY;

                        if($('#slider-tooltip').length < 1) {

                            var pos = $('#slider').position();

                            $(document.body).append($('<div id="slider-tooltip"></div>'));

                            $('#slider-tooltip').css('top', pos.top - $('#slider').height());
                            $('#slider-tooltip').css('left', window.mouseXPos);
                        }

                        var all = $('#time-all').html();

                        $('#slider-tooltip').html(getTimeByPercentage(all, $('#slider').val()));
                    }, function(e) {

                        $('#slider-tooltip').remove();
                    });

                    $('#slider').mousemove(function(e) {

                        $('#slider-tooltip').css('left', window.mouseXPos);
                    });

                }
            } else {

                upnp.gui.disablePlayer();
            }
        });
    }

    this.control = function(action) {

        $('#player-loading').show();
        upnp.backend.call(this.dstDevice.getUid(), action, null, function(res) {

            upnp.playlist.load();

            $('#player-loading').hide();
        });
    }

    this.i18n = function(str) {

        return str;
    };

    this.unsetLoader = function(column) {

        if(null != upnp.gui.loader[column]) {

            var id = upnp.gui.loader[column];

            $('#' + id).remove();
            upnp.gui.loader[column] = null;
        }
    };

    this.setError = function(message) {

        upnp.gui.unsetLoader('left');
        upnp.gui.unsetLoader('right');
        upnp.gui.showLoadingLayer();

        $('#error').html(message);
        $('#error').slideDown();

        window.setTimeout(function() {

            $('#error').slideUp();
            $('#error').html('');
        }, 3000);
    };

    this.loadDevices = function() {

        var gui = this;
        upnp.backend.call(null, 'getDevices', null, function(res) {

            var dropdown_src = $('<select id="device-src" rel="src"></select>');
            var dropdown_dst = $('<select id="device-dst" rel="dst"></select>');

            $(dropdown_src).append($('<option value="">-- ' + gui.i18n('Please choose') + ' --</option>'));
            $(dropdown_dst).append($('<option value="">-- ' + gui.i18n('Please choose') + ' --</option>'));

            for(var uid in res) {

                var device = new UPnPDevice(uid, res[uid]);
                gui.devices[uid] = device;

                if(device.hasService('ContentDirectory')) {

                    $(dropdown_src).append($('<option value="' + uid + '" data-icon="resources.php?image=' + device.icons[0].url + '&sq=18">' + device.getName() + '</option>'));
                }

                if(device.hasService('AVTransport')) {

                    $(dropdown_dst).append($('<option value="' + uid + '" data-icon="resources.php?image=' + device.icons[0].url + '&sq=18">' + device.getName() + '</option>'));
                }
            }

            $('#ds-src').html('<label class="control-label" for="device-src">' + upnp.gui.i18n('Device') + ':</label> ').append(dropdown_src);
            $('#ds-dst').html('<label class="control-label" for="device-dst">' + upnp.gui.i18n('Device') + ':</label> ').append(dropdown_dst);

            $('#device-src, #device-dst').change(function() {

                upnp.filemanager.containerId = 0;
                gui.deviceChanged(this);
            });
        
            gui.loadFavorites();
        });
    };

    this.deviceChanged = function(dropdown) {

        var uid = $(dropdown).val();
        var rel = $(dropdown).attr('rel');

        var device = this.devices[uid];

        switch(rel) {

            case 'src':

                this.srcDevice = (undefined == device ? null : device);

                this.loadSourceDevice(device);
                break;

            case 'dst':

                this.dstDevice = (undefined == device ? null : device);

                upnp.gui.drawFiletable();

                if(undefined != device) {

                    this.loadRenderer(device);
                }
                break;
        }
    };

    this.loadSourceDevice = function(device) {

        upnp.filemanager.load(device.getUid());
    };

    this.loadRenderer = function(device) {

        upnp.playlist.load();

        $('#p-dst').slideUp();
        $('#p-dst').empty();

        var name = '- Nothing is playing - ';

        var controls = $('<div id="controls"></div>');
        var title = $('<div class="title"><img src="res/images/icons/ajax-loader-player.gif" id="player-loading">Now Playing: <span id="track-title">' + name + '</span></div>');

        var table = $('<table></table>');
        var tbody = $('<tbody></tbody>');

        var progressRow = $('<tr><td width="50" id="time-current">00:00:00</td><td><div id="slider"></div></td><td width="50" id="time-all">00:00:00</td></tr>');
        var buttonRow = $('<tr></td>');
        var buttonCol = $('<td colspan="3"></td>');

        var buttonList = $('<ul class="buttons"></ul>');

        $(buttonCol).append(buttonList);
        $(buttonRow).append(buttonCol);

        $(tbody).append(progressRow);
        $(tbody).append(buttonRow);

        $(buttonList).append('<li><a href="javascript:upnp.gui.control(\'play\');" class="control disabled" id="play" title="' + upnp.gui.i18n('Play') + '" /></li>');
        $(buttonList).append('<li><a href="javascript:upnp.gui.control(\'pause\');" class="control disabled hidden" id="pause" title="' + upnp.gui.i18n('Pause') + '" /></li>');
        $(buttonList).append('<li><a href="javascript:upnp.gui.control(\'stop\');" class="control disabled" id="stop" title="' + upnp.gui.i18n('Stop') + '" /></li>');

        $(table).append(tbody);
        $(controls).append(title);
        $(controls).append(table);


        var html_playlist = '<div id="playlist">';
        html_playlist += '    <table></table>';
        html_playlist += '</div>';

        var playlist = $(html_playlist);
        $(controls).append(playlist);

        $('#p-dst').append(controls);
        $('#p-dst').append($('<div id="track-data"></div>'));
        $('#p-dst').slideDown();

        $('#slider').slider({ disabled: true });
    };

    this.loadFavorites = function(deviceUid) {

        var favorites = upnp.favorites.getAll();
    }

    this.drawFiletable = function() {

        var isFavorit = upnp.favorites.isFavorite();

        var navbar = $('<div id="navbar"></div>');

        if(Object.keys(upnp.filemanager.breadcrumps).length > 0) {

            var breadcrumplist = $('<ul id="breadcrumps"></ul>');
            for(var uid in upnp.filemanager.breadcrumps) {

                if(uid != upnp.filemanager.objectId) {

                    $(breadcrumplist).append($('<li><a href="javascript:upnp.filemanager.open(\'' + uid + '\');"' + (uid == upnp.filemanager.containerId ? ' class="active"' : '') + '>' + upnp.filemanager.breadcrumps[uid] + '</a></li>'));
                }
            }

            $(navbar).append(breadcrumplist);
        }

        var favLink = $('<a href="javascript:upnp.favorites.toggle();" id="favorite" title="' + upnp.gui.i18n('Set/Unset as favorite') + '"' + (isFavorit ? ' class="active"' : '') + ' /></a>');
        $(navbar).append(favLink);
        $(navbar).append($('<br class="clear" />'));

        var html = '<div id="search">';
        html += '<span>' + this.i18n('Search') + ': </span>';
        html += '   <input type="text" id="searchbar" value="" />';
        html += '   <img id="search-enter" src="res/images/icons/keyboard-enter.png" style="display: none;" />';
        html += '</div>';

        html = '<div class="control-group">';
        html+= '  <div class="controls">';
        html+= '    <div class="input-prepend">';
        html+= '      <span class="add-on"><i class="icon-search"></i></span>';
        html+= '      <input class="span12" id="searchbar" type="text">';
        html+= '      <img id="search-enter" src="res/images/icons/keyboard-enter.png" style="display: none;" />';
        html+= '    </div>';
        html+= '  </div>';
        html+= '</div>';

        var search = $(html);

        var allowedMimes = [];

        if(null != upnp.gui.dstDevice) {

            allowedMimes = upnp.gui.dstDevice.getProtocols();
        }

        var files = upnp.filemanager.getFiles();

        var cnt = Object.keys(files).length;

        if(cnt > 0) {

            var table = $('<div id="filetable"></div>');

            for(var id in files) {

                var file = files[id];

                var html = '<div class="filerow">';
                html += '   <div class="mime">';
                html += '       <img title="' + file.getClass() + '" alt="' + file.getClass() + '" src="res/images/icons/mime/' + file.getClass() + '.png" />';
                html += '   </div>';
                html += '   <a id="item-' + file.getId() + '" href="javascript:upnp.filemanager.' + (file.getType() == 'container' ? 'open' : 'details') + '(\'' + file.getId() + '\');">' + file.getTitle() + '</a>';
                html += '   <div class="right">';

                if(!in_array(file.getClass(), ['object.container', 'object.container.storageFolder'])) {

                    if(in_array(file.getMimeType(), allowedMimes)) {

                        html += '   <a href="javascript:upnp.playlist.append(\'' + this.srcDevice.getUid() + '\', \'' + file.getId() + '\', \'' + file.getTitle() + '\', \'' + file.getClass() + '\');" title="' + upnp.gui.i18n('Play') + '">';
                        html += '       <img src="res/images/icons/add.png" alt="' + upnp.gui.i18n('Add to playlist') + '" />';
                        html += '   </a>';

                        html += '   <a href="javascript:upnp.playlist.appendAndStart(\'' + this.srcDevice.getUid() + '\', \'' + file.getId() + '\', \'' + file.getTitle() + '\', \'' + file.getClass() + '\');" title="' + upnp.gui.i18n('Play') + '">';
                        html += '       <img src="res/images/icons/play.png" alt="' + upnp.gui.i18n('Play') + '" />';
                        html += '   </a>';
                    } else {

                        html += '   <a href="javascript:void();" title="' + upnp.gui.i18n('Not possible') + '" style="cursor: default;">';
                        html += '       <img src="res/images/icons/play-gray.png" alt="' + upnp.gui.i18n('Not possible') + '" />';
                        html += '   </a>';
                    }
                } 

                html += '   </div>';
                html += '</div>';

                $(table).append($(html));
            }

            $('#p-src').slideUp('fast', function() {

                $('#p-src').empty();
                $('#p-src').append(navbar);
                $('#p-src').append(search);
                $('#p-src').append(table);
                $('#p-src').slideDown('fast', function() {

                    $('#searchbar').focus();

                    $('#searchbar').keyup(function(ev) {

                        if(ev.keyCode == upnp.KEYCODE_ENTER) {

                            upnp.gui.triggerSearchResult();
                        } else {

                            upnp.gui.search($(this).val());
                        }
                    });
                });
            });
        }
    }

    this.triggerSearchResult = function() {

        if($('#search-enter').css('display') != 'inline') {

            return false;
        }

        var links = $('.filerow');
        var element = null;

        for(var i = 0; i < links.length; i++) {

            var link = $(links[i]);
            if($(link).css('display') == 'block') {

                element = links[i];
                break;
            }
        }

        if(null != element) {

            var link = $(element).children('a')[0];
            var target = $(link).attr('href');

            if(target.substring(0, 10) == 'javascript') {

                var command = target.substring(11);
                eval(command);
            } else {

                location.href = target;
            }
        }
    }

    this.search = function(key) {

        $('.filerow a').each(function() {

            var name = $(this).text().toLowerCase();
            var qry = key.toLowerCase();
           
            if(name.indexOf(qry) > -1) {

                $(this).parent().css('display', 'block');
            } else {

                $(this).parent().css('display', 'none');
            }
        });

        upnp.gui.enableSearchEnterHit();
    }

    this.enableSearchEnterHit = function() {

        var cnt = 0;
        var links = $('.filerow');

        for(var i = 0; i < links.length; i++) {

            var link = $(links[i]);
            if($(link).css('display') == 'block') {

                cnt ++;
            }
        }

        if(cnt == 1) {

            $('#search-enter').css('display', 'inline');
        } else {

            $('#search-enter').css('display', 'none');
        }
    }

    this.initTooltips = function() {

        $('.tooltip').each(function() {

            if($(this).attr('title') != '' && $(this).attr('rel') == undefined) {

                $(this).attr('rel', $(this).attr('title'));
                $(this).attr('title', '');
            }

            var random = Math.floor(Math.random() * 10000);

            $(this).hover(function() {

                $(document.body).css('cursor', 'pointer');

                var html = '<div class="tooltip" id="tooltip-' + random + '">' + $(this).attr('rel') + '</div>';
                var tooltip = $(html);

                var offset = $(this).offset();

                tooltip.css('top', offset.top + $(this).height());
                tooltip.css('left', offset.left + $(this).width());

                $(document.body).append(tooltip);

                $('#tooltip-' + random).fadeIn();

            }, function() {

                $(document.body).css('cursor', 'default');

                $('#tooltip-' + random).fadeOut('fast', function() {

                    $('#tooltip-' + random).remove();
                });
            });
        });
    };

    this.showFileInfo = function(deviceUid, objectId) {

        var html = '<div id="fileinfo" style="display:none"><center><img src="res/images/icons/ajax-loader.gif" />';
        html += '<br />';
        html += '<input type="button" onclick="hideFileInfo();" value="' + upnp.gui.i18n('Cancel') + '" />';
        html += '</center></div>';

        $(document.body).append($('<div id="carpet" style="display:none"></div>'));
        $(document.body).append($(html));

        $('#carpet').fadeIn();
        $('#fileinfo').fadeIn();

        upnp.backend.call(deviceUid, 'getFileInfoHtml', 'ObjectID=' + objectId, function(res) {

            var enabled = false;
            
            if(upnp.gui.dstDevice != null) {

                var file = upnp.filemanager.files[objectId];
                var allowedMimes = upnp.gui.dstDevice.getProtocols();

                if(in_array(file.getMimeType(), allowedMimes)) {

                    enabled = true;
                }
            }

            $('#fileinfo').html(res);

            var html = '<div>';
            html += '<input type="button" onclick="upnp.filemanager.play(\'' + objectId + '\')" value="' + upnp.gui.i18n('Play') + '"' + (!enabled ? ' disabled="disabled"' : '') + ' />';
            html += '<input type="button" onclick="upnp.gui.hideFileInfo();" value="' + upnp.gui.i18n('Close') + '" />';
            html += '</div>';

            $('#fileinfo').append($(html));
        });
    }

    this.hideFileInfo = function() {

        if($('#fileinfo').length > 0) {

            $('#fileinfo').fadeOut('slow', function() {

                $(this).remove();
            });
        }
        
        if($('#carpet').length > 0) {

            $('#carpet').fadeOut('slow', function() {

                $(this).remove();
            });
        }
    }

    this.showLoadingLayer = function(msg) {

        $(document.body).append('<div id="carpet"></div><div id="loading-layer">' + msg + '<br /><img src="res/images/icons/ajax-loader.gif" /><br /><input type="button" onclick="hideLoadingLayer();" value="' + this.i18n('Cancel') + '" /></div>');
    },

    this.hideLoadingLayer = function() {

        $('#carpet').remove();
        $('#loading-layer').remove();
    }
}
