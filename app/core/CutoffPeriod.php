<?php
namespace App\Core;

/**
 * Semi-monthly cutoff periods used for budget allocation, mirroring the same
 * payroll cutoff split (1-15/16, 16/17-30/31, calendar-aware for February).
 * A period is identified by a stable key like "2026-08-H1" / "2026-08-H2" so
 * it can be used anywhere a period identifier is needed (budgets.month_year,
 * store_requisitions.budget_month_year, budget_adjustments.month_year,
 * revenue_splits.budget_period) without re-deriving date math at each call
 * site.
 */
class CutoffPeriod
{
    /**
     * The two cutoff halves for a given year/month.
     * Each half: ['half' => 1|2, 'key' => 'YYYY-MM-H1', 'start_date' => 'YYYY-MM-DD',
     *             'end_date' => 'YYYY-MM-DD', 'label' => 'August 1-15, 2026']
     */
    public static function getHalves($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $mid = $daysInMonth >= 30 ? ($daysInMonth === 31 ? 16 : 15) : 15;

        $half1Start = sprintf('%04d-%02d-01', $year, $month);
        $half1End = sprintf('%04d-%02d-%02d', $year, $month, $mid - 1);
        $half2Start = sprintf('%04d-%02d-%02d', $year, $month, $mid);
        $half2End = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
        $keyBase = sprintf('%04d-%02d', $year, $month);

        return [
            [
                'half' => 1,
                'key' => $keyBase . '-H1',
                'start_date' => $half1Start,
                'end_date' => $half1End,
                'label' => "$monthName 1-" . ($mid - 1) . ", $year"
            ],
            [
                'half' => 2,
                'key' => $keyBase . '-H2',
                'start_date' => $half2Start,
                'end_date' => $half2End,
                'label' => "$monthName $mid-$daysInMonth, $year"
            ]
        ];
    }

    /**
     * The cutoff key ("YYYY-MM-H1"/"YYYY-MM-H2") that a given date falls
     * inside. $date accepts anything strtotime() understands; defaults to now.
     */
    public static function getKeyForDate($date = null)
    {
        $ts = $date ? strtotime($date) : time();
        $year = (int)date('Y', $ts);
        $month = (int)date('n', $ts);
        $day = (int)date('j', $ts);

        $halves = self::getHalves($year, $month);
        foreach ($halves as $half) {
            $startDay = (int)date('j', strtotime($half['start_date']));
            $endDay = (int)date('j', strtotime($half['end_date']));
            if ($day >= $startDay && $day <= $endDay) {
                return $half['key'];
            }
        }
        return $halves[1]['key'];
    }

    public static function getCurrentKey()
    {
        return self::getKeyForDate();
    }

    /**
     * Parse a "YYYY-MM-H1"/"YYYY-MM-H2" key back into its start/end dates and
     * human label, for display. Returns null if the key isn't a recognized
     * cutoff key (e.g. legacy 'YYYY-MM' data from before this migration).
     */
    public static function describeKey($key)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-H([12])$/', $key, $m)) {
            return null;
        }
        $halves = self::getHalves((int)$m[1], (int)$m[2]);
        return $halves[(int)$m[3] - 1];
    }

    /**
     * All cutoff halves for a range of months around today (from $monthsBack
     * months ago through $monthsForward months ahead), newest first -- used
     * to populate period pickers without the caller doing date math.
     */
    public static function getRecentHalves($monthsBack = 2, $monthsForward = 1)
    {
        $halves = [];
        $cursor = new \DateTime('first day of this month');
        $cursor->modify('-' . $monthsBack . ' months');
        $total = $monthsBack + $monthsForward + 1;
        for ($i = 0; $i < $total; $i++) {
            $monthHalves = self::getHalves((int)$cursor->format('Y'), (int)$cursor->format('n'));
            array_push($halves, ...$monthHalves);
            $cursor->modify('+1 month');
        }
        return array_reverse($halves);
    }
}
