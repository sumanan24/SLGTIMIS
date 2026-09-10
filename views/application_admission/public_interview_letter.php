<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$nic = (string) ($nic ?? '');
$lookupAction = (string) ($lookupAction ?? (APP_URL . '/application-admission/interview-letter'));
$formAction = (string) ($formAction ?? (APP_URL . '/application-admission/interview-letter/download'));
$logoUrl = rtrim(APP_URL, '/') . '/assets/img/logo.png';
$result = is_array($result ?? null) ? $result : null;
$prefs = is_array($result['preferences'] ?? null) ? $result['preferences'] : [1 => '', 2 => '', 3 => ''];
$selected = (int) ($result['selected_choice'] ?? 0);
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
<style>
.iv-letter-page { max-width: 40rem; margin: 0 auto 2rem; }
.iv-letter-card {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(12, 74, 110, 0.12);
    overflow: hidden;
}
.iv-letter-accent {
    height: 5px;
    background: linear-gradient(90deg, #0c4a6e 0%, #0369a1 45%, #d97706 100%);
}
.iv-letter-body { padding: 1.5rem 1.5rem 1.75rem; }
.iv-letter-brand { text-align: center; margin-bottom: 1.15rem; }
.iv-letter-brand img { height: 56px; width: auto; display: block; margin: 0 auto 0.65rem; }
.iv-letter-brand .iv-inst {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0c4a6e;
    margin: 0;
}
.iv-letter-brand .iv-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0.45rem 0 0.25rem;
    color: #0f172a;
    letter-spacing: 0.02em;
    line-height: 1.35;
}
.iv-letter-brand .iv-sub { font-size: 0.9rem; color: #64748b; margin: 0; }
.iv-letter-card .form-label { font-weight: 600; font-size: 0.875rem; }
.iv-letter-card .form-control {
    min-height: 2.75rem;
    font-size: 1.05rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.iv-letter-note {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.85rem 0 0;
}
.iv-letter-contact {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0c4a6e;
    text-align: center;
    margin: 1rem 0 0;
}
.iv-result { margin-top: 1.25rem; padding-top: 1.15rem; border-top: 1px solid #e2e8f0; }
.iv-result h2 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
    color: #0c4a6e;
}
.iv-meta-list { margin: 0 0 1rem; font-size: 0.9rem; }
.iv-meta-list dt {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-top: 0.55rem;
}
.iv-meta-list dt:first-child { margin-top: 0; }
.iv-meta-list dd { margin: 0.1rem 0 0; font-weight: 600; color: #0f172a; }
.iv-choice-table { width: 100%; border-collapse: collapse; margin: 0.35rem 0 1rem; }
.iv-choice-table th, .iv-choice-table td {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.7rem;
    font-size: 0.875rem;
    vertical-align: middle;
}
.iv-choice-table th {
    background: #f8fafc;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #475569;
}
.iv-choice-table .iv-ch-on { background: #fff3cd; font-weight: 700; }
.iv-choice-table .iv-flag { text-align: center; font-size: 0.75rem; letter-spacing: 0.03em; text-transform: uppercase; }
.iv-selected-banner {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    border-radius: 0.5rem;
    padding: 0.7rem 0.85rem;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.iv-selected-banner strong { display: block; font-size: 1rem; margin-top: 0.15rem; }
</style>

<div class="iv-letter-page">
    <div class="iv-letter-card">
        <div class="iv-letter-accent"></div>
        <div class="iv-letter-body">
            <div class="iv-letter-brand">
                <img src="<?php echo $e($logoUrl); ?>" alt="SLGTI" onerror="this.style.display='none'">
                <p class="iv-inst">Sri Lanka German Training Institute</p>
                <h1 class="iv-title">INVITATION FOR THE SELECTION INTERVIEW – 2026 INTAKE</h1>
                <p class="iv-sub">Enter your NIC to see your applied and selected courses, then download the letter.</p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo $e($lookupAction); ?>">
                <div class="mb-3">
                    <label class="form-label" for="student_nic_letter">NIC number</label>
                    <input type="text"
                           class="form-control"
                           id="student_nic_letter"
                           name="nic"
                           required
                           autocomplete="off"
                           maxlength="20"
                           placeholder="e.g. 200312345678 or 991234567V"
                           value="<?php echo $e($nic); ?>">
                </div>
                <button type="submit" class="btn btn-outline-primary w-100 py-2">
                    <i class="fas fa-search me-2"></i>View Eligibility
                </button>
            </form>
            <p class="iv-letter-note">Use the NIC exactly as on your application. Details are available after your interview schedule is published.</p>
            <p class="iv-letter-contact">Contact Student Affairs Office 0703060138 / 021 492 7799</p>

            <?php if ($result !== null): ?>
            <div class="iv-result">
                <h2>Your interview details</h2>
                <dl class="iv-meta-list">
                    <dt>Applicant</dt>
                    <dd><?php echo $e($result['name'] !== '' ? $result['name'] : '—'); ?></dd>
                    <dt>NIC</dt>
                    <dd><?php echo $e($result['nic'] !== '' ? $result['nic'] : $nic); ?></dd>
                    <?php if (trim((string) ($result['level'] ?? '')) !== ''): ?>
                    <dt>NVQ level</dt>
                    <dd><?php echo $e($result['level']); ?></dd>
                    <?php endif; ?>
                </dl>

                <div class="iv-selected-banner">
                    Selected course for interview
                    <?php if (!empty($result['selected_label'])): ?>
                    (<?php echo $e($result['selected_label']); ?>)
                    <?php endif; ?>
                    <strong><?php echo $e($result['selected_course'] !== '' ? $result['selected_course'] : '—'); ?></strong>
                </div>

                <h2>Courses you applied for</h2>
                <table class="iv-choice-table">
                    <thead>
                        <tr>
                            <th>Choice</th>
                            <th>Applied course</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ([1, 2, 3] as $n):
                        $prefName = trim((string) ($prefs[$n] ?? ''));
                        $on = $selected === $n && $prefName !== '';
                        if ($on) {
                            $flag = 'Selected';
                        } elseif ($prefName !== '' && !$allChoicesSameCourse) {
                            $flag = 'Not selected';
                        } else {
                            $flag = '—';
                        }
                    ?>
                        <tr>
                            <td class="<?php echo $on ? 'iv-ch-on' : ''; ?>"><?php echo $e($ordLabels[$n]); ?></td>
                            <td class="<?php echo $on ? 'iv-ch-on' : ''; ?>"><?php echo $prefName !== '' ? $e($prefName) : '—'; ?></td>
                            <td class="iv-flag<?php echo $on ? ' iv-ch-on' : ''; ?>"><?php echo $e($flag); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <dl class="iv-meta-list">
                    <dt>Interview date</dt>
                    <dd><?php echo $e($result['interview_date'] !== '' ? $result['interview_date'] : '—'); ?></dd>
                    <dt>Interview time</dt>
                    <dd><?php echo $e($result['interview_time'] !== '' ? $result['interview_time'] : '—'); ?></dd>
                    <dt>Venue</dt>
                    <dd><?php echo $e($result['venue'] !== '' ? $result['venue'] : '—'); ?></dd>
                </dl>

                <form method="post" action="<?php echo $e($formAction); ?>">
                    <input type="hidden" name="nic" value="<?php echo $e($nic); ?>">
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-file-pdf me-2"></i>Download interview letter (PDF)
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
