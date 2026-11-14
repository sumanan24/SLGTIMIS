<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$videos = is_array($videos ?? null) ? $videos : [];
$sinhala = $videos['sinhala'] ?? null;
$tamil = $videos['tamil'] ?? null;
$sinhalaUrl = (string) ($sinhala_url ?? (rtrim(APP_URL, '/') . '/guidelines/sinhala'));
$tamilUrl = (string) ($tamil_url ?? (rtrim(APP_URL, '/') . '/guidelines/tamil'));
?>
<style>
.guide-wrap {
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
    padding: .25rem 0 1rem;
    box-sizing: border-box;
}
.guide-hero {
    text-align: center;
    padding: clamp(1rem, 3vw, 1.4rem) clamp(.85rem, 3vw, 1.1rem);
    background: linear-gradient(160deg, #001f3f 0%, #0a3d6b 55%, #0d4f86 100%);
    color: #fff;
    border-radius: .9rem;
    margin-bottom: 1rem;
    box-shadow: 0 10px 28px rgba(0, 31, 63, .28);
}
.guide-hero img {
    height: clamp(44px, 10vw, 52px);
    width: auto;
    background: #fff;
    border-radius: .5rem;
    padding: .25rem .4rem;
    margin-bottom: .65rem;
}
.guide-hero h1 {
    font-size: clamp(1.1rem, 4vw, 1.35rem);
    font-weight: 700;
    margin: 0 0 .35rem;
    letter-spacing: .01em;
    line-height: 1.3;
}
.guide-hero p {
    margin: 0 auto;
    opacity: .92;
    font-size: clamp(.8rem, 2.6vw, .9rem);
    line-height: 1.45;
    max-width: 28rem;
}
.guide-grid {
    display: grid;
    gap: .85rem;
    grid-template-columns: 1fr;
}
@media (min-width: 576px) {
    .guide-grid { grid-template-columns: 1fr 1fr; }
}
.guide-card {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: .65rem;
    background: #fff;
    border: 1px solid #d7e2ef;
    border-radius: .9rem;
    padding: 1.1rem .95rem 1rem;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 14px rgba(16, 32, 51, .06);
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    min-height: 100%;
}
.guide-card:hover {
    transform: translateY(-2px);
    border-color: #9bb8d4;
    box-shadow: 0 10px 24px rgba(0, 31, 63, .12);
    color: inherit;
}
.guide-card .guide-lang {
    display: flex;
    align-items: center;
    gap: .65rem;
}
.guide-card .guide-ico {
    flex: 0 0 auto;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: .7rem;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.05rem;
    background: #001f3f;
}
.guide-card.tamil .guide-ico { background: #0d4f86; }
.guide-card h2 {
    margin: 0;
    font-size: clamp(1.05rem, 3.5vw, 1.15rem);
    font-weight: 700;
    color: #001f3f;
}
.guide-card .guide-sub {
    margin: 0;
    font-size: .82rem;
    color: #5b6b7c;
}
.guide-card .guide-cta {
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    background: #001f3f;
    color: #fff;
    border-radius: .55rem;
    padding: .55rem .75rem;
    font-weight: 600;
    font-size: .88rem;
    text-align: center;
}
.guide-card.tamil .guide-cta { background: #0d4f86; }
.guide-card .guide-miss {
    margin-top: auto;
    font-size: .8rem;
    color: #b45309;
    background: #fff7ed;
    border-radius: .45rem;
    padding: .4rem .55rem;
    text-align: center;
}
.guide-note {
    margin-top: 1rem;
    text-align: center;
    font-size: .8rem;
    color: #64748b;
}
@media (max-width: 575.98px) {
    .guide-hero { border-radius: .7rem; }
    .guide-card { border-radius: .75rem; }
}
</style>

<div class="guide-wrap">
    <div class="guide-hero">
        <img src="<?php echo $e(rtrim(APP_URL, '/') . '/assets/img/logo.png'); ?>" alt="SLGTI">
        <h1>Registration guideline videos</h1>
        <p>භාෂාව තෝරන්න · உங்கள் மொழியைத் தேர்ந்தெடுக்கவும்</p>
    </div>

    <div class="guide-grid">
        <a class="guide-card" href="<?php echo $e($sinhalaUrl); ?>">
            <div class="guide-lang">
                <span class="guide-ico" aria-hidden="true"><i class="fas fa-play"></i></span>
                <div>
                    <h2>සිංහල</h2>
                    <p class="guide-sub">Sinhala guideline video</p>
                </div>
            </div>
            <?php if (!empty($sinhala['exists'])): ?>
                <span class="guide-cta"><i class="fas fa-video"></i> Watch Sinhala video</span>
            <?php else: ?>
                <span class="guide-miss">Video file not found yet</span>
            <?php endif; ?>
        </a>

        <a class="guide-card tamil" href="<?php echo $e($tamilUrl); ?>">
            <div class="guide-lang">
                <span class="guide-ico" aria-hidden="true"><i class="fas fa-play"></i></span>
                <div>
                    <h2>தமிழ்</h2>
                    <p class="guide-sub">Tamil guideline video</p>
                </div>
            </div>
            <?php if (!empty($tamil['exists'])): ?>
                <span class="guide-cta"><i class="fas fa-video"></i> Watch Tamil video</span>
            <?php else: ?>
                <span class="guide-miss">Video file not found yet</span>
            <?php endif; ?>
        </a>
    </div>

    <p class="guide-note">Sri Lanka German Training Institute (SLGTI)</p>
</div>
