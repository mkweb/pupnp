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
var currentFiles = {};
var intv_playing = null;
var breadcrumps = {};
var lastFileResult = null;
var searchResult = null;
var allowedMimes;
var currentObject = {
    'id'   : 0,
    'name' : i18n('Root')
};
var disableSliderUpdate = false;
var counter = 0;
var favorites = {};

function i18n(msg) {

	return msg;
}

window.onload = function() {

	$('*').mousemove(function(e){
		window.mouseXPos = e.pageX;
		window.mouseYPos = e.pageY;
	}); 

	var sites = { 
		'left' : 'ContentDirectory', 
		'right' : 'AVTransport' 
	};

	for(var site in sites) {

		var service = sites[site];

		UPnPBackend.call(null, 'getDevices', (service != null ? 'service=' + service + '&' : '') + 'callback=' + site, function(res) {

			var site = res.callback;
			delete res.callback;

			var dropdown = $('<select><option value="">-- ' + i18n('Please select') + ' --</option></select>');

			for(var id in res) {

				var device = res[id];

				$(dropdown).append($('<option value="' + id + '">' + device.name + '</option>'));
			}

			var loader = $('<img src="res/images/icons/ajax-loader-small.gif" id="device-' + site + '-loading" style="display: none;" />');

			$(dropdown).attr('id', 'device-' + site);

			$('#ds_' + site).html(i18n('Device') + ': ');
			$('#ds_' + site).append(dropdown);
			$('#ds_' + site).append(loader);

            Favorites.update();

			$('#device-' + site).change(function() {

                $('#favorites select').val('');

                deviceSelected(site);

                if(site == 'left' && $('.properties').css('display') != 'none') {

                    breadcrumps = {};
                    currentObject = {'id' : 0, 'name' : i18n('Root') };

                    loadFiles(0);
                }
            });
		});
	}
}

function deviceSelected(site) {

    $('#device-' + site + '-loading').show();

    var dropdown = $('#device-' + site);

    if($(dropdown).val() == '') {

        return false;
    }

    var column = $(dropdown).attr('id').split('-')[1];
    var device = $(dropdown).val();

    UPnPBackend.call(device, 'getDeviceData', null, function(res) {

        var html = '';
        if(undefined != res.icons[0]) {

            var icon = 'resources.php?image=' + res.icons[0].url + '&sq=30';
            html += '<img src="' + icon + '" alt="' + res.friendlyName + '" />';
        }

        html += '<h2 class="' + column + '">' + res.friendlyName + '</h2>';
        
        if($('#desc-' + column).css('display') != 'none') {

            $('#desc-' + column).slideUp('fast', function() {

                $('#desc-' + column).html(html);
                $('#desc-' + column).slideDown();
            });
        } else {

            $('#desc-' + column).html(html);
            $('#desc-' + column).slideDown();
        }

        $('#device-' + column + '-loading').hide();

        if(column == 'right') {

            $('#desc-' + column).append($('<img src="res/images/icons/ajax-loader-small.gif" id="device-' + site + '-mediatype-loading" /><br class="clear" id="tmpclear-' + site + '" />'));
            UPnPBackend.call(device, 'getProtocolInfo', 'callback=' + column, function(res) {

                if(undefined != res.Sink) {

                    var allowed = res.Sink.split(',');
                    allowedMimes = allowed;

                    var html = '<img class="tooltip" src="res/images/icons/info.png" title="<b>' + i18n('Possible Media Types') + ':</b><br />';

                    for(var i in allowed) {

                        html += allowed[i].split('http-get:').join('') + '<br />';
                    }

                    html += '" />';

                    var info = $(html);

                    $('#device-' + res.callback + '-mediatype-loading').remove();
                    $('#tmpclear-' + res.callback).remove();

                    $('#desc-' + column).append(info);
                    $('#desc-' + column).append('<br class="clear" />');

                    Gui.initTooltips();
                }
            });
        }
    });

    switch(column) {

        case 'left':

            if(null != currentObject) {

                loadFiles(currentObject.id, currentObject.name);
            }
            break;

        case 'right':

            if(null != currentObject) {

                if($('.properties').css('display') != 'none') {
                loadFiles(currentObject.id, currentObject.name);
                }
            }

            currentPlaying();
            break;
    }
}

function getPercentage(now, all) {

	var zero = getUnixTimestamp('01.01.2012 00:00:00');

	var unix_now = getUnixTimestamp('01.01.2012 ' + now);
	var unix_all = getUnixTimestamp('01.01.2012 ' + all);

	var now = unix_now - zero;
	var all = unix_all - zero;

	return Math.round((now * 100) / all);
}

