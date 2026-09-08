<?php
/**
 * 1st / 2nd / 3rd course choices; selected choice is highlighted.
 *
 * @var callable $e
 * @var array<string, mixed> $choiceInfo
 */
$prefs = is_array($choiceInfo['preferences'] ?? null) ? $choiceInfo['preferences'] : [];
$selected = (int) ($choiceInfo['choice'] ?? 0);
$ordLabels = [1 => '1st choice', 2 => '2nd choice', 3 => '3rd choice'];
?>
<table class="iv-choices" width="100%" cellspacing="0" cellpadding="0">
    <?php foreach ([1, 2, 3] as $n):
        $prefName = trim((string) ($prefs[$n] ?? ''));
        $on = $selected === $n && $prefName !== '';
        $cell = $on ? ' iv-ch-on' : '';
    ?>
    <tr>
        <td class="iv-ch-ord<?php echo $cell; ?>" width="22%"><?php echo $e($ordLabels[$n]); ?></td>
        <td class="iv-ch-name<?php echo $cell; ?>" width="58%"><?php echo $prefName !== '' ? $e($prefName) : '—'; ?></td>
        <td class="iv-ch-flag<?php echo $cell; ?>" width="20%"><?php echo $on ? 'Selected' : '&nbsp;'; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
