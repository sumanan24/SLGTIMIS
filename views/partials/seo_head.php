<?php
/**
 * SEO meta tags (head word: SLGTI)
 *
 * Optional view data: $title, $seo_title, $seo_description, $seo_keywords,
 * $seo_robots, $seo_canonical, $seo_og_type, $page
 */
require_once BASE_PATH . '/core/SeoHelper.php';

$seoCfg = SeoHelper::config();
$seoHw = $seoCfg['head_word'];

$pageTitleRaw = $seo_title ?? ($title ?? '');
$documentTitle = SeoHelper::title($pageTitleRaw);

$seoDescription = $seo_description ?? $seoCfg['default_description'];
$seoKeywords = $seo_keywords ?? $seoCfg['default_keywords'];

if (!isset($seo_robots)) {
    if (!empty($_SESSION['user_id'])) {
        $seo_robots = 'noindex, nofollow';
    } elseif (isset($page) && $page === 'login') {
        $seo_robots = 'noindex, nofollow';
    } else {
        $seo_robots = 'index, follow';
    }
}

$seoCanonical = $seo_canonical ?? SeoHelper::canonical();
$seoOgType = $seo_og_type ?? 'website';
$seoOgImage = SeoHelper::ogImageUrl();
$seoSiteName = $seoCfg['site_short'];
$seoLocale = $seoCfg['locale'] ?? 'en_LK';
$seoTwitterCard = $seoCfg['twitter_card'] ?? 'summary';

$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<title><?php echo $esc($documentTitle); ?></title>
<meta name="description" content="<?php echo $esc($seoDescription); ?>">
<meta name="keywords" content="<?php echo $esc($seoKeywords); ?>">
<meta name="robots" content="<?php echo $esc($seo_robots); ?>">
<meta name="author" content="<?php echo $esc($seoHw . ' — ' . $seoCfg['brand_name']); ?>">
<link rel="canonical" href="<?php echo $esc($seoCanonical); ?>">

<meta property="og:locale" content="<?php echo $esc($seoLocale); ?>">
<meta property="og:type" content="<?php echo $esc($seoOgType); ?>">
<meta property="og:site_name" content="<?php echo $esc($seoSiteName); ?>">
<meta property="og:title" content="<?php echo $esc($documentTitle); ?>">
<meta property="og:description" content="<?php echo $esc($seoDescription); ?>">
<meta property="og:url" content="<?php echo $esc($seoCanonical); ?>">
<meta property="og:image" content="<?php echo $esc($seoOgImage); ?>">

<meta name="twitter:card" content="<?php echo $esc($seoTwitterCard); ?>">
<meta name="twitter:title" content="<?php echo $esc($documentTitle); ?>">
<meta name="twitter:description" content="<?php echo $esc($seoDescription); ?>">
<meta name="twitter:image" content="<?php echo $esc($seoOgImage); ?>">
