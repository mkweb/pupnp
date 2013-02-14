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
var dropdownValues = {};

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

    if($('.deviceSelection').length > 0) {

        upnp = new UPnP();

        upnp.gui.prepare();
    }

    if($('.flash').length > 0) {

        window.setTimeout(function() {

            $('.flash').slideUp();
        }, 3000);
    }

    updateClock();
    window.setInterval('updateClock()', 1000);
}

function updateClock() {

    $('#clock').text((new Date()).toLocaleString());
}

function updateDropdowns() {

    var select = $('select');

    $(select).each(function() {

        if($(this).css('display') == 'none') return;

        var container = $('<div class="select"></div>');

        var rand = Math.floor(Math.random() * 1000);
        var selectBox = $('<div class="selectBox" id="' + rand + '"></div>');

        var dropdown = $('<ul class="dropDown" id="dropdown-' + rand + '" style="display: none;"></ul>');

        var index = ($(this).selectedIndex != undefined ? $(this).selectedIndex : 0);

        var selected = $($(this).children('option')[index]).html();

        var options = $(this).find('option');

        selectBox.html(selected);

        $(options).each(function() {

            var value = $(this).attr('value');
            var name = $(this).html();

            var html = '';
            if($(this).data('icon') != undefined) {

                html += '<img src="' + $(this).data('icon') + '" />';
            }

            html += '<span>' + name + '</span>';

            $(dropdown).append('<li data-value="' + value + '">' + html + '</li>');
        });

        $(container).append(selectBox);
        $(container).append(dropdown);

        dropdownValues[rand] = $(this).val();
        $(this).addClass('original-select');
        $(this).addClass('original-' + rand);
        $(this).hide().after(container);

        window.setInterval(function() {

            $('.original-select').each(function() {

                var classes = $(this).attr('class').split(' ');

                var id = null;
                for(var i in classes) {

                    if(classes[i] != 'original-select' && classes[i].substr(0, 9) == 'original-') {

                        id = classes[i].substr(9);
                    }
                }

                if(id != null) {

                    var current = $('.original-' + id).val();
                    var saved = dropdownValues[id];

                    if(saved != current) {

                        var ul = $('#dropdown-' + id);
                        var items = $(ul).children('li');

                        for(var i = 0; i < items.length; i++) {

                            var item = items[i];
                            if($(item).data('value') == current) {

                                $('#' + id).html($(item).html());
                                dropdownValues[id] = current;
                                break;
                            }
                        }
                    }
                }
            });
        }, 30);

        $('#' + rand).click(function() {

            var dropdown = $('#dropdown-' + $(this).attr('id'));

            if($(dropdown).hasClass('expanded')) {

                $(dropdown).slideUp();
                $(dropdown).removeClass('expanded');
            } else {

                $(dropdown).slideDown();
                $(dropdown).addClass('expanded');
            }
        });

        $('.dropdown').children('li').click(function() {

            var val = $(this).data('value');
            var id = $(this).parent().attr('id').split('dropdown-').join('');
            var select = $('.original-' + id);

            $(this).parent().slideUp().removeClass('expanded');
            $(this).parent().prev().html($(this).html());

            var options = $(select).children('option');

            for(var i = 0; i < options.length; i++) {

                var option= options[i];
                var attr = $(option).attr('value');

                if(undefined != attr && attr == val) {

                    $(option).attr('selected', 'selected');
                    break;
                }
            }

            $(select).val(val);
            $(select).trigger('change');
        });
    });
}
