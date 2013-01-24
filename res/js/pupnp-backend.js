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
* Ajax backend handler
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
var UPnPBackend = {

    /**
    * Url of server file
    * @var string
    */
	url : 'backend.php',

    /**
    * Performing asynchronious Ajax request
    * If successfull callback method will be triggered
    *
    * @var string   device    Device UID
    * @var string   method    Backend method
    * @var string   data      HTTP querystring
    * @var function c         Callback function
    */
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
