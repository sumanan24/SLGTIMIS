<?php
/**
 * One O/L subject row: slot-specific subject list (main 1–6, basket 7–9) + result (dropdown).
 *
 * @var int $i Subject slot 1–9
 * @var array<string, mixed> $old Form values (unescaped) for selected options
 * @var string $variant l05 | wizard | form
 * @var string $extraAttr Extra HTML attributes for both selects (e.g. ' required')
 * @var string $slotReqHtml Extra HTML after slot label (e.g. $req)
 */
declare(strict_types=1);

$i = (int) ($i ?? 1);
if ($i < 1 || $i > 9) {
    $i = 1;
}
$s = sprintf('%02d', $i);
$old = $old ?? [];
$variant = $variant ?? 'l05';
$extraAttr = $extraAttr ?? '';
$slotReqHtml = $slotReqHtml ?? '';
$rawName = (string) ($old['ol_subject_name_' . $s] ?? '');
$rawRes = (string) ($old['ol_subject_' . $s . '_marks'] ?? '');

/** @var array<string, array{label: string, subjects: list<string>}> $olSlotMap */
$olSlotMap = require dirname(__DIR__, 2) . '/config/ol_subject_slots.php';
$def = $olSlotMap[$s] ?? ['label' => 'O/L subject ' . $i, 'subjects' => []];
$slotLabel = (string) ($def['label'] ?? 'O/L subject');
$slotSubjects = $def['subjects'] ?? [];
$gradeLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'S', 'W', 'W+', 'W-'];

if ($variant === 'form') {
    ?>
                <div class="col-12 col-lg-6">
                    <div class="app-ol-row app-exam-subj-cell h-100">
                        <div class="app-subject-block">
                            <div class="app-subj-title"><?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?><?php echo $slotReqHtml; ?></div>
                            <div class="app-subj-fields">
                                <label class="form-label visually-hidden" for="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                                <select name="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>" id="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>" class="form-select form-select-sm app-subj-name app-exam-input-compact" aria-label="<?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $extraAttr; ?>>
                                    <option value="">Choose subject…</option>
                                    <?php foreach ($slotSubjects as $subj) : ?>
                                    <option value="<?php echo htmlspecialchars($subj, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rawName === $subj ? ' selected' : ''; ?>><?php echo htmlspecialchars($subj, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="app-subj-mark-group">
                                    <label class="form-label app-subj-mark-label mb-0" for="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks">Result</label>
                                    <select name="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks" id="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks" class="form-select form-select-sm app-mark-input app-exam-input-compact" aria-label="<?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?> result"<?php echo $extraAttr; ?>>
                                        <option value="">Choose result…</option>
                                        <?php foreach ($gradeLetters as $g) : ?>
                                        <option value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rawRes === $g ? ' selected' : ''; ?>><?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    <?php
    return;
}

/* l05 + wizard */
?>
                                        <div class="col-12 col-lg-6 col-xl-4">
                                            <div class="l05-subject-slot">
                                                <div class="l05-slot-label"><?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?><?php echo $slotReqHtml; ?></div>
                                                <label class="form-label visually-hidden" for="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                                                <select class="form-select form-select-sm mb-2" id="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>" name="ol_subject_name_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $extraAttr; ?>>
                                                    <option value="">Choose subject…</option>
                                                    <?php foreach ($slotSubjects as $subj) : ?>
                                                    <option value="<?php echo htmlspecialchars($subj, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rawName === $subj ? ' selected' : ''; ?>><?php echo htmlspecialchars($subj, ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label class="form-label visually-hidden" for="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks">Result for <?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                                                <select class="form-select form-select-sm" id="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks" name="ol_subject_<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>_marks"<?php echo $extraAttr; ?>>
                                                    <option value="">Choose result…</option>
                                                    <?php foreach ($gradeLetters as $g) : ?>
                                                    <option value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rawRes === $g ? ' selected' : ''; ?>><?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
