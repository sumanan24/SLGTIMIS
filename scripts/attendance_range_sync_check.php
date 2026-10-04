<?php
/**
 * Logic checks for selected-date attendance sync.
 * Does not connect to readers and does not write attendance rows.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/MachineAttendanceService.php';

date_default_timezone_set('UTC');

$failed = 0;
$check = static function (bool $ok, string $label) use (&$failed): void {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
};

$bounds = MachineAttendanceService::inclusiveRangeToExclusive('2026-09-28', '2026-10-04', 'Asia/Colombo');
$check($bounds['start']->format('Y-m-d H:i:s') === '2026-09-28 00:00:00', 'start is 2026-09-28 00:00:00');
$check($bounds['end_exclusive']->format('Y-m-d H:i:s') === '2026-10-05 00:00:00', 'end exclusive is 2026-10-05 00:00:00');
$check($bounds['start']->getTimezone()->getName() === 'Asia/Colombo', 'window timezone is Asia/Colombo');

$evening = '2026-10-04 19:30:00';
$morning = '2026-10-04 08:15:00';
$startS = $bounds['start']->format('Y-m-d H:i:s');
$endS = $bounds['end_exclusive']->format('Y-m-d H:i:s');
$check($evening >= $startS && $evening < $endS, 'evening punch on the last day is inside the window');
$check($morning >= $startS && $morning < $endS, 'morning punch on the last day is inside the window');
$check(!('2026-10-05 00:00:00' < $endS), 'midnight after the end date is excluded');

$svc = new MachineAttendanceService([
    'host' => '172.16.0.29',
    'username' => 'admin',
    'password' => 'unused',
    'timezone' => 'Asia/Colombo',
]);
$parse = new ReflectionMethod(MachineAttendanceService::class, 'parseDeviceTime');
$parse->setAccessible(true);
$parsedEvening = $parse->invoke($svc, '2026-10-04T19:30:00');
$parsedMorning = $parse->invoke($svc, '2026-10-04T08:15:00');
$check($parsedEvening instanceof DateTimeImmutable && $parsedEvening->format('Y-m-d H:i:s') === '2026-10-04 19:30:00', 'naive evening time stays 19:30 Asia/Colombo');
$check($parsedMorning instanceof DateTimeImmutable && $parsedMorning->format('Y-m-d H:i:s') === '2026-10-04 08:15:00', 'naive morning time stays 08:15 Asia/Colombo');

$check(MachineAttendanceService::acsSearchShouldContinue(30, 30, 1200, 'OK') === true, 'short page continues when totalMatches is larger');
$check(MachineAttendanceService::acsSearchShouldContinue(30, 1200, 1200, 'OK') === false, 'paging stops when position reaches totalMatches');
$check(MachineAttendanceService::acsSearchShouldContinue(30, 30, null, '') === true, 'short page continues when the device omits totalMatches');
$check(MachineAttendanceService::acsSearchShouldContinue(0, 30, 1200, 'MORE') === false, 'an empty page stops paging');

echo $failed === 0 ? "ALL CHECKS PASSED\n" : ($failed . " CHECK(S) FAILED\n");
exit($failed === 0 ? 0 : 1);
