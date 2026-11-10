/* Kizora theme behaviour (no jQuery). Mirrors the prototype's React components:
   header menus, cart drawer, AJAX add to cart, product gallery, shop and ingredient filters. */
(function () {
  'use strict';
  var KZ = window.KZ || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };

  /* ---------------------------------------------------------- overlays */
  function overlay(root, panel, openCls, closedCls, open) {
    if (!root) return;
    root.classList.toggle('pointer-events-none', !open);
    root.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) root.removeAttribute('inert'); else root.setAttribute('inert', '');
    var shade = root.firstElementChild;
    if (shade) { shade.classList.toggle('opacity-100', open); shade.classList.toggle('opacity-0', !open); }
    if (panel) { panel.classList.toggle(openCls, open); panel.classList.toggle(closedCls, !open); }
  }
  var cart = $('[data-kz-cart]');
  var menu = $('[data-kz-menu]');
  function setCart(open) { overlay(cart, $('[data-kz-cart-panel]'), 'translate-x-0', 'translate-x-full', open); }
  function setMenu(open) {
    overlay(menu, $('[data-kz-menu-panel]'), 'translate-x-0', '-translate-x-full', open);
    var b = $('[data-kz-menu-open]'); if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-kz-menu-open]')) { setMenu(true); return; }
    if (t.closest('[data-kz-menu-close]')) { setMenu(false); return; }
    if (t.closest('[data-kz-close-cart]')) { setCart(false); return; }
    var oc = t.closest('[data-kz-open-cart]');
    if (oc && cart) { e.preventDefault(); setCart(true); return; }
    var st = t.closest('[data-kz-search-toggle]');
    if (st) {
      var box = $('[data-kz-search]'); var open = box.classList.toggle('hidden') === false;
      st.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var i = $('#site-search'); if (i) i.focus(); }
      return;
    }
    var add = t.closest('[data-kz-add]');
    if (add) { e.preventDefault(); addToCart(add.getAttribute('data-kz-add').split(','), parseInt(add.getAttribute('data-qty') || '1', 10), add); return; }
    var q = t.closest('[data-kz-qty]');
    if (q) { e.preventDefault(); setQty(q.getAttribute('data-kz-qty'), parseInt(q.getAttribute('data-q'), 10), q); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setCart(false); setMenu(false); } });

  /* Shop mega menu: opens on hover and keyboard focus. */
  $$('[data-kz-shop]').forEach(function (el) {
    var panel = $('[data-kz-shop-panel]', el); var link = el.querySelector('a');
    var show = function (v) { panel.classList.toggle('hidden', !v); link.setAttribute('aria-expanded', v ? 'true' : 'false'); };
    el.addEventListener('mouseenter', function () { show(true); });
    el.addEventListener('mouseleave', function () { show(false); });
    link.addEventListener('focus', function () { show(true); });
    el.addEventListener('focusout', function (e) { if (!el.contains(e.relatedTarget)) show(false); });
  });

  /* -------------------------------------------------------------- cart */
  function applyFragments(f) {
    if (!f) return;
    Object.keys(f).forEach(function (sel) {
      $$(sel).forEach(function (el) { var tmp = document.createElement('div'); tmp.innerHTML = f[sel]; if (tmp.firstElementChild) el.replaceWith(tmp.firstElementChild); });
    });
  }
  function post(url, data) {
    var body = new URLSearchParams(); Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
      .then(function (r) { return r.json(); });
  }
  function busy(el, on) { if (el) { el.classList.toggle('opacity-50', on); el.toggleAttribute('aria-busy', on); } }
  function addToCart(ids, qty, el) {
    if (!KZ.wcAjax) { if (el && el.href) window.location.href = el.href; return; }
    busy(el, true);
    var chain = Promise.resolve(null);
    ids.forEach(function (id) {
      chain = chain.then(function () { return post(KZ.wcAjax.replace('%%endpoint%%', 'add_to_cart'), { product_id: id, quantity: qty || 1 }); })
        .then(function (res) { if (res && res.error && res.product_url) { window.location.href = res.product_url; throw new Error('add failed'); } return res; });
    });
    chain.then(function (res) { if (res) applyFragments(res.fragments); setCart(true); })
      .catch(function () { if (el && el.href && !el.hasAttribute('data-kz-noredirect')) window.location.href = el.href; })
      .then(function () { busy(el, false); });
  }
  function setQty(key, qty, el) {
    busy(el, true);
    post(KZ.ajax, { action: 'kz_cart_set', nonce: KZ.nonce, key: key, qty: Math.max(0, qty) })
      .then(function (res) { applyFragments(res && res.fragments); })
      .catch(function () { window.location.reload(); })
      .then(function () { busy(el, false); });
  }
  /* Pages can be cached: refresh the cart count and drawer once if a cart exists. */
  if (KZ.wcAjax && /woocommerce_items_in_cart=1/.test(document.cookie)) {
    post(KZ.wcAjax.replace('%%endpoint%%', 'get_refreshed_fragments'), {}).then(function (r) { applyFragments(r && r.fragments); }).catch(function () {});
  }

  /* ------------------------------------------------------- product page */
  var buy = $('[data-kz-buy]');
  if (buy) {
    var qty = 1, price = parseFloat(buy.getAttribute('data-price')) || 0;
    var render = function () { $('[data-kz-qty-label]', buy).textContent = qty; $('[data-kz-qty-input]', buy).value = qty; $('[data-kz-buy-label]', buy).textContent = 'Add to cart · ' + inr(price * qty); };
    $$('[data-kz-step]', buy).forEach(function (b) { b.addEventListener('click', function () { qty = Math.max(1, qty + parseInt(b.getAttribute('data-kz-step'), 10)); render(); }); });
    buy.addEventListener('submit', function (e) {
      if (!KZ.wcAjax) return;
      e.preventDefault();
      var ids = buy.getAttribute('data-ids') ? buy.getAttribute('data-ids').split(',') : [buy.querySelector('[name="add-to-cart"]').value];
      addToCart(ids, qty, $('[data-kz-buy-label]', buy));
    });
    var bar = $('[data-kz-sticky-buy]');
    if (bar && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (en) {
        var show = !en[0].isIntersecting && en[0].boundingClientRect.top < 0;
        bar.classList.toggle('translate-y-0', show); bar.classList.toggle('translate-y-full', !show);
      }).observe(buy);
    }
  }
  $$('[data-kz-gallery]').forEach(function (g) {
    $$('[data-kz-thumb]', g).forEach(function (b) {
      b.addEventListener('click', function () {
        var k = b.getAttribute('data-kz-thumb');
        $$('[data-kz-slide]', g).forEach(function (im) { var on = im.getAttribute('data-kz-slide') === k; im.classList.toggle('opacity-100', on); im.classList.toggle('opacity-0', !on); });
        $$('[data-kz-thumb]', g).forEach(function (t) {
          var on = t === b; t.setAttribute('aria-selected', on ? 'true' : 'false');
          t.classList.toggle('border-ink', on); t.classList.toggle('border-line', !on); t.classList.toggle('opacity-80', !on); t.classList.toggle('hover:opacity-100', !on);
        });
      });
    });
  });

  /* -------------------------------------------------------------- shop */
  var shop = $('[data-kz-shop-browser]');
  if (shop) {
    var SYN = { haldi: 'turmeric', besan: 'chickpea', mulethi: 'licorice', gulab: 'rose', sahjan: 'moringa', drumstick: 'moringa', nettle: 'kuppaimeni', heel: 'foot', heels: 'foot', lips: 'lip', soap: 'soap bar', toner: 'mist' };
    var PRICE = { u400: function (p) { return p < 400; }, '400-800': function (p) { return p >= 400 && p <= 800; }, o800: function (p) { return p > 800; } };
    var cards = $$('[data-kz-results] > a');
    var state = { q: '', col: 'all', needs: [], skin: '', price: '', sort: 'recommended' };
    var sp = new URLSearchParams(window.location.search);
    state.q = sp.get('q') || ''; if (sp.get('need')) state.needs = [sp.get('need')]; if (sp.get('collection')) state.col = sp.get('collection');
    var qIn = $('[data-kz-q]', shop); qIn.value = state.q;
    var score = function (c, q) {
      var t = q.toLowerCase().trim(); if (!t) return 1;
      var s = 0; t.split(/\s+/).map(function (w) { return SYN[w] || w; }).forEach(function (w) {
        if (c.dataset.name.indexOf(w) > -1) s += 5;
        if (c.dataset.key.indexOf(w) > -1) s += 3;
        if (c.dataset.ing.indexOf(w) > -1) s += 2;
        if (c.dataset.rest.indexOf(w) > -1) s += 1;
      });
      return s;
    };
    var has = function (list, v) { return (' ' + list + ' ').indexOf(' ' + v + ' ') > -1; };
    var update = function () {
      var grid = $('[data-kz-results]', shop);
      var list = cards.map(function (c) { return { c: c, s: score(c, state.q), p: parseFloat(c.dataset.price) }; }).filter(function (x) {
        if (x.s <= 0) return false;
        if (state.col !== 'all' && !has(x.c.dataset.cols, state.col)) return false;
        if (state.needs.length && !state.needs.some(function (n) { return has(x.c.dataset.needs, n); })) return false;
        if (state.skin && !(has(x.c.dataset.skin, state.skin) || has(x.c.dataset.skin, 'all'))) return false;
        if (state.price && !PRICE[state.price](x.p)) return false;
        return true;
      });
      if (state.sort === 'low') list.sort(function (a, b) { return a.p - b.p; });
      else if (state.sort === 'high') list.sort(function (a, b) { return b.p - a.p; });
      else if (state.q) list.sort(function (a, b) { return b.s - a.s || a.c.dataset.order - b.c.dataset.order; });
      else list.sort(function (a, b) { return a.c.dataset.order - b.c.dataset.order; });
      cards.forEach(function (c) { c.classList.add('hidden'); });
      list.forEach(function (x) { x.c.classList.remove('hidden'); grid.appendChild(x.c); });
      var n = list.length;
      $('[data-kz-count]', shop).textContent = n + (n === 1 ? ' item' : ' items') + (state.q ? ' for “' + state.q + '”' : '');
      $('[data-kz-empty]', shop).classList.toggle('hidden', n > 0);
      grid.classList.toggle('hidden', n === 0);
      var active = (state.col !== 'all' ? 1 : 0) + state.needs.length + (state.skin ? 1 : 0) + (state.price ? 1 : 0);
      $$('button[data-kz-reset].link', shop).forEach(function (b) { if (b.closest('[data-kz-filters], [data-kz-filters-mobile]')) b.classList.toggle('hidden', active === 0); });
      var ft = $('[data-kz-filters-toggle]', shop); if (ft) ft.textContent = 'Filters' + (active ? ' (' + active + ')' : '');
      $$('button[data-col]', shop).forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.col === state.col ? 'true' : 'false'); });
      $$('button[data-need]', shop).forEach(function (b) { b.setAttribute('aria-pressed', state.needs.indexOf(b.dataset.need) > -1 ? 'true' : 'false'); });
      $$('button[data-skin]', shop).forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.skin === state.skin ? 'true' : 'false'); });
      $$('button[data-price]', shop).forEach(function (b) { if (b.tagName === 'BUTTON') b.setAttribute('aria-pressed', b.dataset.price === state.price ? 'true' : 'false'); });
      var u = new URLSearchParams(); if (state.q) u.set('q', state.q); if (state.col !== 'all') u.set('collection', state.col); if (state.needs.length === 1) u.set('need', state.needs[0]);
      history.replaceState(null, '', window.location.pathname + (u.toString() ? '?' + u.toString() : ''));
    };
    shop.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      if (b.hasAttribute('data-kz-reset')) { state = { q: '', col: 'all', needs: [], skin: '', price: '', sort: 'recommended' }; qIn.value = ''; $('[data-kz-sort]', shop).value = 'recommended'; }
      else if (b.dataset.col) state.col = b.dataset.col;
      else if (b.dataset.need) { var i = state.needs.indexOf(b.dataset.need); if (i > -1) state.needs.splice(i, 1); else state.needs.push(b.dataset.need); }
      else if (b.dataset.skin) state.skin = state.skin === b.dataset.skin ? '' : b.dataset.skin;
      else if (b.tagName === 'BUTTON' && b.dataset.price) state.price = state.price === b.dataset.price ? '' : b.dataset.price;
      else if (b.hasAttribute('data-kz-filters-toggle')) { var m = $('[data-kz-filters-mobile]', shop); var open = m.classList.toggle('hidden') === false; b.setAttribute('aria-expanded', open ? 'true' : 'false'); return; }
      else return;
      update();
    });
    qIn.addEventListener('input', function () { state.q = qIn.value; update(); });
    $('[data-kz-sort]', shop).addEventListener('change', function (e) { state.sort = e.target.value; update(); });
    update();
  }

  /* ------------------------------------------------- ingredient library */
  var ing = $('[data-kz-ingredients]');
  if (ing) {
    var g = 'All', q = '';
    var rows = $$('li[data-group]', ing);
    var run = function () {
      var n = 0;
      rows.forEach(function (li) { var ok = (g === 'All' || li.dataset.group === g) && (!q || li.dataset.text.indexOf(q) > -1); li.classList.toggle('hidden', !ok); if (ok) n++; });
      $('[data-kz-ing-empty]', ing).classList.toggle('hidden', n > 0);
      $$('button[data-group]', ing).forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.group === g ? 'true' : 'false'); });
    };
    ing.addEventListener('click', function (e) { var b = e.target.closest('button[data-group]'); if (b) { g = b.dataset.group; run(); } });
    $('[data-kz-ing-q]', ing).addEventListener('input', function (e) { q = e.target.value.toLowerCase(); run(); });
  }
})();
