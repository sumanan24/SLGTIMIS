<?php
/**
 * Dashboard section: hostel availability (ring summary + a card per hostel + room detail modal).
 * Uses $h, $n from views/dashboard/index.php.
 */
$hostels = $hostelOverview['hostels'] ?? [];
$hostelTotals = $hostelOverview['totals'] ?? [];

$occColor = function ($pct) {
    $pct = (int) $pct;
    return $pct >= 90 ? '#e11d48' : ($pct >= 70 ? '#f59e0b' : '#16a34a');
};
$genderTone = function ($gender) {
    $g = strtolower((string) $gender);
    return $g === 'male' ? 'tone-blue' : ($g === 'female' ? 'tone-pink' : 'tone-violet');
};
$genderIcon = function ($gender) {
    $g = strtolower((string) $gender);
    return $g === 'male' ? 'fa-mars' : ($g === 'female' ? 'fa-venus' : 'fa-venus-mars');
};

$hostelJson = [];
foreach ($hostels as $hostel) {
    $hostelJson[(string) $hostel['id']] = [
        'name' => $hostel['name'],
        'gender' => $hostel['gender'],
        'location' => $hostel['location'],
        'blocks' => $hostel['blocks'],
        'rooms' => $hostel['rooms'],
        'capacity' => $hostel['capacity'],
        'allocated' => $hostel['allocated'],
        'available' => $hostel['available'],
        'fullRooms' => $hostel['full_rooms'],
        'occupancy' => $hostel['occupancy'],
        'roomList' => $hostel['room_list'],
    ];
}
$hostelJsonStr = json_encode($hostelJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$totalOcc = (int) ($hostelTotals['occupancy'] ?? 0);
?>
<section class="dh-section tone-amber" id="sec-hostel">
    <div class="dh-sec-head">
        <span class="dh-sec-icon"><i class="fas fa-bed"></i></span>
        <div>
            <h2>Hostel availability</h2>
            <p>Free beds right now, by hostel</p>
        </div>
        <a class="dh-link" href="<?php echo APP_URL; ?>/hostel-report">Open hostel report <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($hostels)): ?>
        <div class="dh-card"><p class="dh-empty"><i class="fas fa-bed"></i>No hostel rooms have been set up.</p></div>
    <?php else: ?>
        <div class="dh-hostel-layout">
            <div class="dh-card dh-ring-card">
                <div class="dh-ring" style="--p:<?php echo min(100, $totalOcc); ?>;--c:<?php echo $occColor($totalOcc); ?>" role="img" aria-label="<?php echo $totalOcc; ?> percent of beds occupied">
                    <div><strong><?php echo $totalOcc; ?>%</strong><span>Occupied</span></div>
                </div>
                <div>
                    <div class="dh-mini-stats">
                        <div class="dh-mini"><span>Beds</span><strong><?php echo $n($hostelTotals['capacity'] ?? 0); ?></strong></div>
                        <div class="dh-mini"><span>Used</span><strong><?php echo $n($hostelTotals['allocated'] ?? 0); ?></strong></div>
                        <div class="dh-mini good"><span>Free</span><strong><?php echo $n($hostelTotals['available'] ?? 0); ?></strong></div>
                    </div>
                    <p class="text-muted small mb-0 mt-2"><?php echo $n($hostelTotals['hostels'] ?? 0); ?> hostels · <?php echo $n($hostelTotals['rooms'] ?? 0); ?> rooms</p>
                </div>
            </div>

            <div class="dh-hostel-grid">
                <?php foreach ($hostels as $hostel): ?>
                    <?php $occ = (int) $hostel['occupancy']; $c = $occColor($occ); ?>
                    <article class="dh-card dh-hostel <?php echo $genderTone($hostel['gender']); ?>">
                        <div class="dh-hostel-top">
                            <h4><?php echo $h($hostel['name']); ?></h4>
                            <span class="dh-tag"><i class="fas <?php echo $genderIcon($hostel['gender']); ?> me-1"></i><?php echo $h($hostel['gender'] ?: 'Mixed'); ?></span>
                        </div>
                        <div class="dh-free" style="--free:<?php echo $hostel['available'] > 0 ? '#16a34a' : '#e11d48'; ?>">
                            <strong><?php echo $n($hostel['available']); ?></strong>
                            <span>free <?php echo $hostel['available'] === 1 ? 'bed' : 'beds'; ?></span>
                        </div>
                        <div>
                            <div class="dh-occ" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $occ; ?>" aria-label="Occupancy"><span style="width:<?php echo min(100, $occ); ?>%;--c:<?php echo $c; ?>"></span></div>
                            <div class="dh-occ-meta mt-1"><span><?php echo $occ; ?>% occupied</span><span><?php echo $n($hostel['allocated']); ?> / <?php echo $n($hostel['capacity']); ?></span></div>
                        </div>
                        <div class="dh-hostel-stats">
                            <div><span>Rooms</span><strong><?php echo $n($hostel['rooms']); ?></strong></div>
                            <div><span>Full</span><strong><?php echo $n($hostel['full_rooms']); ?></strong></div>
                            <div><span>Blocks</span><strong><?php echo $n($hostel['blocks']); ?></strong></div>
                        </div>
                        <button type="button" class="dh-btn js-hostel-more" data-hostel="<?php echo $h($hostel['id']); ?>">
                            <i class="fas fa-circle-info"></i> More information
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal fade" id="hostelModal" tabindex="-1" aria-labelledby="hostelModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="border-radius:16px;overflow:hidden">
                    <div class="modal-header" style="background:linear-gradient(120deg,#0b2545,#1d4ed8);color:#fff">
                        <div>
                            <h5 class="modal-title mb-0" id="hostelModalTitle">Hostel</h5>
                            <div class="small" id="hostelModalMeta" style="color:#dbe7ff"></div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body dash-page" style="max-width:none">
                        <div class="dh-mini-stats mb-3" id="hostelModalKpis" style="grid-template-columns:repeat(4,1fr)"></div>
                        <div class="btn-group btn-group-sm mb-2 dh-room-filter" role="group" aria-label="Room filter">
                            <button type="button" class="btn btn-outline-primary active" data-room-filter="all">All rooms</button>
                            <button type="button" class="btn btn-outline-success" data-room-filter="available">With free beds</button>
                            <button type="button" class="btn btn-outline-danger" data-room-filter="full">Full</button>
                        </div>
                        <div class="table-responsive">
                            <table class="dh-table">
                                <thead>
                                    <tr><th>Block</th><th>Room</th><th class="num">Beds</th><th class="num">Occupied</th><th class="num">Free</th><th>Status</th></tr>
                                </thead>
                                <tbody id="hostelModalRooms"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-outline-primary btn-sm" id="hostelModalReport" href="<?php echo APP_URL; ?>/hostel-report"><i class="fas fa-file-lines me-1"></i>Open in hostel report</a>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Bootstrap's bundle loads at the end of the page, so wait for it before wiring the modal.
        document.addEventListener('DOMContentLoaded', function () {
            var hostels = <?php echo $hostelJsonStr ?: '{}'; ?>;
            var modalEl = document.getElementById('hostelModal');
            if (!modalEl || !window.bootstrap) { return; }
            // Keep the modal outside the page wrapper so it always sits above the sidebar and navbar.
            document.body.appendChild(modalEl);
            var modal = new bootstrap.Modal(modalEl);
            var roomsBody = document.getElementById('hostelModalRooms');
            var current = null;
            var filter = 'all';
            var reportBase = <?php echo json_encode(APP_URL . '/hostel-report', JSON_UNESCAPED_SLASHES); ?>;

            function el(tag, text, className) {
                var node = document.createElement(tag);
                if (text !== undefined) { node.textContent = text; }
                if (className) { node.className = className; }
                return node;
            }
            function stat(label, value, good) {
                var box = el('div', undefined, 'dh-mini' + (good ? ' good' : ''));
                box.appendChild(el('span', label));
                box.appendChild(el('strong', String(value)));
                return box;
            }
            function renderRooms() {
                roomsBody.textContent = '';
                var shown = 0;
                current.roomList.forEach(function (room) {
                    var state = room.available === 0 ? 'full' : (room.allocated === 0 ? 'empty' : 'available');
                    if (filter === 'available' && room.available === 0) { return; }
                    if (filter === 'full' && room.available > 0) { return; }
                    shown++;
                    var tr = document.createElement('tr');
                    tr.appendChild(el('td', room.block));
                    tr.appendChild(el('td', room.room));
                    tr.appendChild(el('td', room.capacity, 'num'));
                    tr.appendChild(el('td', room.allocated, 'num'));
                    tr.appendChild(el('td', room.available, 'num'));
                    var td = document.createElement('td');
                    var label = state === 'full' ? 'Full' : (state === 'empty' ? 'Empty' : 'Partly free');
                    var cls = state === 'full' ? 'bg-danger' : (state === 'empty' ? 'bg-success' : 'bg-warning text-dark');
                    td.appendChild(el('span', label, 'badge ' + cls));
                    tr.appendChild(td);
                    roomsBody.appendChild(tr);
                });
                if (!shown) {
                    var empty = document.createElement('tr');
                    var cell = el('td', 'No rooms match this filter.', 'dh-empty');
                    cell.colSpan = 6;
                    empty.appendChild(cell);
                    roomsBody.appendChild(empty);
                }
            }
            function open(id) {
                current = hostels[id];
                if (!current) { return; }
                filter = 'all';
                modalEl.querySelectorAll('[data-room-filter]').forEach(function (b) {
                    b.classList.toggle('active', b.getAttribute('data-room-filter') === 'all');
                });
                document.getElementById('hostelModalTitle').textContent = current.name;
                var kpis = document.getElementById('hostelModalKpis');
                kpis.textContent = '';
                kpis.appendChild(stat('Beds', current.capacity));
                kpis.appendChild(stat('Occupied', current.allocated));
                kpis.appendChild(stat('Free', current.available, true));
                kpis.appendChild(stat('Occupancy', current.occupancy + '%'));
                var meta = [];
                if (current.gender) { meta.push('For ' + current.gender); }
                if (current.location) { meta.push(current.location); }
                meta.push(current.blocks + (current.blocks === 1 ? ' block' : ' blocks'));
                meta.push(current.rooms + ' rooms (' + current.fullRooms + ' full)');
                document.getElementById('hostelModalMeta').textContent = meta.join(' · ');
                document.getElementById('hostelModalReport').href = reportBase + '?hostel_id=' + encodeURIComponent(id);
                renderRooms();
                modal.show();
            }
            document.querySelectorAll('.js-hostel-more').forEach(function (btn) {
                btn.addEventListener('click', function () { open(btn.getAttribute('data-hostel')); });
            });
            modalEl.querySelectorAll('[data-room-filter]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    filter = btn.getAttribute('data-room-filter');
                    modalEl.querySelectorAll('[data-room-filter]').forEach(function (b) { b.classList.toggle('active', b === btn); });
                    renderRooms();
                });
            });
        });
        </script>
    <?php endif; ?>
</section>
