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

/**
* This file is a small collection of helper method
*/
function object_key_exists(search, obj) {

    for(var key in obj) {

        if(key == search) {

            return true;
        }
    }

    return false;
}
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

function getUnixTimestamp(date) {

	return Date.parse(date) / 1000;
}

function getTime(UNIX_timestamp){

	var a = new Date(UNIX_timestamp * 1000);

	var hour = a.getUTCHours();
	var min = a.getUTCMinutes();
	var sec = a.getUTCSeconds();
	var time = LPad(hour.toString(), 2, '0') + ':' + LPad(min.toString(), 2, '0') + ':' + LPad(sec.toString(), 2, '0');

	return time;
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

function is_int(mixed_var) {

	return mixed_var === +mixed_var && isFinite(mixed_var) && !(mixed_var % 1);
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
