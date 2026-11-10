<?php
/** Tab strip shared by the Training Programme pages. Expects $pageKey. */
$app = rtrim((string) APP_URL, '/');
$tabs = [
    'tp-schedule' => ['Programme Schedule', '/training-programmes'],
    'tp-activities' => ['Training Activities', '/training-programmes/activities'],
    'tp-practical' => ['Practical Training', '/training-programmes/practical'],
    'tp-progress' => ['Training Progress', '/training-programmes/progress'],
];
$current = $pageKey ?? ($page ?? '');
?>
<div class="att-tabs">
  <?php foreach ($tabs as $key => $t): ?>
    <a href="<?php echo att_e($app . $t[1]); ?>" class="<?php echo $current === $key ? 'active' : ''; ?>"><?php echo att_e($t[0]); ?></a>
  <?php endforeach; ?>
</div>
