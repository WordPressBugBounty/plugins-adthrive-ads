(function(doc) {
	var adBlockerCookieKey = '__adblocker';
	if(doc.cookie.indexOf(adBlockerCookieKey) === -1) {
		doc.cookie = adBlockerCookieKey + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
		var request = new XMLHttpRequest();
		request.open('GET', 'https://ads.adthrive.com/abd/abd.js', true);
		request.onreadystatechange = function() {
			if(XMLHttpRequest.DONE === request.readyState) {
				if(request.status === 200) {
					var script = doc.createElement("script");
					script.innerHTML = request.responseText;
					doc.getElementsByTagName("head")[0].appendChild(script);
				} else {
					var date = new Date();
					date.setTime(date.getTime() + 60 * 5 * 1000);
					doc.cookie = adBlockerCookieKey + "=true; expires=" + date.toUTCString() + "; path=/";
				}
			}
		};
		request.send();
	}
})(document);