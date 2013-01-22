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

function in_array(search, arr) {

    for(var i in arr) {

        if(arr[i] == search) {

            return true;
        }
    }

    return false;
}

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

var UPnPBackend = {

	url : 'backend.php',

	call : function(device, method, data, c) {

		if(undefined != c) {

			var callback = c;
		}
		
		var url = this.url + '?' + (device != null ? 'device=' + device + '&' : '') + 'action=' + method + '&' + data;

		$.ajax({

			url : url,
			success : function(res) {

                if(res.substr(res.length - 2, 2) == '==') {

                    res = base64_decode(res);

                    if(undefined != callback) {

                        callback(res);
                    } else {

                        console.log(res);
                    }

                    return;
                }

				if(res != '') {

					response = eval('(' + res + ')');
				}

				if(null != response) {

					if(undefined != response.error) {

						setError(response.error);
					} else {

						if(undefined != callback) {

							callback(response);
						} else {

							console.log(response);
						}
					}
				} else {

					console.log("Error in response");
						
					if(undefined != callback) {
	
						callback(null);
					}
				}
			}
		});
	}
};

function i18n(msg) {

	return msg;
}

function setError(message) {

	unsetLoader('left');
	unsetLoader('right');

	$('#error').html(message);
	$('#error').slideDown();

	window.setTimeout(function() {

		$('#error').slideUp();
		$('#error').html('');
	}, 3000);
}

function setLoader(column) {

	if(null != loader[column]) {

		return false;
	}

	var col = $('#p_' + column);

	if($(col).css('display') == 'none') {

		$(col).slideDown();
	}

	var id = 'loader_' + Math.floor(Math.random() * 1000);
	
	var oldhtml = $(col).html();
	$(col).html($('<div class="loading" id="' + id + '"><img src="res/images/icons/ajax-loader.gif" /></div>' + oldhtml));

	// $('#' + id).css('height', $('#p_' + column).css('height'));

	loader[column] = id;
}

function unsetLoader(column) {

	if(null != loader[column]) {

		var id = loader[column];

		$('#' + id).remove();
		loader[column] = null;
	}
}

var loader = {
	left : null,
	right : null
};

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

            updateFavorites();

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

            var icon = 'backend.php?image=' + res.icons[0].url + '&sq=30';
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

                    initTooltips();
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

function initTooltips() {

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
}