function getTimeByPercentage(all, percentage) {

	var zero = getUnixTimestamp('01.01.2012 00:00:00');
	all = getUnixTimestamp('01.01.2012 ' + all) - zero;

	return getTime((all * percentage) / 100);
}

function currentPlaying() {

	if($('#p_right').html() == '') {

		$('#p_right').html('<center><img src="res/images/icons/ajax-loader.gif" /></center>');
		$('#p_right').show();
	}

	var device = $('#device-right').val();

	UPnPBackend.call(device, 'getTransportInfo', null, function(res) {

        if(res.CurrentTransportState != 'PLAYING') {

			$('#p_right').text(i18n('Currently nothing is playing.'));

			window.setTimeout('currentPlaying()', 1000);
        } else {

            UPnPBackend.call(device, 'getPositionInfo', null, function(res) {

                var data = res.TrackMetaData;

                    var title = (undefined == data ? null : data.title);
                    var duration = (null == res.TrackDuration ? null : res.TrackDuration);
                    var currentTime = (null == res.RelTime ? null : res.RelTime);

                    var percentage = getPercentage(currentTime, duration);

                    var html = '<div id="controls">'; 
                    html += '<div style="margin-bottom: 6px;"><img src="res/images/icons/ajax-loader-player.gif" id="player-loading"/>' + i18n('Now Playing') + ': ' + title + '</div>';
                    html += '<table>';
                    html += '<tr>';
                    html += '<td width="50" id="time-current">' + currentTime + '</td><td><div id="slider"></div></td><td width="50" id="time-all">' + duration + '</td>';
                    html += '</tr>';
                    html += '<tr>';
                    html += '<td colspan="3">';
                    html += '<ul class="buttons">';
                    html += 	'<li><a href="javascript:playControl(\'play\');" class="control disabled" style="display: none;" id="play" title="' + i18n('Play') + '"></a></li>';
                    html += 	'<li><a href="javascript:playControl(\'pause\');" class="control disabled" style="display: none;" id="pause" title="' + i18n('Pause') + '"></a></li>';
                    html += 	'<li><a href="javascript:playControl(\'stop\');" class="control disabled" id="stop" title="' + i18n('Stop') + '"></a></li>';
                    html += '</ul>';
                    html += '</td>';
                    html += '</tr>';
                    html += '</table>';
                    html += '</div>';

                    html += '<div id="mediainfo"></div>';

                    html += '<input type="hidden" id="currently-playing-id" value="' + (undefined == data ? null : data.id) + '" />';

                    $('#p_right').html(html);

                    UPnPBackend.call($('#device-right').val(), 'getCurrentInfoHtml', '', function(res) {

                        $('#mediainfo').html(res);
                    });

                    $('#slider').slider({
                        max : 100,
                        value: percentage,
                        start: function(ev, ui) {

                            disableSliderUpdate = true;
                        },
                        slide: function(ev, ui) {

                            var all = $('#time-all').html();

                            $('#slider-tooltip').html(getTimeByPercentage(all, ui.value));
                            $('#slider-tooltip').fadeIn();
                        },
                        stop: function(ev, ui) {

                            $('#player-loading').show();
                            $('#slider-tooltip').fadeOut();

                            var all = $('#time-all').html();

                            var time = getTimeByPercentage(all, ui.value);

                            UPnPBackend.call($('#device-right').val(), 'Seek', 'InstanceID=0&Unit=REL_TIME&Target=' + time, function() {

                                $('#player-loading').hide();

                                window.setTimeout(function() {

                                    disableSliderUpdate = false;
                                }, 3000);
                            });
                        }
                    });

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

                    watchPlaying();
                    intv_playing = window.setInterval('watchPlaying()', 1000);
            });
        }
    });
}

function playControl(action) {

	var button = null;

    if($('#' + action).length > 0) {

        $('#' + action).addClass('disabled');
    }

	if(action == 'play' || action == 'pause') {

		$('#player-loading').show();
		disableLink(button);

        var button = (action == 'pause' ? $('#pause') : $('#play'));

        UPnPBackend.call($('#device-right').val(), action, null, function() {

            enableLink(button);

            $('#player-loading').hide();
            
            $('#' + action).removeClass('disabled');
        });

    } else if (action == 'stop') {

        UPnPBackend.call($('#device-right').val(), 'Stop', null, function() {

            var button = $('#stop');

            enableLink(button);

            $('#player-loading').hide();

            $('#stop').removeClass('disabled');
        });
	}
}

