<?php
/** Correction modal + JS. Expects $app, $csrf, $back (module-relative URL to return to). */
?>
<div class="modal fade att-page" id="correctionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?php echo att_e($app . '/attendance-management/correct'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>">
    <input type="hidden" name="back" value="<?php echo att_e($back); ?>">
    <input type="hidden" name="student_id" id="cmStudent"><input type="hidden" name="date" id="cmDate">
    <div class="modal-header"><h5 class="modal-title" id="cmTitle">Correct attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p class="mb-2"><strong id="cmWho"></strong><br><span class="att-muted small" id="cmCurrent"></span></p>
      <div class="att-note" id="cmNote" style="display:none"></div>
      <label class="form-label fw-semibold">New attendance *</label>
      <select class="form-select mb-3" name="new_status" id="cmStatus" required>
        <option value="">Select</option>
        <option value="full">Full Day Present (1.0)</option>
        <option value="half">Half Day Present (0.5)</option>
        <option value="absent">Absent (0)</option>
      </select>
      <label class="form-label fw-semibold">Reason *</label>
      <textarea class="form-control" name="reason" rows="3" required minlength="5" maxlength="500" placeholder="Why is this change needed?"></textarea>
      <div class="att-muted small mt-2">The original fingerprint records are not changed. This correction is stored with your name, the date and time, and the previous value.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="cmSubmit">Save</button></div>
  </form></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var labels = {full: 'Full Day Present', half: 'Half Day Present', absent: 'Absent'};
  document.querySelectorAll('.att-correct').forEach(function (b) {
    b.addEventListener('click', function () {
      var mode = b.dataset.mode;
      document.getElementById('cmStudent').value = b.dataset.student;
      document.getElementById('cmDate').value = b.dataset.date;
      document.getElementById('cmWho').textContent = b.dataset.student + ' — ' + b.dataset.name + ' (' + b.dataset.date + ')';
      document.getElementById('cmCurrent').textContent = 'Current: ' + (labels[b.dataset.current] || b.dataset.current);
      var status = document.getElementById('cmStatus');
      Array.prototype.forEach.call(status.options, function (o) { o.disabled = (o.value === b.dataset.current); });
      status.value = '';
      var note = document.getElementById('cmNote');
      var title = {direct: 'Correct attendance', request: 'Request attendance correction', exceptional: 'Exceptional correction request'}[mode] || 'Correct attendance';
      document.getElementById('cmTitle').textContent = title;
      document.getElementById('cmSubmit').textContent = mode === 'direct' ? 'Save correction' : 'Submit request';
      if (mode === 'request') { note.style.display = ''; note.textContent = 'This request will be sent to the Head of Department for approval.'; }
      else if (mode === 'exceptional') { note.style.display = ''; note.textContent = 'This month is closed. The request needs approval from the higher authority and is fully audited.'; }
      else { note.style.display = 'none'; }
      new bootstrap.Modal(document.getElementById('correctionModal')).show();
    });
  });
});
</script>
