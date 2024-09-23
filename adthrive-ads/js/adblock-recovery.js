(function(doc) {
	var recovery = (function() {
		var adBlockKey = '__adblocker';
		var adBlockDetectionCookie;
		var pollForDetection;

		function init() {
			adBlockDetectionCookie = checkCookie();

			if(adBlockDetectionCookie === 'true') {
				addRecoveryScript();
			} else {
				checkForAdBlockDetection();
			}
		}

		function addRecoveryScript() {
			var script = doc.createElement('script');
			script.src = "https://cafemedia-com.videoplayerhub.com/galleryplayer.js";
			doc.head.appendChild(script);
		}

		function checkCookie() {
			var adBlockCookie = doc.cookie.match('(^|[^;]+)\\s*' + adBlockKey + '\\s*=\\s*([^;]+)');
			return adBlockCookie && adBlockCookie.pop();
		}

		function checkForAdBlockDetection() {
			var counter = 0;
			pollForDetection = setInterval(function() {
				if(counter === 100 || adBlockDetectionCookie === 'false') clearPoll();
				if(adBlockDetectionCookie === 'true') {
					addRecoveryScript();
					clearPoll();
				}
				adBlockDetectionCookie = checkCookie();
				counter++;
			}, 50);
		}

		function clearPoll() {
			clearInterval(pollForDetection);
		}

		return {
			init: init
		}
	})();


	recovery.init();
})(document);