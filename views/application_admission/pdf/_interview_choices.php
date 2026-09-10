<?php
/**
 * 1st / 2nd / 3rd course choices; selected choice is highlighted.
 * Non-selected choices show "Not Selected" only when the applied courses differ.
 *
 * @var callable $e
 * @var array<string, mixed> $choiceInfo
 */
$prefs = is_array($choiceInfo['preferences'] ?? null) ? $choiceInfo['preferences'] : [];
$selected = (int) ($choiceInfo['choice'] ?? 0);
$ordLabels = [1 => '1st choice', 2 => '2nd choice', 3 => '3rd choice'];
$namedNames = [];
foreach ([1, 2, 3] as $n) {
    $prefCheck = trim((string) ($prefs[$n] ?? ''));
    if ($prefCheck !== '') {
        $namedNames[] = mb_strtolower($prefCheck, 'UTF-8');
    }
}
$allChoicesSameCourse = $namedNames !== [] && count(array_unique($namedNames)) === 1;
?>
<table class="iv-choices" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <th class="iv-ch-ord" width="20%">NO</th>
        <th class="iv-ch-name" width="58%">COURSENAME</th>
        <th class="iv-ch-flag" width="22%">RESULTS</th>
    </tr>
    <?php foreach ([1, 2, 3] as $n):
        $prefName = trim((string) ($prefs[$n] ?? ''));
        $on = $selected === $n && $prefName !== '';
        $cell = $on ? ' iv-ch-on' : '';
        if ($on) {
            $resultLabel = 'Selected';
        } elseif ($prefName !== '' && !$allChoicesSameCourse) {
            $resultLabel = 'Not Selected';
        } else {
            $resultLabel = '';
        }
    ?>
    <tr>
        <td class="iv-ch-ord<?php echo $cell; ?>" width="20%"><?php echo $e($ordLabels[$n]); ?></td>
        <td class="iv-ch-name<?php echo $cell; ?>" width="58%"><?php echo $prefName !== '' ? $e($prefName) : '—'; ?></td>
        <td class="iv-ch-flag<?php echo $cell; ?>" width="22%"><?php echo $resultLabel !== '' ? $e($resultLabel) : '&nbsp;'; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
