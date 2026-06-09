<?php
/**
 * Database-driven staff sidebar (requires $navMenuTree, $navMenuSvc, $page)
 */
if (empty($navMenuTree) || empty($navMenuSvc)) {
    return;
}

$renderNav = null;
$renderNav = function (array $items) use (&$renderNav, $navMenuSvc, $page) {
    foreach ($items as $item) {
        if (!empty($item['is_divider'])) {
            echo '<li class="menu-divider"></li>';
            continue;
        }

        $children = $item['children'] ?? [];
        $hasChildren = $children !== [];
        $currentPage = (string) ($page ?? '');
        $isActive = $navMenuSvc->isItemActive($item, $currentPage);
        $href = $navMenuSvc->itemHref($item);

        if ($hasChildren) {
            $subStyle = $isActive ? 'display: block;' : '';
            echo '<li class="menu-item-has-children' . ($isActive ? ' active' : '') . '">';
            echo '<a href="#" class="menu-toggle">';
            echo '<i class="' . htmlspecialchars($item['icon_class']) . '"></i>';
            echo '<span>' . htmlspecialchars($item['label']) . '</span>';
            echo '<i class="fas fa-chevron-down menu-arrow"></i></a>';
            echo '<ul class="submenu" style="' . $subStyle . '">';
            $renderNav($children);
            echo '</ul></li>';
            continue;
        }

        echo '<li>';
        $linkClass = ($currentPage !== '' && !empty($item['page_key']) && $item['page_key'] === $currentPage) ? ' active' : '';
        if ($href === '#') {
            echo '<a href="#" class="menu-toggle' . $linkClass . '">';
        } else {
            echo '<a href="' . htmlspecialchars($href) . '" class="' . trim($linkClass) . '">';
        }
        echo '<i class="' . htmlspecialchars($item['icon_class']) . '"></i>';
        echo '<span>' . htmlspecialchars($item['label']) . '</span></a></li>';
    }
};

$renderNav($navMenuTree);

// ADM-only settings link (always at bottom of dynamic menu for ADM)
if (!empty($GLOBALS['nav_show_settings_link'])) {
    $settingsActive = ($page ?? '') === 'admin-nav-menu' ? ' active' : '';
    echo '<li class="menu-divider"></li>';
    echo '<li><a href="' . htmlspecialchars(rtrim(APP_URL, '/') . '/admin/nav-menu') . '" class="' . trim($settingsActive) . '">';
    echo '<i class="fas fa-bars"></i><span>Navbar Settings</span></a></li>';
}