function getUnixTimestamp(date) {

	return Date.parse(date) / 1000;
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

function LPad(ContentToSize,PadLength,PadChar)
{
	var PaddedString=ContentToSize.toString();

	for(var i = ContentToSize.length + 1 ; i <= PadLength; i++)
	{
		PaddedString=PadChar+PaddedString;
	}

	return PaddedString;
}

function getTime(UNIX_timestamp){

	var a = new Date(UNIX_timestamp * 1000);

	var hour = a.getUTCHours();
	var min = a.getUTCMinutes();
	var sec = a.getUTCSeconds();
	var time = LPad(hour, 2, '0') + ':' + LPad(min, 2, '0') + ':' + LPad(sec, 2, '0');

	return time;
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

                        $('#slider-tooltip').html(getTimeByPercentage(all, ui.value));
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

function disableLink(link) {

	$(link).bind('click', function(e) {

		e.preventDefault();
	});

	$(link).addClass('disabled');
}

function enableLink(link) {

	$(link).removeClass('disabled');
	$(link).unbind('click');
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

function is_int(mixed_var) {

	return mixed_var === +mixed_var && isFinite(mixed_var) && !(mixed_var % 1);
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

	setLoader('left');

	var device = $('#device-left').val();

	UPnPBackend.call(device, 'getChilds', 'ObjectID=' + objectId, function(res) {

		unsetLoader('left');

		if(res.NumberReturned == 0) {

			setError(i18n('This directory is empty'));
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
            
            var html = '<a href="javascript:toggleFav();" id="favorite" title="' + i18n('Set/Unset as favorite') + '"></a><br class="clear" />';
            $(navigation).append($(html));

            var searchbar = $('<div id="search"><span>' + i18n('Search') + '</span>: <input type="text" id="searchbar" value="" /> <img id="search-enter" src="res/images/icons/keyboard-enter.png" style="display: none;" /></div>');

			$('#p_left').empty();
            $('#p_left').append(navigation);

            $('#p_left').append(searchbar);

            if(isFavorite()) {

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

                        html += '   <a href="backend.php?image=' + data.res + '&w=640" rel="lightbox[preview]" title="' + i18n('View') + '">';
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

function showLoadingLayer(msg) {

	$(document.body).append('<div id="carpet"></div><div id="loading-layer">' + msg + '<br /><img src="res/images/icons/ajax-loader.gif" /><br /><input type="button" onclick="hideLoadingLayer();" value="' + i18n('Cancel') + '" /></div>');
}

function hideLoadingLayer() {

	$('#carpet').remove();
	$('#loading-layer').remove();
}

function play(objectId) {

	var playDevice = $('#device-right').val();

	if(playDevice == '') {

		setError(i18n('No Destination selected'));
        return;
	}

	var className = currentFiles[objectId].class;

	showLoadingLayer(i18n('Sending to device'));

    UPnPBackend.call($('#device-left').val(), 'getMetaData', 'ObjectID=' + objectId, function(res) {

        for(var title in res.Result) {

            var data = res.Result[title];
            break;
        }

        var mime = (undefined == data ? null : data.mimeType);

        if(mime != null && !in_array(mime, allowedMimes)) {

            setError(i18n('Selected device does not support files with type ' + mime));
            hideLoadingLayer();
        }

        var artist = (undefined == data ? null : data.artist);
        var album = (undefined == data ? null : data.album);
        var originalTrackNumber = (undefined == data ? null : data.originalTrackNumber);
        var genre = (undefined == data ? null : data.genre);

        var url = (undefined == data ? null : data.res);
        var xml = (undefined == data ? null : res.Result_XML.split('<').join('&lt;').split('>').join('&gt;').split('&').join('amp;'));

        UPnPBackend.call($('#device-right').val(), 'StartPlay', 'source=' + $('#device-left').val() + '&id=' + objectId, function(res) {

            hideLoadingLayer();
        });
    });
}

function removeLightbox() {

	if($('#carpet').length > 0) {

		$('#carpet').fadeOut();
	}

	if($('#lightbox').length > 0) {

		$('#lightbox').fadeIn();
	}
}

function createLightbox(url, name) {

	var html = '<div id="carpet"></div>';

	html += '<div id="lightbox">';
	html += '	<div class="head">';
	html += '		<a id="close" href="javascript:removeLightbox();" title="' + i18n('Close') + '"></a>';
	html += '	</div>';
	html += '	<div id="image">';
	html += '		<img style="max-width: 640px; max-height: 480px;" src="backend.php?image=' + url + '" />';
	html += '	</div>';
	html += '	<div class="foot">';
	html += '		<div id="name">' + name + '</div>';
	html += '	</div>';
	html += '</div>';

	$('#lightbox img').ready(function() {

		window.setTimeout(function() {

			var width = $('#lightbox img').width();
			var height = $('#lightbox img').height();

			$('#lightbox').css('height', (height + 72) + 'px');
			$('#lightbox').css('width', (width + 20) + 'px');
			
			$('#lightbox').css('margin-left', -($('#lightbox').width() / 2) + 'px');
			$('#lightbox').css('margin-top', -($('#lightbox').height() / 2) + 'px');

		}, 100);
	});

	$(document.body).append($(html));
}

function toggleFav() {

    var elem = $('a#favorite');
    
    var deviceId = $('#device-left').val();
    var uid = deviceId + '---' + currentObject.id;

    if($(elem).hasClass('active')) {

        removeFavorite(uid);
        $(elem).removeClass('active');
    } else {

        addFavorite();
        $(elem).addClass('active');
    }
}

function addFavorite() {

    var deviceId = $('#device-left').val();
    var deviceName = $('#device-left').find('option[value=' + deviceId + ']').text();
    var uid = deviceId + '---' + currentObject.id;

    var path = '';
    for(var i in breadcrumps) {

        path += '/' + breadcrumps[i];
    }

    var data = 'deviceId=' + deviceId + '&deviceName=' + deviceName + '&objectId=' + currentObject.id + '&path=' + path + '&breadcrumps=' + json_encode(breadcrumps).split('&').join('%26');

    UPnPBackend.call(null, 'addFavorite', data, function(res) {

        updateFavorites();
    });
}

function removeFavorite(uid) {

    UPnPBackend.call(null, 'removeFavorite', 'uid=' + uid, function(res) {

        updateFavorites();
    });
}

function updateFavorites() {

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

                showLoadingLayer(i18n('Loading favorite'));

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

                hideLoadingLayer();
            });
        }
    });
}

function isFavorite() {

    var deviceId = $('#device-left').val();
    var check_uid = deviceId + '---' + currentObject.id;

    for(var uid in favorites) {

        if(uid == check_uid) {

            return true;
        }
    }

    return false;
}

function md5 (str) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // + namespaced by: Michael White (http://getsprink.com)
  // +    tweaked by: Jack
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // -    depends on: utf8_encode
  // *     example 1: md5('Kevin van Zonneveld');
  // *     returns 1: '6e658d4bfcb59cc13f96c14450ac40b9'
  var xl;

  var rotateLeft = function (lValue, iShiftBits) {
    return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
  };

  var addUnsigned = function (lX, lY) {
    var lX4, lY4, lX8, lY8, lResult;
    lX8 = (lX & 0x80000000);
    lY8 = (lY & 0x80000000);
    lX4 = (lX & 0x40000000);
    lY4 = (lY & 0x40000000);
    lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
    if (lX4 & lY4) {
      return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
    }
    if (lX4 | lY4) {
      if (lResult & 0x40000000) {
        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
      } else {
        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
      }
    } else {
      return (lResult ^ lX8 ^ lY8);
    }
  };

  var _F = function (x, y, z) {
    return (x & y) | ((~x) & z);
  };
  var _G = function (x, y, z) {
    return (x & z) | (y & (~z));
  };
  var _H = function (x, y, z) {
    return (x ^ y ^ z);
  };
  var _I = function (x, y, z) {
    return (y ^ (x | (~z)));
  };

  var _FF = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _GG = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _HH = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _II = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var convertToWordArray = function (str) {
    var lWordCount;
    var lMessageLength = str.length;
    var lNumberOfWords_temp1 = lMessageLength + 8;
    var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
    var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
    var lWordArray = new Array(lNumberOfWords - 1);
    var lBytePosition = 0;
    var lByteCount = 0;
    while (lByteCount < lMessageLength) {
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
      lByteCount++;
    }
    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
    lBytePosition = (lByteCount % 4) * 8;
    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
    lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
    lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
    return lWordArray;
  };

  var wordToHex = function (lValue) {
    var wordToHexValue = "",
      wordToHexValue_temp = "",
      lByte, lCount;
    for (lCount = 0; lCount <= 3; lCount++) {
      lByte = (lValue >>> (lCount * 8)) & 255;
      wordToHexValue_temp = "0" + lByte.toString(16);
      wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
    }
    return wordToHexValue;
  };

  var x = [],
    k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
    S12 = 12,
    S13 = 17,
    S14 = 22,
    S21 = 5,
    S22 = 9,
    S23 = 14,
    S24 = 20,
    S31 = 4,
    S32 = 11,
    S33 = 16,
    S34 = 23,
    S41 = 6,
    S42 = 10,
    S43 = 15,
    S44 = 21;

  str = this.utf8_encode(str);
  x = convertToWordArray(str);
  a = 0x67452301;
  b = 0xEFCDAB89;
  c = 0x98BADCFE;
  d = 0x10325476;

  xl = x.length;
  for (k = 0; k < xl; k += 16) {
    AA = a;
    BB = b;
    CC = c;
    DD = d;
    a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
    d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
    c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
    b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
    a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
    d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
    c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
    b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
    a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
    d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
    c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
    b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
    a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
    d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
    c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
    b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
    a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
    d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
    c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
    b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
    a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
    d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
    c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
    b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
    a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
    d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
    c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
    b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
    a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
    d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
    c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
    b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
    a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
    d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
    c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
    b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
    a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
    d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
    c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
    b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
    a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
    d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
    c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
    b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
    a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
    d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
    c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
    b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
    a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
    d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
    c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
    b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
    a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
    d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
    c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
    b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
    a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
    d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
    c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
    b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
    a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
    d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
    c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
    b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
    a = addUnsigned(a, AA);
    b = addUnsigned(b, BB);
    c = addUnsigned(c, CC);
    d = addUnsigned(d, DD);
  }

  var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

  return temp.toLowerCase();
}

function utf8_encode (argString) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: sowberry
  // +    tweaked by: Jack
  // +   bugfixed by: Onno Marsman
  // +   improved by: Yves Sucaet
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Ulrich
  // +   bugfixed by: Rafal Kukawski
  // +   improved by: kirilloid
  // *     example 1: utf8_encode('Kevin van Zonneveld');
  // *     returns 1: 'Kevin van Zonneveld'

  if (argString === null || typeof argString === "undefined") {
    return "";
  }

  var string = (argString + ''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
  var utftext = '',
    start, end, stringl = 0;

  start = end = 0;
  stringl = string.length;
  for (var n = 0; n < stringl; n++) {
    var c1 = string.charCodeAt(n);
    var enc = null;

    if (c1 < 128) {
      end++;
    } else if (c1 > 127 && c1 < 2048) {
      enc = String.fromCharCode((c1 >> 6) | 192, (c1 & 63) | 128);
    } else {
      enc = String.fromCharCode((c1 >> 12) | 224, ((c1 >> 6) & 63) | 128, (c1 & 63) | 128);
    }
    if (enc !== null) {
      if (end > start) {
        utftext += string.slice(start, end);
      }
      utftext += enc;
      start = end = n + 1;
    }
  }

  if (end > start) {
    utftext += string.slice(start, stringl);
  }

  return utftext;
}

function utf8_decode (str_data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // +      input by: Aman Gupta
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Norman "zEh" Fuchs
  // +   bugfixed by: hitwork
  // +   bugfixed by: Onno Marsman
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // *     example 1: utf8_decode('Kevin van Zonneveld');
  // *     returns 1: 'Kevin van Zonneveld'
  var tmp_arr = [],
    i = 0,
    ac = 0,
    c1 = 0,
    c2 = 0,
    c3 = 0;

  str_data += '';

  while (i < str_data.length) {
    c1 = str_data.charCodeAt(i);
    if (c1 < 128) {
      tmp_arr[ac++] = String.fromCharCode(c1);
      i++;
    } else if (c1 > 191 && c1 < 224) {
      c2 = str_data.charCodeAt(i + 1);
      tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
      i += 2;
    } else {
      c2 = str_data.charCodeAt(i + 1);
      c3 = str_data.charCodeAt(i + 2);
      tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
      i += 3;
    }
  }

  return tmp_arr.join('');
}

function base64_decode (data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Tyler Akins (http://rumkin.com)
  // +   improved by: Thunder.m
  // +      input by: Aman Gupta
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Pellentesque Malesuada
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
  // *     returns 1: 'Kevin van Zonneveld'
  // mozilla has this native
  // - but breaks in 2.0.0.12!
  //if (typeof this.window['atob'] == 'function') {
  //    return atob(data);
  //}
  var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    dec = "",
    tmp_arr = [];

  if (!data) {
    return data;
  }

  data += '';

  do { // unpack four hexets into three octets using index points in b64
    h1 = b64.indexOf(data.charAt(i++));
    h2 = b64.indexOf(data.charAt(i++));
    h3 = b64.indexOf(data.charAt(i++));
    h4 = b64.indexOf(data.charAt(i++));

    bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

    o1 = bits >> 16 & 0xff;
    o2 = bits >> 8 & 0xff;
    o3 = bits & 0xff;

    if (h3 == 64) {
      tmp_arr[ac++] = String.fromCharCode(o1);
    } else if (h4 == 64) {
      tmp_arr[ac++] = String.fromCharCode(o1, o2);
    } else {
      tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
    }
  } while (i < data.length);

  dec = tmp_arr.join('');

  return dec;
}

function json_encode (mixed_val) {
  // http://kevin.vanzonneveld.net
  // +      original by: Public Domain (http://www.json.org/json2.js)
  // + reimplemented by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      improved by: Michael White
  // +      input by: felix
  // +      bugfixed by: Brett Zamir (http://brett-zamir.me)
  // *        example 1: json_encode(['e', {pluribus: 'unum'}]);
  // *        returns 1: '[\n    "e",\n    {\n    "pluribus": "unum"\n}\n]'
/*
    http://www.JSON.org/json2.js
    2008-11-19
    Public Domain.
    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
    See http://www.JSON.org/js.html
  */
  var retVal, json = this.window.JSON;
  try {
    if (typeof json === 'object' && typeof json.stringify === 'function') {
      retVal = json.stringify(mixed_val); // Errors will not be caught here if our own equivalent to resource
      //  (an instance of PHPJS_Resource) is used
      if (retVal === undefined) {
        throw new SyntaxError('json_encode');
      }
      return retVal;
    }

    var value = mixed_val;

    var quote = function (string) {
      var escapable = /[\\\"\u0000-\u001f\u007f-\u009f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
      var meta = { // table of character substitutions
        '\b': '\\b',
        '\t': '\\t',
        '\n': '\\n',
        '\f': '\\f',
        '\r': '\\r',
        '"': '\\"',
        '\\': '\\\\'
      };

      escapable.lastIndex = 0;
      return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
        var c = meta[a];
        return typeof c === 'string' ? c : '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
      }) + '"' : '"' + string + '"';
    };

    var str = function (key, holder) {
      var gap = '';
      var indent = '    ';
      var i = 0; // The loop counter.
      var k = ''; // The member key.
      var v = ''; // The member value.
      var length = 0;
      var mind = gap;
      var partial = [];
      var value = holder[key];

      // If the value has a toJSON method, call it to obtain a replacement value.
      if (value && typeof value === 'object' && typeof value.toJSON === 'function') {
        value = value.toJSON(key);
      }

      // What happens next depends on the value's type.
      switch (typeof value) {
      case 'string':
        return quote(value);

      case 'number':
        // JSON numbers must be finite. Encode non-finite numbers as null.
        return isFinite(value) ? String(value) : 'null';

      case 'boolean':
      case 'null':
        // If the value is a boolean or null, convert it to a string. Note:
        // typeof null does not produce 'null'. The case is included here in
        // the remote chance that this gets fixed someday.
        return String(value);

      case 'object':
        // If the type is 'object', we might be dealing with an object or an array or
        // null.
        // Due to a specification blunder in ECMAScript, typeof null is 'object',
        // so watch out for that case.
        if (!value) {
          return 'null';
        }
        if ((this.PHPJS_Resource && value instanceof this.PHPJS_Resource) || (window.PHPJS_Resource && value instanceof window.PHPJS_Resource)) {
          throw new SyntaxError('json_encode');
        }

        // Make an array to hold the partial results of stringifying this object value.
        gap += indent;
        partial = [];

        // Is the value an array?
        if (Object.prototype.toString.apply(value) === '[object Array]') {
          // The value is an array. Stringify every element. Use null as a placeholder
          // for non-JSON values.
          length = value.length;
          for (i = 0; i < length; i += 1) {
            partial[i] = str(i, value) || 'null';
          }

          // Join all of the elements together, separated with commas, and wrap them in
          // brackets.
          v = partial.length === 0 ? '[]' : gap ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']' : '[' + partial.join(',') + ']';
          gap = mind;
          return v;
        }

        // Iterate through all of the keys in the object.
        for (k in value) {
          if (Object.hasOwnProperty.call(value, k)) {
            v = str(k, value);
            if (v) {
              partial.push(quote(k) + (gap ? ': ' : ':') + v);
            }
          }
        }

        // Join all of the member texts together, separated with commas,
        // and wrap them in braces.
        v = partial.length === 0 ? '{}' : gap ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}' : '{' + partial.join(',') + '}';
        gap = mind;
        return v;
      case 'undefined':
        // Fall-through
      case 'function':
        // Fall-through
      default:
        throw new SyntaxError('json_encode');
      }
    };

    // Make a fake root object containing our value under the key of ''.
    // Return the result of stringifying the value.
    return str('', {
      '': value
    });

  } catch (err) { // Todo: ensure error handling above throws a SyntaxError in all cases where it could
    // (i.e., when the JSON global is not available and there is an error)
    if (!(err instanceof SyntaxError)) {
      throw new Error('Unexpected error type in json_encode()');
    }
    this.php_js = this.php_js || {};
    this.php_js.last_error_json = 4; // usable by json_last_error()
    return null;
  }
}