function watchPlaying() {

	var device = $('#device-right').val();

	if($('#slider').length > 0) {
		
        UPnPBackend.call(device, 'getTransportInfo', null, function(res) {

            if(undefined != res.CurrentTransportState) {

                switch(res.CurrentTransportState) {

                    case 'PLAYING':

                        $('#play').hide();

                        $('#stop').removeClass('disabled');

                        $('#pause').removeClass('disabled');
                        $('#pause').show();
                        break;

                    case 'PAUSED_PLAYBACK':

                        $('#pause').hide();
                        $('#stop').removeClass('disabled');

                        $('#play').removeClass('disabled');
                        $('#play').show();
                        break;

                    case 'STOPPED':

                        $('#stop').addClass('disabled');

                        $('#play').removeClass('disabled');
                        $('#play').show();

                        $('#pause').hide();

                        currentPlaying();
                        break;
                }
            }
        });

		UPnPBackend.call(device, 'getPositionInfo', null, function(res) {

			var data = res.TrackMetaData;

			if(undefined == data || res.TrackDuration == '00:00:00') {

				if(intv_playing != null) {

					window.clearInterval(intv_playing);
					intv_playing = null;
				}

				$('#p_right').text(i18n('Currently nothing is playing.'));

				window.setTimeout('currentPlaying()', 1000);
			} else {

				if(data.id != $('#currently-playing-id').val()) {

					$('#p_right').empty();
					$('#p_right').text(i18n('Refreshing.'));

					window.setTimeout('currentPlaying()', 2000);
					return true;
				}

				var title = data.title;
				var duration = res.TrackDuration;
				var currentTime = res.RelTime;

				var percentage = getPercentage(currentTime, duration);

				$('#time-current').html(currentTime);

				if(!disableSliderUpdate) {

					$('#slider').slider('value', percentage);
				}
			}
		});
	}
}

function loadFiles(objectId, objectName) {

	if(undefined == objectId) {

		objectId = 0;
	}

    currentObject = {
        'id' : objectId,
        'name' : name
    };

	currentFiles = {};

	Gui.setLoader('left');

	var device = $('#device-left').val();

	UPnPBackend.call(device, 'getChilds', 'ObjectID=' + objectId, function(res) {

		Gui.unsetLoader('left');

		if(res.NumberReturned == 0) {

			Gui.setError(i18n('This directory is empty'));
		}

		if(undefined != res.Result) {

            if(objectId == '0') {

                objectName = i18n('Root');
            }

            var back = false;

            for(var id in breadcrumps) {

                if(id == objectId) {

                    back = true;
                    continue;
                }

                if(back) {

                    delete(breadcrumps[id]);
                }
            }

            if(!back) {

                breadcrumps[objectId] = objectName;
            }

            var navigation = $('<div id="navbar"></div>');

			if(Object.size(breadcrumps) > 1) {

                var bc_navigation = $('<ul id="breadcrumps"></ul>');

                for(var id in breadcrumps) {

                    var name = breadcrumps[id];

                    var method = 'void(0);';

                    if(id != objectId) {

                        method = 'loadFiles(\'' + id + '\', \'' + name + '\');';
                    }

                    $(bc_navigation).append($('<li><a href="javascript:' + method + '"' + (id == objectId ? ' class="active"' : '') + '>&gt; ' + name + '</a></li>'));
                }

                $(navigation).append(bc_navigation);
			}
            
            var html = '<a href="javascript:Favorites.toggle();" id="favorite" title="' + i18n('Set/Unset as favorite') + '"></a><br class="clear" />';
            $(navigation).append($(html));

            var searchbar = $('<div id="search"><span>' + i18n('Search') + '</span>: <input type="text" id="searchbar" value="" /> <img id="search-enter" src="res/images/icons/keyboard-enter.png" style="display: none;" /></div>');

			$('#p_left').empty();
            $('#p_left').append(navigation);

            $('#p_left').append(searchbar);

            if(Favorites.isFavorite()) {

                $('#favorite').addClass('active');
            }

            $('#searchbar').keyup(function() {

                var value = $('#searchbar').val().toLowerCase();

                searchResult = [];

                for(var i in lastFileResult) {

                    var title = lastFileResult[i].title.toLowerCase();

                    if(title.search(value) != -1) {

                        searchResult.push(lastFileResult[i]);
                    }

                    if(searchResult.length == 1) {

                        $('#search-enter').show();
                    } else {

                        $('#search-enter').hide();
                    }
                }

                buildFileTable(searchResult);
            });
            
            lastFileResult = res.Result;

            buildFileTable(lastFileResult);

            $('#searchbar').focus();

            $('#searchbar').keyup(function(ev) {

                var id = searchResult[0].id;
                var name = searchResult[0].title;

                if(ev.keyCode == 13) {

                    var method = $('#item-' + md5(id)).attr('href');

                    if(method.substr(0, 11) == 'javascript:') {

                        eval(method.substr(11));
                    }
                    return; 
                }
            });
		}
	});
}

