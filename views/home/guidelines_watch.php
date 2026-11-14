<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$lang = (string) ($lang ?? '');
$video = is_array($video ?? null) ? $video : null;
$hubUrl = (string) ($hub_url ?? (rtrim(APP_URL, '/') . '/guidelines'));
$otherUrl = (string) ($other_url ?? $hubUrl);
$otherLang = (string) ($other_lang ?? '');
$isSi = $lang === 'sinhala';
$titleLocal = $video['title'] ?? ($isSi ? 'සිංහල මාර්ගෝපදේශ වීඩියෝව' : 'தமிழ் வழிகாட்டி வீடியோ');
$titleEn = $video['title_en'] ?? ($isSi ? 'Sinhala guideline video' : 'Tamil guideline video');
?>
<style>
.gw-wrap {
    width: 100%;
    max-width: 960px;
    margin: 0 auto;
    padding: .25rem 0 1rem;
    box-sizing: border-box;
}
.gw-bar {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .75rem;
}
.gw-bar a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    text-decoration: none;
    font-size: .85rem;
    font-weight: 600;
    color: #001f3f;
    background: #fff;
    border: 1px solid #cfd9e6;
    border-radius: .5rem;
    padding: .45rem .75rem;
    min-height: 2.4rem;
}
.gw-bar a:hover { background: #f1f5f9; color: #001f3f; }
.gw-head {
    text-align: center;
    margin: 0 auto .9rem;
    max-width: 36rem;
}
.gw-head h1 {
    margin: 0 0 .25rem;
    font-size: clamp(1.05rem, 3.6vw, 1.35rem);
    color: #001f3f;
    font-weight: 700;
    line-height: 1.3;
    word-break: break-word;
}
.gw-head p {
    margin: 0;
    color: #5b6b7c;
    font-size: clamp(.8rem, 2.5vw, .92rem);
}
.gw-player {
    width: 100%;
    background: #0b1220;
    border-radius: .75rem;
    overflow: hidden;
    box-shadow: 0 10px 28px rgba(0, 31, 63, .22);
    aspect-ratio: 16 / 9;
    max-height: min(70vh, 540px);
}
.gw-player video {
    width: 100%;
    height: 100%;
    display: block;
    background: #000;
    object-fit: contain;
}
.gw-empty {
    background: #fff7ed;
    border: 1px solid #fdba74;
    color: #9a3412;
    border-radius: .85rem;
    padding: 1.15rem;
    text-align: center;
}
@media (max-width: 575.98px) {
    .gw-bar { justify-content: stretch; }
    .gw-bar a { flex: 1 1 calc(50% - .25rem); font-size: .8rem; padding: .4rem .5rem; }
    .gw-player { border-radius: .55rem; max-height: none; }
}
@media (min-width: 992px) {
    .gw-wrap { padding-top: .5rem; }
}
</style>

<div class="gw-wrap">
    <div class="gw-bar">
        <a href="<?php echo $e($hubUrl); ?>"><i class="fas fa-arrow-left"></i> All languages</a>
        <?php if ($otherUrl !== ''): ?>
            <a href="<?php echo $e($otherUrl); ?>">
                <i class="fas fa-language"></i>
                <?php echo $otherLang === 'tamil' ? 'Tamil / தமிழ்' : 'Sinhala / සිංහල'; ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="gw-head">
        <h1><?php echo $e($titleLocal); ?></h1>
        <p><?php echo $e($titleEn); ?> · SLGTI</p>
    </div>

    <?php if ($video && !empty($video['url'])): ?>
        <div class="gw-player">
            <video controls playsinline preload="metadata" controlslist="nodownload">
                <source src="<?php echo $e($video['url']); ?>" type="video/mp4">
                Your browser does not support video playback.
            </video>
        </div>
    <?php else: ?>
        <div class="gw-empty">
            <p class="mb-1 fw-semibold">Video not available yet.</p>
            <p class="mb-0 small">Place the file as <code>assets/video/<?php echo $e($lang); ?>.mp4</code> on the server.</p>
        </div>
    <?php endif; ?>
</div>
