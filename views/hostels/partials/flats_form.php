<?php
/**
 * Flats repeater shared by hostels/create and hostels/edit.
 * @var int $initialFlatRows number of empty flat rows to show first
 */
$initialFlatRows = isset($initialFlatRows) ? (int) $initialFlatRows : 1;
?>
<div class="border rounded p-3 mb-3 bg-light" id="flatsBox">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h6 class="fw-bold mb-0"><i class="fas fa-door-open me-2"></i>Flats</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addFlatBtn">
            <i class="fas fa-plus me-1"></i>Add Flat
        </button>
    </div>
    <p class="text-muted small mb-3">
        Enter each flat with its number of rooms and the beds in each room. The rooms are created for you (Room 1, Room 2, ...).
        The hostel capacity is the total number of beds.
    </p>
    <div id="flatRows"></div>
    <div class="d-flex flex-wrap justify-content-between gap-2 pt-2 mt-2 border-top small">
        <span class="text-muted">Flats to add: <strong id="flatTotalFlats">0</strong> &middot; Rooms: <strong id="flatTotalRooms">0</strong></span>
        <span class="text-muted">Beds to add: <strong id="flatTotalBeds">0</strong></span>
    </div>
</div>
<script>
(function () {
    var rowsBox = document.getElementById('flatRows');
    var addBtn = document.getElementById('addFlatBtn');

    function recount() {
        var flats = 0, rooms = 0, beds = 0;
        rowsBox.querySelectorAll('.flat-row').forEach(function (row) {
            var name = row.querySelector('[name="flat_name[]"]').value.trim();
            var r = parseInt(row.querySelector('[name="flat_rooms[]"]').value, 10) || 0;
            var b = parseInt(row.querySelector('[name="flat_beds[]"]').value, 10) || 0;
            row.querySelector('.flat-row-beds').textContent = (r * b) + ' beds';
            if (name !== '' || r > 0) { flats++; rooms += r; beds += r * b; }
        });
        document.getElementById('flatTotalFlats').textContent = flats;
        document.getElementById('flatTotalRooms').textContent = rooms;
        document.getElementById('flatTotalBeds').textContent = beds;
    }

    function addRow() {
        var row = document.createElement('div');
        row.className = 'flat-row row g-2 align-items-end mb-2';
        row.innerHTML =
            '<div class="col-md-4"><label class="form-label small fw-semibold mb-1">Flat Name / No.</label>' +
            '<input type="text" class="form-control" name="flat_name[]" maxlength="50" placeholder="e.g. 1 or A"></div>' +
            '<div class="col-6 col-md-3"><label class="form-label small fw-semibold mb-1">Rooms</label>' +
            '<input type="number" class="form-control" name="flat_rooms[]" min="1" max="100" placeholder="e.g. 4"></div>' +
            '<div class="col-6 col-md-3"><label class="form-label small fw-semibold mb-1">Beds per Room</label>' +
            '<input type="number" class="form-control" name="flat_beds[]" min="1" max="50" placeholder="e.g. 8"></div>' +
            '<div class="col-12 col-md-2 d-flex align-items-center justify-content-between gap-2">' +
            '<span class="flat-row-beds text-muted small">0 beds</span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger flat-remove" title="Remove flat"><i class="fas fa-times"></i></button></div>';
        rowsBox.appendChild(row);
        recount();
        return row;
    }

    addBtn.addEventListener('click', function () { addRow().querySelector('input').focus(); });
    rowsBox.addEventListener('input', recount);
    rowsBox.addEventListener('click', function (e) {
        var btn = e.target.closest('.flat-remove');
        if (btn) { btn.closest('.flat-row').remove(); recount(); }
    });

    for (var i = 0; i < <?php echo $initialFlatRows; ?>; i++) { addRow(); }
})();
</script>