function buildFileTable(files) {
		
        if($('#filetable').length > 0) {

            $('#filetable').remove();
        }

        var table = $('<div id="filetable"></div>');
        $('#p_left').append(table);

        for(var i in files) {

            var data = files[i];

            currentFiles[data.id] = data;

            var html = '<div class="filerow">';

            html += '	<div class="mime">';
            html += '		<img src="res/images/icons/mime/' + data.class + '.png" alt="' + data.class + '" title="' + data.class + '" />';
            html += '	</div>';

            if(data.class.substr(0, 16) == 'object.container') {

                html += '		<a href="javascript:loadFiles(\'' + data.id + '\', \'' + data.title + '\');" id="item-' + md5(data.id) + '" class="filerow">' + data.title + '</a>';
            } else {

                html += '		<a href="javascript:showFileInfo(\'' + data.id + '\');" id="item-' + md5(data.id) + '" class="filerow">' + data.title + '</a>';
            }

            html += '   <div class="right">';
            if(data.class.split('.')[1] != 'container') {

                switch(data.class) {

                    case 'object.item.imageItem.photo':

                        html += '   <a href="resources.php?image=' + data.res + '&w=640" rel="lightbox[preview]" title="' + i18n('View') + '">';
                        html += '       <img src="res/images/icons/view.png" alt="' + i18n('View') + '" />';
                        html += '   </a>';
                        break;
                }

                if(in_array(data.mimeType, allowedMimes)) {

                    html += '   <a href="javascript:play(\'' + data.id + '\');" title="' + i18n('Play') + '">';
                    html += '       <img src="res/images/icons/play.png" alt="' + i18n('Play') + '" />';
                    html += '   </a>';
                } else {

                    html += '   <a href="javascript:void();" title="' + i18n('Not possible') + '" style="cursor: default;">';
                    html += '       <img src="res/images/icons/play-gray.png" alt="' + i18n('Not possible') + '" />';
                    html += '   </a>';
                }

            }
            html += '	</div>';
            html += '</div>';

            $('div#filetable').append($(html));
        }
}

function hideFileInfo() {

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

function showFileInfo(objectId) {

    var html = '<div id="fileinfo" style="display:none"><center><img src="res/images/icons/ajax-loader.gif" />';
    html += '<br />';
    html += '<input type="button" onclick="hideFileInfo();" value="' + i18n('Cancel') + '" />';
    html += '</center></div>';

    $(document.body).append($('<div id="carpet" style="display:none"></div>'));
    $(document.body).append($(html));

    $('#carpet').fadeIn();
    $('#fileinfo').fadeIn();

    UPnPBackend.call($('#device-left').val(), 'getFileInfoHtml', 'ObjectID=' + objectId, function(res) {

        $('#fileinfo').html(res);

        var html = '<div>';
        html += '<input type="button" onclick="play(\'' + objectId + '\')" value="' + i18n('Play') + '" />';
        html += '<input type="button" onclick="hideFileInfo();" value="' + i18n('Close') + '" />';
        html += '</div>';

        $('#fileinfo').append($(html));
    });
}

function play(objectId) {

	var playDevice = $('#device-right').val();

	if(playDevice == '') {

		Gui.setError(i18n('No Destination selected'));
        return;
	}

	var className = currentFiles[objectId].class;

	Gui.showLoadingLayer(i18n('Sending to device'));

    UPnPBackend.call($('#device-left').val(), 'getMetaData', 'ObjectID=' + objectId, function(res) {

        for(var title in res.Result) {

            var data = res.Result[title];
            break;
        }

        var mime = (undefined == data ? null : data.mimeType);

        if(mime != null && !in_array(mime, allowedMimes)) {

            Gui.setError(i18n('Selected device does not support files with type ' + mime));
            Gui.hideLoadingLayer();
        }

        var artist = (undefined == data ? null : data.artist);
        var album = (undefined == data ? null : data.album);
        var originalTrackNumber = (undefined == data ? null : data.originalTrackNumber);
        var genre = (undefined == data ? null : data.genre);

        var url = (undefined == data ? null : data.res);
        var xml = (undefined == data ? null : res.Result_XML.split('<').join('&lt;').split('>').join('&gt;').split('&').join('amp;'));

        UPnPBackend.call($('#device-right').val(), 'StartPlay', 'source=' + $('#device-left').val() + '&id=' + objectId, function(res) {

            Gui.hideLoadingLayer();
        });
    });
}
