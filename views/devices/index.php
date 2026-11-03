<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$scopeMode = (string) ($assetScope['mode'] ?? 'all');
$title = $scopeMode === 'staff' || $scopeMode === 'student' ? 'My Assets' : 'My Department Assets';
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title"><?php echo $e($title); ?></h1>
    <p class="asset-help">Search by asset name or asset code. Open an asset to view, assign, or update it.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="get" action="<?php echo APP_URL; ?>/devices/list" class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label" for="assetSearch">Search</label>
                <input id="assetSearch" name="q" class="form-control form-control-lg" value="<?php echo $e($filters['search'] ?? ''); ?>" placeholder="Asset name or asset code">
            </div>
            <div>
                <label class="form-label" for="assetCondition">Condition</label>
                <select id="assetCondition" name="condition" class="form-select form-select-lg">
                    <option value="">All</option>
                    <?php foreach (DeviceModel::SIMPLE_CONDITIONS as $cond): ?>
                    <option value="<?php echo $e($cond); ?>" <?php echo ($filters['condition'] ?? '') === $cond ? 'selected' : ''; ?>><?php echo $e($cond); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary btn-lg" type="submit">Search</button>
            <a class="btn btn-outline-secondary btn-lg" href="<?php echo APP_URL; ?>/devices/list">Clear</a>
        </div>
    </form>

    <?php if (empty($devices)): ?>
    <div class="asset-empty">
        <i class="fas fa-box-open"></i>
        <p>No assets found.</p>
        <?php if (!empty($canManage)): ?><a class="btn btn-primary btn-lg" href="<?php echo APP_URL; ?>/devices/create">Add New Asset</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($devices as $d):
            $id = (int) ($d['id'] ?? 0);
            $who = trim((string) (($d['assigned_student_name'] ?? '') ?: ($d['assigned_staff_name'] ?? '')));
            $whoKind = !empty($d['assigned_student_name']) ? 'Student' : ($who !== '' ? 'Staff' : '');
            $photo = trim((string) ($d['photo_path'] ?? ''));
        ?>
        <div class="col-md-6 col-xl-4">
            <article class="asset-card">
                <div class="d-flex gap-3">
                    <?php if ($photo !== ''): ?>
                    <img src="<?php echo APP_URL . '/' . $e($photo); ?>" alt="" class="asset-photo">
                    <?php else: ?>
                    <div class="asset-photo asset-photo-empty"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                    <div>
                        <h2 class="h5 mb-1"><?php echo $e($d['asset_name'] ?: ($d['brand'] . ' ' . $d['model']) ?: 'Asset'); ?></h2>
                        <div class="text-muted"><?php echo $e($d['asset_id'] ?? ''); ?></div>
                        <div><?php echo $e($d['room_location'] ?: ($d['owner_department_name'] ?? 'No location yet')); ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <div><strong>Assigned person:</strong> <?php echo $who !== '' ? $e($whoKind . ' — ' . $who) : 'Not assigned'; ?></div>
                    <div><strong>Condition:</strong> <?php echo $e($d['asset_condition'] ?: 'Good'); ?></div>
                    <div><strong>Status:</strong> <?php echo $e(DeviceModel::statusLabel((string) ($d['status'] ?? ''))); ?></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-primary" href="<?php echo APP_URL; ?>/devices/view?id=<?php echo $id; ?>"><i class="fas fa-eye"></i> View</a>
                    <?php if (!empty($canManage)): ?>
                    <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/devices/edit?id=<?php echo $id; ?>"><i class="fas fa-pen"></i> Edit</a>
                    <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/devices/assign?id=<?php echo $id; ?>"><i class="fas fa-user-check"></i> Assign</a>
                    <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/devices/condition?id=<?php echo $id; ?>"><i class="fas fa-screwdriver-wrench"></i> Update Condition</a>
                    <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/devices/transfer"><i class="fas fa-right-left"></i> Transfer</a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    $pages = (int) ceil(((int) ($total ?? 0)) / max(1, (int) ($perPage ?? 25)));
    if ($pages > 1):
    ?>
    <nav class="mt-3">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="btn btn-sm <?php echo $p === (int) ($currentPage ?? 1) ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?php echo APP_URL; ?>/devices/list?page=<?php echo $p; ?>&q=<?php echo rawurlencode((string) ($filters['search'] ?? '')); ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>
