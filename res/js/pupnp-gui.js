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
var Gui = {

    loader : {
        left : null,
        right : null
    },

    setError : function(message) {

        this.unsetLoader('left');
        this.unsetLoader('right');

        $('#error').html(message);
        $('#error').slideDown();

        window.setTimeout(function() {

            $('#error').slideUp();
            $('#error').html('');
        }, 3000);
    },

    setLoader : function(column) {

        if(null != this.loader[column]) {

            return false;
        }

        var col = $('#p_' + column);

        if($(col).css('display') == 'none') {

            $(col).slideDown();
        }

        var id = 'loader_' + Math.floor(Math.random() * 1000);
        
        var oldhtml = $(col).html();
        $(col).html($('<div class="loading" id="' + id + '"><img src="res/images/icons/ajax-loader.gif" /></div>' + oldhtml));

        this.loader[column] = id;
    },

    unsetLoader : function(column) {

        if(null != this.loader[column]) {

            var id = this.loader[column];

            $('#' + id).remove();
            this.loader[column] = null;
        }
    },

    initTooltips : function() {

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
    },

    showLoadingLayer : function(msg) {

        $(document.body).append('<div id="carpet"></div><div id="loading-layer">' + msg + '<br /><img src="res/images/icons/ajax-loader.gif" /><br /><input type="button" onclick="hideLoadingLayer();" value="' + i18n('Cancel') + '" /></div>');
    },

    hideLoadingLayer : function() {

        $('#carpet').remove();
        $('#loading-layer').remove();
    }
}
