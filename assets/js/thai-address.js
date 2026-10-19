/* thai-address.js
 * ช่องที่อยู่หน้า checkout: เลือกจังหวัด/อำเภอ/ตำบล และเลือกรหัสไปรษณีย์ของจังหวัดเอง
 * ข้อมูล: github.com/kongvut/thai-province-data (MIT) อิงข้อมูลกรมการปกครอง
 */
(function (root) {
  'use strict';

  // ลองโหลดไฟล์ในเว็บก่อน (เร็วและใช้ออฟไลน์ได้) ถ้าไม่มีค่อยโหลดจาก GitHub
  var SOURCES = [
    'assets/data/thai-address.json',
    'https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/province_with_district_and_sub_district.json'
  ];

  /* ---------- ส่วนจัดรูปข้อมูล (ไม่แตะหน้าเว็บ) ---------- */
  function str(v) { return String(v == null ? '' : v).trim(); }
  // ชื่ออาจเป็นข้อความ หรือ object แบบ {th:'..', en:'..'} -> ดึงเฉพาะชื่อภาษาไทยเป็นข้อความเสมอ
  function text(v) {
    if (v == null) return '';
    if (typeof v === 'string' || typeof v === 'number') return str(v);
    if (typeof v === 'object') return text(v.th != null ? v.th : (v.TH != null ? v.TH : (v.name_th != null ? v.name_th : (v.nameTh != null ? v.nameTh : v.thai))));
    return '';
  }
  function nameOf(o) {
    if (!o) return '';
    var v = o.name_th != null ? o.name_th : (o.nameTh != null ? o.nameTh : (o.nameThai != null ? o.nameThai : o.name));
    return text(v);
  }
  function zipOf(o) {
    var keys = ['zip_code', 'zipCode', 'zipcode', 'zip', 'postcode', 'postal_code', 'postalCode'];
    for (var i = 0; i < keys.length; i++) {
      if (o[keys[i]] != null) return str(o[keys[i]]).replace(/\D/g, '');
    }
    return '';
  }
  // รายการลูก: เป็น array หรือเป็น object ที่ใช้รหัสเป็นคีย์ก็ได้
  function kids(o, keys) {
    for (var i = 0; i < keys.length; i++) {
      var v = o[keys[i]];
      if (Array.isArray(v)) return v;
      if (v && typeof v === 'object' && !v.name_th && !v.name) return Object.keys(v).map(function (k) { return v[k]; });
    }
    return [];
  }

  // รับได้ทั้งรูปแบบ จังหวัด>อำเภอ>ตำบล (ซ้อนกัน) และรายการตำบลแบบแบน -> คืน [{p,d,s,z}]
  function normalize(data) {
    var out = [];
    if (data && !Array.isArray(data)) data = data.data || data.RECORDS || Object.keys(data).map(function (k) { return data[k]; });
    (data || []).forEach(function (a) {
      var ds = kids(a, ['districts', 'amphure', 'amphures']);
      if (ds.length) {
        ds.forEach(function (d) {
          kids(d, ['sub_districts', 'subdistricts', 'tambon', 'tambons']).forEach(function (s) {
            out.push({ p: nameOf(a), d: nameOf(d), s: nameOf(s), z: zipOf(s) });
          });
        });
      } else {
        var d = a.district || a.amphure || {};
        var p = d.province || a.province || {};
        out.push({ p: nameOf(p), d: nameOf(d), s: nameOf(a), z: zipOf(a) });
      }
    });
    return out.filter(function (r) { return r.p && r.d && r.s; });
  }

  function uniq(list) {
    var seen = {}, out = [];
    list.forEach(function (v) { if (v && !seen[v]) { seen[v] = 1; out.push(v); } });
    return out;
  }
  function sortTh(list, first) {
    return list.slice().sort(function (a, b) {
      if (first) { if (a === first) return -1; if (b === first) return 1; }
      return a.localeCompare(b, 'th');
    });
  }

  root.ThaiAddr = { normalize: normalize, uniq: uniq, sortTh: sortTh };
  if (typeof document === 'undefined') return;

  /* ---------- ส่วนหน้าเว็บ ---------- */
  var zipEl = document.getElementById('addr-zip');
  var pEl = document.getElementById('addr-province');
  var dEl = document.getElementById('addr-district');
  var sEl = document.getElementById('addr-subdistrict');
  var msg = document.getElementById('addr-msg');
  if (!zipEl || !pEl || !dEl || !sEl) return;

  var rows = [];
  function setMsg(text, warn) {
    if (!msg) return;
    msg.textContent = text || '';
    msg.style.color = warn ? '#d6336c' : '';
  }

  function setOptions(el, list, placeholder, selected) {
    el.innerHTML = '';
    var first = document.createElement('option');
    first.value = '';
    first.textContent = '— ' + placeholder + ' —';
    el.appendChild(first);
    list.forEach(function (v) {
      var o = document.createElement('option');
      o.value = v;
      o.textContent = v;
      if (v === selected) o.selected = true;
      el.appendChild(o);
    });
    el.value = selected || '';
  }

  // ใช้ค่าที่เลือกอยู่ (หรือค่าที่ส่งกลับมาหลังฟอร์ม error) ถ้ามีในรายการ; ถ้ารายการมีตัวเดียวเลือกให้เลย
  function pick(el, list) {
    var c = el.value || el.getAttribute('data-want') || '';
    if (list.indexOf(c) !== -1) return c;
    return list.length === 1 ? list[0] : '';
  }

  function update() {
    var provs = sortTh(uniq(rows.map(function (r) { return r.p; })), 'กรุงเทพมหานคร');
    var pv = pick(pEl, provs);
    setOptions(pEl, provs, 'เลือกจังหวัด', pv);

    var dists = pv ? sortTh(uniq(rows.filter(function (r) { return r.p === pv; }).map(function (r) { return r.d; }))) : [];
    var dv = pick(dEl, dists);
    setOptions(dEl, dists, pv ? 'เลือกอำเภอ/เขต' : 'เลือกจังหวัดก่อน', dv);

    var subs = (pv && dv) ? sortTh(uniq(rows.filter(function (r) { return r.p === pv && r.d === dv; }).map(function (r) { return r.s; }))) : [];
    var sv = pick(sEl, subs);
    setOptions(sEl, subs, dv ? 'เลือกตำบล/แขวง' : 'เลือกอำเภอก่อน', sv);

    var zips = (pv && dv) ? sortTh(uniq(rows.filter(function (r) { return r.p === pv && r.d === dv; }).map(function (r) { return r.z; }))) : [];
    var zip = zipEl.value || zipEl.getAttribute('data-want') || '';
    setOptions(zipEl, zips, dv ? 'เลือกรหัสไปรษณีย์' : (pv ? 'เลือกอำเภอ/เขตก่อน' : 'เลือกจังหวัดก่อน'), zips.indexOf(zip) !== -1 ? zip : '');
    setMsg('');

    [pEl, dEl, sEl, zipEl].forEach(function (el) { el.removeAttribute('data-want'); });
  }

  // ถ้าโหลดข้อมูลไม่ได้ เปลี่ยนเป็นช่องพิมพ์ธรรมดา เพื่อให้สั่งซื้อต่อได้
  function fallbackToText() {
    [pEl, dEl, sEl, zipEl].forEach(function (el) {
      var inp = document.createElement('input');
      inp.type = 'text'; inp.name = el.name; inp.id = el.id; inp.required = true;
      inp.value = el.value || el.getAttribute('data-want') || '';
      el.parentNode.replaceChild(inp, el);
    });
    setMsg('โหลดรายการจังหวัดไม่สำเร็จ กรุณาพิมพ์จังหวัด อำเภอ และตำบลเอง');
  }

  function load(i) {
    if (i >= SOURCES.length) return Promise.reject(new Error('no source'));
    return fetch(SOURCES[i], { cache: 'force-cache' })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (j) { var rs = normalize(j); if (!rs.length) throw new Error('empty'); return rs; })
      .catch(function () { return load(i + 1); });
  }

  load(0).then(function (rs) {
    rows = rs;
    if (typeof console !== 'undefined' && console.debug) console.debug('[thai-address] loaded', rows.length, 'rows; sample:', rows[0]);
    update();
    pEl.addEventListener('change', function () {
      zipEl.value = '';
      update();
    });
    [dEl, sEl].forEach(function (el) {
      el.addEventListener('change', update);
    });
  }).catch(fallbackToText);
})(typeof window !== 'undefined' ? window : globalThis);
