<?php
/**
 * Staff online applications — table markup (admin list + AJAX refresh).
 *
 * @param list<array<string, mixed>> $rows
 * @param callable(string):string $esc
 * @param callable(int):string $viewUrl
 * @param callable(?string):array{order:string,display:string} $formatSubmitted
 */
function sa_admin_student_applications_render_table(
    array $rows,
    callable $esc,
    callable $viewUrl,
    string $deleteAction,
    bool $can_delete,
    callable $formatSubmitted,
    string $statusLabel,
    string $badgeClass,
    int $rowNumBase = 0
): void {
    ?>
    <div class="table-responsive sa-apps-table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle w-100 mb-0 sa-apps-table">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="text-end sa-apps-col-num">#</th>
                    <th scope="col">Level</th>
                    <th scope="col">Status</th>
                    <th scope="col">Full name</th>
                    <th scope="col">NIC</th>
                    <th scope="col">District</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Date sent</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="10" class="sa-apps-empty text-secondary text-center py-5 px-3">No <?php echo $esc($statusLabel); ?> applications<?php
                        if ($statusLabel === 'new') {
                            echo ' to review';
                        } elseif ($statusLabel === 'rejected') {
                            echo ' for this view';
                        }
                    ?>.</td>
                </tr>
                <?php else: ?>
                    <?php
                    $rowIx = 0;
                    foreach ($rows as $r):
                        $rowIx++;
                        $id = (int) ($r['application_id'] ?? 0);
                        $seq = $rowNumBase + $rowIx;
                        $submitted = $formatSubmitted(isset($r['created_at']) ? (string) $r['created_at'] : null);
                        $waDigits = StudentModel::digitsForWhatsAppMe($r);
                        ?>
                <tr>
                    <td class="text-muted text-end sa-apps-col-num"><?php echo (int) $seq; ?></td>
                    <td><?php echo $esc((string) ($r['application_level'] ?? '')); ?></td>
                    <td><span class="badge rounded-pill px-2 <?php echo $esc($badgeClass); ?>"><?php echo $esc(ucfirst($statusLabel)); ?></span></td>
                    <td><?php echo $esc((string) ($r['student_full_name'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_nic'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_district'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_email'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_phone'] ?? '')); ?></td>
                    <td data-order="<?php echo $submitted['order']; ?>"><?php echo $submitted['display']; ?></td>
                    <td>
                        <?php if ($can_delete): ?>
                        <form id="sa-app-del-<?php echo $id; ?>" method="post" action="<?php echo $deleteAction; ?>" class="d-none"
                              onsubmit="return confirm('Delete application #<?php echo $id; ?>? This will also remove uploaded documents on the server.');">
                            <input type="hidden" name="application_id" value="<?php echo $id; ?>">
                        </form>
                        <?php endif; ?>
                        <div class="btn-group btn-group-sm sa-apps-table-actions" role="group" aria-label="Application #<?php echo $id; ?> actions">
                            <a class="btn btn-outline-primary" href="<?php echo $viewUrl($id); ?>"
                               title="View application">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="visually-hidden"> View</span>
                            </a>
                            <?php if ($waDigits !== null): ?>
                            <a class="btn btn-wa-outline" href="<?php echo $esc('https://wa.me/' . $waDigits); ?>"
                               target="_blank" rel="noopener noreferrer"
                               title="WhatsApp <?php echo $esc($waDigits); ?>">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                <span class="visually-hidden"> WhatsApp</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                            <button type="submit" form="sa-app-del-<?php echo $id; ?>" class="btn btn-outline-danger" title="Delete application">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                <span class="visually-hidden"> Delete</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
