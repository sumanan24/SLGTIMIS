<?php
/**
 * Sri Lanka nationwide public holidays (gazette-based) for attendance / register exports.
 * Weekends are handled separately; this class marks dates that are public holidays on the calendar.
 *
 * Years 2024–2028 use published gazette-style lists. Any other year still treats
 * Independence (4 Feb), May Day (1 May), and Christmas (25 Dec) as public holidays.
 * Update the GAZETTE array when the government publishes a new annual calendar.
 */
class SriLankaPublicHolidays {
    /** @var array<string,bool> Y-m-d => true */
    private static $gazetteBuilt = null;

    /**
     * @return array<string,bool>
     */
    private static function gazetteMap() {
        if (self::$gazetteBuilt !== null) {
            return self::$gazetteBuilt;
        }
        $list = [
            // 2024 (typical gazette — verify against current Gazette if disputed)
            '2024-01-15', '2024-01-25', '2024-02-04', '2024-02-24', '2024-03-08', '2024-03-29',
            '2024-04-12', '2024-04-13', '2024-05-01', '2024-05-23', '2024-05-24', '2024-06-17',
            '2024-06-21', '2024-07-20', '2024-08-19', '2024-09-16', '2024-09-17', '2024-10-17',
            '2024-10-31', '2024-11-14', '2024-12-13', '2024-12-25',
            // 2025 — Gazette extraordinary July 2024 (public * dates; excludes bank-only special holiday)
            '2025-01-13', '2025-01-14', '2025-02-04', '2025-02-12', '2025-02-26', '2025-03-13', '2025-03-31',
            '2025-04-12', '2025-04-13', '2025-04-14', '2025-04-18', '2025-05-01', '2025-05-12', '2025-05-13',
            '2025-06-07', '2025-06-10', '2025-07-10', '2025-08-08', '2025-09-05', '2025-09-07', '2025-10-06',
            '2025-10-20', '2025-11-05', '2025-12-04', '2025-12-25',
            // 2026 — Government Printing / public holiday tables; Vesak follow-day per revised gazette (May 31, not May 2)
            '2026-01-03', '2026-01-15', '2026-02-04', '2026-03-02', '2026-03-21', '2026-04-01', '2026-04-03',
            '2026-04-13', '2026-04-14', '2026-05-01', '2026-05-28', '2026-05-30', '2026-05-31', '2026-06-29',
            '2026-07-29', '2026-08-26', '2026-08-27', '2026-09-26', '2026-10-25', '2026-11-08', '2026-11-24',
            '2026-12-23', '2026-12-25',
            // 2027 — provisional published tables (confirm when official gazette is released)
            '2027-01-15', '2027-01-22', '2027-02-04', '2027-02-20', '2027-03-06', '2027-03-10', '2027-03-21',
            '2027-03-26', '2027-04-13', '2027-04-14', '2027-04-20', '2027-05-01', '2027-05-17', '2027-05-20',
            '2027-05-21', '2027-06-18', '2027-07-18', '2027-08-15', '2027-08-16', '2027-09-15', '2027-10-15',
            '2027-10-28', '2027-11-13', '2027-12-13', '2027-12-25',
            // 2028 — provisional published tables
            '2028-01-12', '2028-01-14', '2028-02-04', '2028-02-23', '2028-02-25', '2028-02-26', '2028-03-25',
            '2028-04-13', '2028-04-14', '2028-04-24', '2028-05-01', '2028-05-05', '2028-05-23', '2028-05-24',
            '2028-06-22', '2028-07-21', '2028-08-03', '2028-08-20', '2028-09-18', '2028-10-17', '2028-10-18',
            '2028-11-16', '2028-12-15', '2028-12-25',
        ];
        $map = [];
        foreach ($list as $d) {
            $map[$d] = true;
        }
        self::$gazetteBuilt = $map;
        return self::$gazetteBuilt;
    }

    public static function isPublicHoliday($ymd) {
        $ymd = (string) $ymd;
        if (isset(self::gazetteMap()[$ymd])) {
            return true;
        }
        $md = strlen($ymd) >= 10 ? substr($ymd, 5, 5) : '';
        return in_array($md, ['02-04', '05-01', '12-25'], true);
    }

    /**
     * Monday–Friday dates in the given calendar month that are not Sri Lanka public holidays.
     *
     * @param string $monthY First day of month as Y-m (e.g. 2025-05)
     * @return array<int,array{date:string,day:string,day_name:string,header:string}>
     */
    public static function weekdayTeachingDaysInMonth($monthY) {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthY)) {
            return [];
        }
        $out = [];
        $start = new DateTime($monthY . '-01');
        $end = new DateTime($start->format('Y-m-t'));

        for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
            $w = (int) $d->format('w'); // 0 Sun .. 6 Sat
            if ($w === 0 || $w === 6) {
                continue;
            }
            $ymd = $d->format('Y-m-d');
            if (self::isPublicHoliday($ymd)) {
                continue;
            }
            $out[] = [
                'date' => $ymd,
                'day' => $d->format('d'),
                'day_name' => $d->format('D'),
                'header' => $d->format('D') . ' ' . $d->format('d'),
                'header_label' => $ymd . "\n" . $d->format('l') . ' ' . $d->format('j M Y'),
            ];
        }
        return $out;
    }
}
