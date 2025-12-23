const loadedTags = new Set();

function appendScript(id, src, innerText) {
  if (typeof document === 'undefined') return;
  if (loadedTags.has(id) || document.getElementById(id)) return;

  const script = document.createElement('script');
  script.id = id;

  if (src) {
    script.async = true;
    script.src = src;
  }

  if (innerText) {
    script.text = innerText;
  }

  document.head.appendChild(script);
  loadedTags.add(id);
}

function initGoogleAnalytics(measurementId) {
  if (!measurementId || typeof window === 'undefined') return;

  appendScript(
    'ga4-tag',
    `https://www.googletagmanager.com/gtag/js?id=${measurementId}`
  );

  appendScript(
    'ga4-inline',
    null,
    `window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);} 
gtag('js', new Date());
gtag('config', '${measurementId}');`
  );
}

function initMetaPixel(pixelId) {
  if (!pixelId || typeof window === 'undefined' || window.fbq) return;

  appendScript(
    'facebook-pixel',
    null,
    `!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '${pixelId}');
fbq('track', 'PageView');`
  );
}

function initTikTokPixel(pixelId) {
  if (!pixelId || typeof window === 'undefined' || window.ttq) return;

  appendScript(
    'tiktok-pixel',
    null,
    `!function (w, d, t) {
  w.TiktokAnalyticsObject = t;
  var ttq = (w[t] = w[t] || []);
  ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie'];
  ttq.setAndDefer = function (t, e) {
    t[e] = function () {
      t.push([e].concat(Array.prototype.slice.call(arguments, 0)));
    };
  };
  for (var i = 0; i < ttq.methods.length; i++) {
    ttq.setAndDefer(ttq, ttq.methods[i]);
  }
  ttq.instance = function (t) {
    var e = ttq._i[t] || [];
    return {
      load: function (n, r) {
        ttq._i[t] = [];
        ttq._i[t]._u = n;
        ttq._i[t]._c = r;
      },
    };
  };
  ttq.load = function (t, e) {
    var n = 'https://analytics.tiktok.com/i18n/pixel/events.js';
    ttq._i = ttq._i || {};
    ttq._i[t] = [];
    ttq._i[t]._u = n;
    ttq._t = ttq._t || {};
    ttq._t[t] = +new Date();
    ttq._o = ttq._o || {};
    ttq._o[t] = e || {};
    ttq._o[t].qid = ttq._o[t].qid || '1';
    ttq._o[t].event = 'page';
    ttq._o[t].timestamp = +new Date();
    var i = d.createElement('script');
    i.async = !0;
    i.src = n;
    var o = d.getElementsByTagName('script')[0];
    o.parentNode.insertBefore(i, o);
  };
  ttq.load('${pixelId}');
  ttq.page();
}(window, document, 'ttq');`
  );
}

export function initPixels() {
  if (typeof window === 'undefined' || window.__marketingPixelsLoaded) {
    return;
  }

  const gaId = import.meta.env.VITE_GA4_ID || import.meta.env.VITE_GA_ID;
  const metaId = import.meta.env.VITE_META_PIXEL_ID;
  const tiktokId = import.meta.env.VITE_TIKTOK_PIXEL_ID;

  if (!gaId && !metaId && !tiktokId) {
    console.warn('[pixels] Aucun identifiant de pixel configuré.');
    return;
  }

  initGoogleAnalytics(gaId);
  initMetaPixel(metaId);
  initTikTokPixel(tiktokId);

  window.__marketingPixelsLoaded = true;
}
