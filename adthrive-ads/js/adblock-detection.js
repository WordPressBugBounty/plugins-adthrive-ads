(function (doc) {
  const timeZoneToRegulationMap = {
    'Europe/Brussels': 'gdpr', // Belgium
    'Europe/Sofia': 'gdpr', // Bulgaria
    'Europe/Prague': 'gdpr', // Czech Republic
    'Europe/Copenhagen': 'gdpr', // Denmark
    'Europe/Berlin': 'gdpr', // Germany
    'Europe/Tallinn': 'gdpr', // Estonia
    'Europe/Dublin': 'gdpr', // Ireland
    'Europe/Athens': 'gdpr', // Greece
    'Europe/Madrid': 'gdpr', // Spain
    'Africa/Ceuta': 'gdpr', // Spain (excl. Canary Islands)
    'Europe/Paris': 'gdpr', // France
    'Europe/Zagreb': 'gdpr', // Croatia
    'Europe/Rome': 'gdpr', // Italy
    'Asia/Nicosia': 'gdpr', // Cyprus
    'Europe/Nicosia': 'gdpr', // Cyprus (Europe TZ)
    'Europe/Riga': 'gdpr', // Latvia
    'Europe/Vilnius': 'gdpr', // Lithuania
    'Europe/Luxembourg': 'gdpr', // Luxembourg
    'Europe/Budapest': 'gdpr', // Hungary
    'Europe/Malta': 'gdpr', // Malta
    'Europe/Amsterdam': 'gdpr', // Netherlands
    'Europe/Vienna': 'gdpr', // Austria
    'Europe/Warsaw': 'gdpr', // Poland
    'Europe/Lisbon': 'gdpr', // Portugal (mainland)
    'Atlantic/Madeira': 'gdpr', // Portugal (Madeira)
    'Europe/Bucharest': 'gdpr', // Romania
    'Europe/Ljubljana': 'gdpr', // Slovenia
    'Europe/Bratislava': 'gdpr', // Slovakia
    'Europe/Helsinki': 'gdpr', // Finland
    'Europe/Stockholm': 'gdpr', // Sweden
    'Europe/London': 'gdpr', // United Kingdom / Great Britain
    'Europe/Vaduz': 'gdpr', // Liechtenstein
    'Atlantic/Reykjavik': 'gdpr', // Iceland
    'Europe/Oslo': 'gdpr', // Norway
    'Europe/Istanbul': 'gdpr', // Turkey
    'Europe/Zurich': 'gdpr', // Switzerland
  };

  const inferRegulation = () => {
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return timeZoneToRegulationMap[timeZone] || null;
  };

  const regulation = inferRegulation();
  if (regulation === null || regulation !== 'gdpr') {
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
  }
})(document);
