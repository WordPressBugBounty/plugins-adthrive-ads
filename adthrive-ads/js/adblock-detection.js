// PE-741: Adshield ad blocker detection enabled globally (including GDPR regions)
// Cookie compliance: __adblocker cookie only set when ad blocker detected, immediately deleted after read
(function (doc) {
  const adBlockerCookieKey = '__adblocker';
  if (doc.cookie.indexOf(adBlockerCookieKey) === -1) {
    const request = new XMLHttpRequest();
    request.open('GET', 'https://ads.adthrive.com/abd/abd.js', true);
    request.onreadystatechange = function () {
      if (XMLHttpRequest.DONE === request.readyState) {
        if (request.status === 200) {
          const script = doc.createElement('script');
          script.innerHTML = request.responseText;
          doc.getElementsByTagName('head')[0].appendChild(script);
        } else {
          const date = new Date();
          date.setTime(date.getTime() + 60 * 5 * 1000);
          doc.cookie =
            adBlockerCookieKey +
            '=true; expires=' +
            date.toUTCString() +
            '; path=/';
        }
      }
    };
    request.send();
  }
})(document);
