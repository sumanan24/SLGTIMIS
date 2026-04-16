<?php
/**
 * Live validation: phone, WhatsApp (Sri Lanka), NIC — matches StudentApplicationController rules.
 */
?>
<script>
(function () {
  var phoneErr = 'Use a valid Sri Lanka number: 10 digits starting with 0 (e.g. 0771234567), or +94 and 9 digits after.';
  var nicErr = 'NIC must be 9 numbers + V or X, or 12 numbers only.';

  function normalizeNic(s) {
    return String(s || '').toUpperCase().trim().replace(/[\s\-_]+/g, '');
  }

  function isValidNic(n) {
    return /^(\d{9}[VX]|\d{12})$/.test(normalizeNic(n));
  }

  function nicState(raw) {
    var n = normalizeNic(raw);
    if (!n) return { kind: 'empty' };
    if (/^\d{9}[VX]$/.test(n) || /^\d{12}$/.test(n)) return { kind: 'valid' };
    if (n.length > 12) return { kind: 'invalid', msg: 'NIC is too long.' };
    if (/[^0-9VX]/.test(n)) return { kind: 'invalid', msg: 'Use only numbers, and V or X for the old NIC format.' };
    if (n.length <= 9) {
      return /^\d+$/.test(n) ? { kind: 'incomplete' } : { kind: 'invalid', msg: nicErr };
    }
    if (n.length === 10) {
      if (/^\d{10}$/.test(n)) return { kind: 'incomplete' };
      if (/^\d{9}[VX]$/.test(n)) return { kind: 'valid' };
      return { kind: 'invalid', msg: nicErr };
    }
    if (n.length === 11) {
      return /^\d{11}$/.test(n) ? { kind: 'incomplete' } : { kind: 'invalid', msg: nicErr };
    }
    return /^\d{12}$/.test(n) ? { kind: 'valid' } : { kind: 'invalid', msg: 'New NIC must be twelve numbers only.' };
  }

  function stripLkPhonePrefixes(d) {
    if (d.indexOf('94') === 0 && d.length > 2) d = d.slice(2);
    else if (d.charAt(0) === '0' && d.length > 1) d = d.slice(1);
    return d;
  }

  function lkPhoneState(raw) {
    var d = String(raw || '').replace(/\D/g, '');
    if (!d) return { kind: 'empty' };
    d = stripLkPhonePrefixes(d);
    if (d.length < 9) return { kind: 'incomplete' };
    if (d.length === 9) {
      return /^[1-9]\d{8}$/.test(d) ? { kind: 'valid' } : { kind: 'invalid', msg: phoneErr };
    }
    return { kind: 'invalid', msg: phoneErr };
  }

  function applyVisual(input, fb, state, invalidMsg) {
    if (!input || !fb) return;
    input.classList.remove('is-valid', 'is-invalid');
    fb.classList.remove('app-live-feedback-valid', 'app-live-feedback-invalid', 'app-live-feedback-muted');
    fb.textContent = '';
    if (state === 'valid') {
      input.classList.add('is-valid');
      fb.classList.add('app-live-feedback-valid');
      fb.textContent = 'Looks good.';
      input.setCustomValidity('');
      return;
    }
    if (state === 'invalid') {
      input.classList.add('is-invalid');
      fb.classList.add('app-live-feedback-invalid');
      fb.textContent = invalidMsg || '';
      input.setCustomValidity(invalidMsg || 'Invalid value.');
      return;
    }
    input.setCustomValidity('');
    fb.classList.add('app-live-feedback-muted');
    if (state === 'incomplete') {
      fb.textContent = input.id === 'student_nic'
        ? 'Old NIC: nine digits then V or X. New NIC: twelve digits.'
        : 'Example: 0771234567 or +94 77 123 4567.';
    }
  }

  function refreshPhone(input, fb) {
    var st = lkPhoneState(input.value);
    if (st.kind === 'empty') {
      input.classList.remove('is-valid', 'is-invalid');
      fb.classList.remove('app-live-feedback-valid', 'app-live-feedback-invalid', 'app-live-feedback-muted');
      fb.textContent = '';
      input.setCustomValidity('');
      return;
    }
    if (st.kind === 'valid') applyVisual(input, fb, 'valid');
    else if (st.kind === 'invalid') applyVisual(input, fb, 'invalid', st.msg);
    else applyVisual(input, fb, 'incomplete');
  }

  function refreshNic(input, fb) {
    var st = nicState(input.value);
    if (st.kind === 'empty') {
      input.classList.remove('is-valid', 'is-invalid');
      fb.classList.remove('app-live-feedback-valid', 'app-live-feedback-invalid', 'app-live-feedback-muted');
      fb.textContent = '';
      input.setCustomValidity('');
      return;
    }
    if (st.kind === 'valid') applyVisual(input, fb, 'valid');
    else if (st.kind === 'invalid') applyVisual(input, fb, 'invalid', st.msg);
    else applyVisual(input, fb, 'incomplete');
  }

  function bind(inputId, fbId, fn) {
    var input = document.getElementById(inputId);
    var fb = document.getElementById(fbId);
    if (!input || !fb) return;
    function run() { fn(input, fb); }
    input.addEventListener('input', run);
    input.addEventListener('blur', run);
    input.addEventListener('paste', function () { setTimeout(run, 0); });
    run();
  }

  bind('student_phone', 'student_phone_feedback', refreshPhone);
  bind('student_whatsapp', 'student_whatsapp_feedback', refreshPhone);
  bind('student_nic', 'student_nic_feedback', refreshNic);

  var form = document.querySelector('form.app-student-application-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      var p = document.getElementById('student_phone');
      var w = document.getElementById('student_whatsapp');
      var n = document.getElementById('student_nic');
      var pf = document.getElementById('student_phone_feedback');
      var wf = document.getElementById('student_whatsapp_feedback');
      var nf = document.getElementById('student_nic_feedback');
      if (p && pf) refreshPhone(p, pf);
      if (w && wf) refreshPhone(w, wf);
      if (n && nf) refreshNic(n, nf);
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        return;
      }
      if (p && lkPhoneState(p.value).kind !== 'valid') {
        e.preventDefault();
        p.setCustomValidity(phoneErr);
        p.reportValidity();
        p.setCustomValidity('');
        if (pf) refreshPhone(p, pf);
        return;
      }
      if (w && lkPhoneState(w.value).kind !== 'valid') {
        e.preventDefault();
        w.setCustomValidity(phoneErr);
        w.reportValidity();
        w.setCustomValidity('');
        if (wf) refreshPhone(w, wf);
        return;
      }
      if (n && !isValidNic(n.value)) {
        e.preventDefault();
        n.setCustomValidity(nicErr);
        n.reportValidity();
        n.setCustomValidity('');
        if (nf) refreshNic(n, nf);
        return;
      }
    });
  }
})();
</script>
