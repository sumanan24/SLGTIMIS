<?php
/**
 * Student envelope PDF (one DL envelope, 220 x 110 mm, per page).
 *
 * @var list<array{name:string,lines:list<string>}> $envelopes
 * @var string $headerImage Absolute path of the header artwork
 * @var callable $e HTML escaper
 */
$envelopes = $envelopes ?? [];
$headerImage = $headerImage ?? '';
$e = $e ?? static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$total = count($envelopes);
?>
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; color: #000; }
    .env { position: relative; width: 220mm; height: 109mm; overflow: hidden; }
    .env.brk { page-break-after: always; }
    .env .hdr { position: absolute; left: 0; top: 0; width: 220mm; height: 25mm; }
    .env .from { position: absolute; left: 6mm; top: 50mm; width: 100mm; font-size: 9.5pt; font-weight: bold; line-height: 1.4; }
    .env .to { position: absolute; left: 118mm; top: 48mm; width: 95mm; font-size: 11pt; line-height: 1.4; }
    .env .to .lbl { font-size: 10pt; font-weight: bold; margin-bottom: 1mm; }
    .env .to .nm { font-size: 13pt; font-weight: bold; }
    .env .foot { position: absolute; left: 0; top: 92mm; width: 220mm; text-align: center; font-size: 9pt; font-weight: bold; }
</style>
<?php foreach ($envelopes as $i => $env): ?>
<div class="env<?php echo $i < $total - 1 ? ' brk' : ''; ?>">
    <?php if ($headerImage !== ''): ?>
        <img class="hdr" src="<?php echo $e($headerImage); ?>" alt="">
    <?php endif; ?>

    <div class="to">
        <div class="lbl">To:</div>
        <div class="nm"><?php echo $e($env['name']); ?></div>
        <?php foreach ($env['lines'] as $line): ?>
            <div><?php echo $e($line); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="from">
        From:<br>
        Sri Lanka German Training Institute<br>
        Ariviyal Nagar,<br>
        Kilinochchi 44000,<br>
        Sri Lanka.
    </div>

    <div class="foot">Tel: +94 21 492 7799 | Mobile: +94 70 306 0138 | Website: www.slgti.ac.lk | Email: info@slgti.ac.lk</div>
</div>
<?php endforeach; ?>
