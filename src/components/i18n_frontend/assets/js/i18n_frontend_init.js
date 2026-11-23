(function () {
  var c = window.dmI18n, d = document.documentElement, key = "dm_lang", lang = null;
  if (!c) return;

  function store(value) {
    try { localStorage.setItem(key, value); } catch (e) {}
  }

  var WS = /\s+/g;
  var SPLIT = /<[^>]*>|\r?\n/;
  var decoder = null;

  function normalize(text) {
    return String(text).replace(WS, " ").replace(/^ | $/g, "");
  }

  function decode(text) {
    if (text.indexOf("&") === -1) return text;
    decoder = decoder || document.createElement("textarea");
    decoder.innerHTML = text;
    return decoder.value;
  }

  function add(map, en, tr) {
    var a = normalize(decode(en)), b = normalize(decode(tr));
    if (!a || !b || a === b || a.indexOf("{{") !== -1 || map.has(a)) return;
    map.set(a, b);
  }

  function pair(map, en, tr) {
    if (en === tr) return;
    if (!SPLIT.test(en)) { add(map, en, tr); return; }
    var x = en.split(SPLIT), y = tr.split(SPLIT);
    if (x.length !== y.length) return;
    for (var i = 0; i < x.length; i++) add(map, x[i], y[i]);
  }

  function walk(map, en, tr) {
    if (typeof en === "string") {
      if (typeof tr === "string") pair(map, en, tr);
      return;
    }
    if (!en || !tr || typeof en !== "object" || typeof tr !== "object") return;
    if (Array.isArray(en)) {
      if (!Array.isArray(tr)) return;
      var byId = null;
      if (en.length && en[0] && typeof en[0] === "object" && "_id" in en[0]) {
        byId = {};
        tr.forEach(function (item) { if (item && item._id != null) byId[item._id] = item; });
      }
      for (var i = 0; i < en.length; i++) walk(map, en[i], byId ? (en[i] ? byId[en[i]._id] : null) : tr[i]);
      return;
    }
    for (var k in en) {
      if (Object.prototype.hasOwnProperty.call(en, k) && Object.prototype.hasOwnProperty.call(tr, k)) walk(map, en[k], tr[k]);
    }
  }

  c.store = store;
  c.fetch = function (target) {
    var root = (c.root || "") + c.data;
    // "no-cache" = the browser keeps its copy but asks the server whether the file changed
    // (a tiny "304 Not Modified" when it didn't), so no version has to be written into the pages.
    function get(url) {
      return fetch(url, { credentials: "same-origin", cache: "no-cache" })
        .then(function (r) { return r.ok ? r.json() : null; })
        .catch(function () { return null; });
    }
    var jobs = Object.keys(c.files || {}).filter(function (file) {
      return (c.files[file] || []).indexOf(target) !== -1;
    }).map(function (file) {
      return Promise.all([get(root + c.default + "/" + file), get(root + target + "/" + file)]);
    });
    return Promise.all(jobs).then(function (results) {
      var map = new Map();
      results.forEach(function (r) { if (r[0] && r[1]) walk(map, r[0], r[1]); });
      return map;
    });
  };

  try {
    var param = new URLSearchParams(location.search).get("lang");
    if (param && c.langs.indexOf(param) !== -1) { lang = param; store(param); }
  } catch (e) {}

  if (c.page_lang !== c.default) {
    if (!lang) store(c.page_lang);
    return;
  }

  if (!lang) {
    try { lang = localStorage.getItem(key); } catch (e) {}
  }
  if (!lang || c.frontend.indexOf(lang) === -1 || !window.fetch || !window.Promise || !window.Map) return;

  c.lang = lang;
  d.classList.add("i18n-loading");
  c.pending = c.fetch(lang);
  setTimeout(function () { d.classList.remove("i18n-loading"); }, c.timeout || 6000);
})();
