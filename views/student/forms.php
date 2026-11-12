<?php
declare(strict_types=1);
/** @var array $forms */
$forms = is_array($forms ?? null) ? $forms : [];
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<style>
.sf-page {
    --sf-navy: #001f3f;
    --sf-blue: #003366;
    --sf-line: #e6eaf0;
    --sf-muted: #64748b;
    --sf-ink: #0f172a;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}
.sf-page *,
.sf-page *::before,
.sf-page *::after { box-sizing: border-box; }

.sf-hero {
    position: relative;
    overflow: hidden;
    color: #fff;
    border-radius: 16px;
    background: linear-gradient(120deg, var(--sf-navy) 0%, var(--sf-blue) 100%);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1rem;
    box-shadow: 0 6px 18px rgba(0, 31, 63, .18);
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: .85rem 1rem;
    align-items: center;
}
.sf-hero::after {
    content: "";
    position: absolute;
    right: -48px;
    top: -64px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .06);
    pointer-events: none;
}
.sf-hero-copy { min-width: 0; position: relative; z-index: 1; }
.sf-hero-kicker {
    margin: 0 0 .2rem;
    font-size: .75rem;
    opacity: .8;
    letter-spacing: .02em;
}
.sf-hero h1 {
    margin: 0;
    font-size: clamp(1.15rem, 2.6vw, 1.45rem);
    font-weight: 700;
    line-height: 1.25;
    overflow-wrap: anywhere;
}
.sf-hero p {
    margin: .4rem 0 0;
    font-size: .88rem;
    opacity: .9;
    line-height: 1.45;
    max-width: 42rem;
}
.sf-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    justify-content: flex-end;
}
.sf-hero-actions .btn {
    white-space: nowrap;
    border-color: rgba(255, 255, 255, .45);
    color: #fff;
    background: rgba(255, 255, 255, .1);
}
.sf-hero-actions .btn:hover,
.sf-hero-actions .btn:focus {
    background: #fff;
    color: var(--sf-navy);
    border-color: #fff;
}

.sf-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
.sf-step {
    background: #fff;
    border: 1px solid var(--sf-line);
    border-radius: 12px;
    padding: .75rem .85rem;
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    min-width: 0;
}
.sf-step b {
    flex: 0 0 1.6rem;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 50%;
    background: var(--sf-navy);
    color: #fff;
    font-size: .75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: .05rem;
}
.sf-step span {
    min-width: 0;
    font-size: .84rem;
    color: #334155;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.sf-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
}
.sf-card {
    background: #fff;
    border: 1px solid var(--sf-line);
    border-radius: 14px;
    padding: 1rem 1.05rem;
    display: flex;
    flex-direction: column;
    gap: .85rem;
    min-width: 0;
    height: 100%;
    box-shadow: 0 1px 4px rgba(15, 23, 42, .05);
}
.sf-card-top {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    min-width: 0;
}
.sf-icon {
    flex: 0 0 2.75rem;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 31, 63, .08);
    color: var(--sf-navy);
    font-size: 1.1rem;
}
.sf-body { flex: 1 1 auto; min-width: 0; }
.sf-title {
    margin: 0 0 .25rem;
    font-size: 1rem;
    font-weight: 700;
    color: var(--sf-ink);
    line-height: 1.3;
    overflow-wrap: anywhere;
}
.sf-desc {
    margin: 0;
    font-size: .86rem;
    color: var(--sf-muted);
    line-height: 1.45;
    overflow-wrap: anywhere;
}
.sf-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .55rem;
}
.sf-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    max-width: 100%;
    font-size: .72rem;
    color: #475569;
    background: #f1f5f9;
    border-radius: 999px;
    padding: .2rem .6rem;
    line-height: 1.3;
    overflow-wrap: anywhere;
}
.sf-chip i { flex-shrink: 0; opacity: .85; }

.sf-action {
    margin-top: auto;
    padding-top: .15rem;
}
.sf-action .btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    min-height: 2.5rem;
    font-weight: 600;
    background: var(--sf-navy);
    border-color: var(--sf-navy);
}
.sf-action .btn:hover,
.sf-action .btn:focus {
    background: var(--sf-blue);
    border-color: var(--sf-blue);
}

.sf-empty {
    grid-column: 1 / -1;
    background: #fff;
    border: 1px dashed var(--sf-line);
    border-radius: 14px;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--sf-muted);
}

@media (max-width: 991.98px) {
    .sf-grid { grid-template-columns: 1fr; }
}

@media (max-width: 767.98px) {
    .sf-hero {
        grid-template-columns: 1fr;
        padding: 1.1rem 1rem;
        border-radius: 14px;
    }
    .sf-hero-actions {
        justify-content: stretch;
    }
    .sf-hero-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .sf-steps {
        grid-template-columns: 1fr;
        gap: .5rem;
    }
}

@media (max-width: 575.98px) {
    .sf-hero h1 { font-size: 1.15rem; }
    .sf-hero p { font-size: .82rem; }
    .sf-card { padding: .9rem; border-radius: 12px; }
    .sf-icon {
        flex-basis: 2.4rem;
        width: 2.4rem;
        height: 2.4rem;
        font-size: 1rem;
        border-radius: 10px;
    }
    .sf-title { font-size: .95rem; }
    .sf-desc { font-size: .82rem; }
    .sf-action .btn {
        min-height: 2.65rem;
        font-size: .9rem;
    }
}

@media (min-width: 1200px) {
    .sf-page { max-width: 1080px; margin-left: auto; margin-right: auto; }
}
</style>

<div class="sf-page">
    <header class="sf-hero">
        <div class="sf-hero-copy">
            <p class="sf-hero-kicker">Sri Lanka–German Training Institute</p>
            <h1><i class="fas fa-file-alt me-2" aria-hidden="true"></i>Student Forms</h1>
            <p>Download official SLGTI forms pre-filled with your student profile. Print, sign, and submit to the office listed on each form.</p>
        </div>
        <div class="sf-hero-actions">
            <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-sm">
                <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Dashboard
            </a>
        </div>
    </header>

    <div class="sf-steps" aria-label="How to use these forms">
        <div class="sf-step"><b>1</b><span>Download the PDF for the form you need</span></div>
        <div class="sf-step"><b>2</b><span>Print the form and sign where required</span></div>
        <div class="sf-step"><b>3</b><span>Submit it to the office shown on the card</span></div>
    </div>

    <div class="sf-grid">
        <?php if ($forms === []): ?>
            <div class="sf-empty">No forms are available right now.</div>
        <?php else: ?>
            <?php foreach ($forms as $form): ?>
                <article class="sf-card">
                    <div class="sf-card-top">
                        <div class="sf-icon" aria-hidden="true">
                            <i class="fas <?php echo $e($form['icon'] ?? 'fa-file-pdf'); ?>"></i>
                        </div>
                        <div class="sf-body">
                            <h2 class="sf-title"><?php echo $e($form['title'] ?? ''); ?></h2>
                            <p class="sf-desc"><?php echo $e($form['description'] ?? ''); ?></p>
                            <div class="sf-meta">
                                <?php if (!empty($form['pages'])): ?>
                                    <span class="sf-chip"><i class="fas fa-file-pdf" aria-hidden="true"></i>A4 · <?php echo $e($form['pages']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($form['submit_to'])): ?>
                                    <span class="sf-chip"><i class="fas fa-paper-plane" aria-hidden="true"></i>Submit to: <?php echo $e($form['submit_to']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="sf-action">
                        <a class="btn btn-primary" href="<?php echo $e($form['url'] ?? '#'); ?>">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <?php echo $e($form['action_label'] ?? 'Download PDF'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
