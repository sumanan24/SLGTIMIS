<?php
/**
 * Pagination for staff applications list (full navigation or AJAX NIC filter mode).
 *
 * @param 'new'|'approved'|'rejected' $pagTab
 * @param callable(string,string,int,int,int): string $buildListUrl ($level, $tab, $pn, $pa, $pr)
 * @param callable(string): string $esc
 */
function sa_admin_student_applications_pagination(
    string $pagTab,
    int $page,
    int $maxPage,
    bool $ajaxMode,
    ?string $filter_level,
    callable $buildListUrl,
    callable $esc
): void {
    $pagTab = in_array($pagTab, ['approved', 'rejected'], true) ? $pagTab : 'new';
    $page = max(1, $page);
    $maxPage = max(1, $maxPage);
    $aria = $pagTab === 'new' ? 'New applications pages' : ($pagTab === 'approved' ? 'Approved applications pages' : 'Rejected applications pages');
    $window = 2;
    $start = max(1, $page - $window);
    $end = min($maxPage, $page + $window);

    $linkPrev = $pagTab === 'new'
        ? $buildListUrl($filter_level, 'new', max(1, $page - 1), 1, 1)
        : ($pagTab === 'approved'
            ? $buildListUrl($filter_level, 'approved', 1, max(1, $page - 1), 1)
            : $buildListUrl($filter_level, 'rejected', 1, 1, max(1, $page - 1)));
    $linkNext = $pagTab === 'new'
        ? $buildListUrl($filter_level, 'new', min($maxPage, $page + 1), 1, 1)
        : ($pagTab === 'approved'
            ? $buildListUrl($filter_level, 'approved', 1, min($maxPage, $page + 1), 1)
            : $buildListUrl($filter_level, 'rejected', 1, 1, min($maxPage, $page + 1)));

    $ajaxBtn = static function (string $label, int $pn, bool $disabled, string $tab, callable $esc): void {
        $tabEsc = $esc($tab);
        $pnAttr = (int) $pn;
        if ($disabled) {
            echo '<li class="page-item disabled"><span class="page-link">' . $esc($label) . '</span></li>';
            return;
        }
        echo '<li class="page-item"><button type="button" class="page-link sa-nic-ajax-pag text-decoration-none border-0 bg-transparent w-100 py-2 px-3"'
            . ' data-sa-tab="' . $tabEsc . '" data-sa-pn="' . $pnAttr . '">' . $esc($label) . '</button></li>';
    };
    ?>
    <nav class="sa-apps-pagination pt-3 px-3 border-top mt-3" aria-label="<?php echo $esc($aria); ?>">
        <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
            <?php if ($ajaxMode): ?>
                <?php $ajaxBtn('Previous', max(1, $page - 1), $page <= 1, $pagTab, $esc); ?>
            <?php else: ?>
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $esc($linkPrev); ?>">Previous</a>
            </li>
            <?php endif; ?>
            <?php if ($start > 1): ?>
                <?php if ($ajaxMode): ?>
                    <?php $ajaxBtn('1', 1, false, $pagTab, $esc); ?>
                <?php else: ?>
            <li class="page-item"><a class="page-link" href="<?php echo $esc($pagTab === 'new' ? $buildListUrl($filter_level, 'new', 1, 1, 1) : ($pagTab === 'approved' ? $buildListUrl($filter_level, 'approved', 1, 1, 1) : $buildListUrl($filter_level, 'rejected', 1, 1, 1))); ?>">1</a></li>
                <?php endif; ?>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item<?php echo $p === $page ? ' active' : ''; ?>">
                <?php if ($ajaxMode): ?>
                    <?php if ($p === $page): ?>
                <span class="page-link"><?php echo (int) $p; ?></span>
                    <?php else: ?>
                <button type="button" class="page-link sa-nic-ajax-pag border-0 bg-transparent" data-sa-tab="<?php echo $esc($pagTab); ?>" data-sa-pn="<?php echo (int) $p; ?>"><?php echo (int) $p; ?></button>
                    <?php endif; ?>
                <?php else: ?>
                <a class="page-link" href="<?php echo $esc($pagTab === 'new' ? $buildListUrl($filter_level, 'new', $p, 1, 1) : ($pagTab === 'approved' ? $buildListUrl($filter_level, 'approved', 1, $p, 1) : $buildListUrl($filter_level, 'rejected', 1, 1, $p))); ?>"><?php echo (int) $p; ?></a>
                <?php endif; ?>
            </li>
            <?php endfor; ?>
            <?php if ($end < $maxPage): ?>
            <?php if ($end < $maxPage - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php if ($ajaxMode): ?>
                    <?php $ajaxBtn((string) (int) $maxPage, $maxPage, false, $pagTab, $esc); ?>
                <?php else: ?>
            <li class="page-item"><a class="page-link" href="<?php echo $esc($pagTab === 'new' ? $buildListUrl($filter_level, 'new', $maxPage, 1, 1) : ($pagTab === 'approved' ? $buildListUrl($filter_level, 'approved', 1, $maxPage, 1) : $buildListUrl($filter_level, 'rejected', 1, 1, $maxPage))); ?>"><?php echo (int) $maxPage; ?></a></li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($ajaxMode): ?>
                <?php $ajaxBtn('Next', min($maxPage, $page + 1), $page >= $maxPage, $pagTab, $esc); ?>
            <?php else: ?>
            <li class="page-item<?php echo $page >= $maxPage ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $esc($linkNext); ?>">Next</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
}
