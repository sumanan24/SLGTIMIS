<?php
/**
 * Province → district cascade; district → post code (uses SL_PROVINCE_DISTRICTS, SL_DISTRICT_POSTAL, APP_FORM_OLD).
 */
?>
<script>
(function () {
  var map = (typeof window.SL_PROVINCE_DISTRICTS === 'object' && window.SL_PROVINCE_DISTRICTS) ? window.SL_PROVINCE_DISTRICTS : {};
  var postal = (typeof window.SL_DISTRICT_POSTAL === 'object' && window.SL_DISTRICT_POSTAL) ? window.SL_DISTRICT_POSTAL : {};
  var provSel = document.getElementById('student_province');
  var distSel = document.getElementById('student_district');
  var zipInput = document.getElementById('student_zip_code');
  if (!provSel || !distSel) return;

  function applyPostalForDistrict(district) {
    if (!zipInput) return;
    if (!district) {
      zipInput.value = '';
      return;
    }
    var code = postal[district];
    if (code) {
      zipInput.value = code;
    }
  }

  function fillDistricts(province, selectedDistrict, opts) {
    opts = opts || {};
    var keepZipFromOld = !!opts.keepZipFromOld;
    distSel.innerHTML = '';
    var opt0 = document.createElement('option');
    opt0.value = '';
    if (!province) {
      opt0.textContent = 'Choose province first…';
      distSel.appendChild(opt0);
      if (zipInput && !keepZipFromOld) {
        zipInput.value = '';
      }
      return;
    }
    opt0.textContent = 'Choose district…';
    distSel.appendChild(opt0);
    var list = map[province] || [];
    list.forEach(function (d) {
      var opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      if (selectedDistrict && selectedDistrict === d) opt.selected = true;
      distSel.appendChild(opt);
    });
    if (selectedDistrict && zipInput) {
      var old = (typeof window.APP_FORM_OLD === 'object' && window.APP_FORM_OLD) ? window.APP_FORM_OLD : {};
      if (keepZipFromOld && old.student_zip_code) {
        zipInput.value = String(old.student_zip_code);
      } else {
        applyPostalForDistrict(selectedDistrict);
      }
    }
  }

  provSel.addEventListener('change', function () {
    fillDistricts(provSel.value, '');
  });

  distSel.addEventListener('change', function () {
    applyPostalForDistrict(distSel.value);
  });

  document.addEventListener('DOMContentLoaded', function () {
    var old = (typeof window.APP_FORM_OLD === 'object' && window.APP_FORM_OLD) ? window.APP_FORM_OLD : {};
    var p = old.student_province || '';
    var d = old.student_district || '';
    if (p) {
      provSel.value = p;
      fillDistricts(p, d, { keepZipFromOld: !!old.student_zip_code });
    } else {
      fillDistricts('', '');
    }
  });
})();
</script>
