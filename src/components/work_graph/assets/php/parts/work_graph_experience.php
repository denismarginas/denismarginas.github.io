<?php

class work_graph_experience
{
public static function calculate_totals(array $jobs): array
    {
        $filtered = array_values(array_filter($jobs, fn($j) => ($j['settings']['render'] ?? true) !== false));
        $filtered = PlatformComponentRenderer::value('utility_sort', ['items' => $filtered, 'rules' => ['by' => 'date.start', 'order' => 'asc']]);

        $totalYears = 0;
        $totalMonths = 0;
        $periods = [];

        foreach ($filtered as $job) {
            $date = $job['data']['date'] ?? [];
            $start = self::parse_date($date['start'] ?? '');
            $end = self::parse_date($date['end'] ?? '');

            if ($start && $end) {
                $interval = $start->diff($end);
                $totalYears += $interval->y;
                $totalMonths += $interval->m;

                $periods[] = ['start' => $start, 'end' => $end];
            }
        }
        $totalYears += floor($totalMonths / 12);
        $totalMonths = (int) ($totalMonths % 12);

        $totalDays = 0;
        foreach ($periods as $k => $p) {
            $totalDays += (int) $p['start']->diff($p['end'])->days;
            for ($i = $k + 1; $i < count($periods); $i++) {
                $os = max($p['start'], $periods[$i]['start']);
                $oe = min($p['end'], $periods[$i]['end']);
                if ($os < $oe) {
                    $totalDays -= (int) $os->diff($oe)->days;
                }
            }
        }

        $timelineYears = (int) floor($totalDays / 365);
        $timelineMonths = (int) floor(($totalDays % 365) / 30);

        return [
            'exp_years' => (int) $totalYears,
            'exp_months' => $totalMonths,
            'timeline_years' => $timelineYears,
            'timeline_months' => $timelineMonths,
        ];
    }

    public static function parse_date(string $value): ?DateTime
    {
        if ($value === '') return null;
        $key = strtolower(trim($value));
        $key = str_replace(['-', ' '], '_', $key);
        if (in_array($key, ['present', 'in_progress', 'currently', 'current_date', 'working'], true)) {
            return new DateTime();
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if ($dt) return $dt;
        $dt = DateTime::createFromFormat('d.m.Y', $value);
        return $dt ?: null;
    }

    public static function calculate_days_in_month(DateTime $month, array $jobs): int
    {
        $monthStart = new DateTime($month->format('Y-m-01'));
        $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
        $days = 0;
        foreach ($jobs as $job) {
            if (($job['settings']['render'] ?? true) === false) continue;
            $date = $job['data']['date'] ?? [];
            $s = self::parse_date($date['start'] ?? '');
            $e = self::parse_date($date['end'] ?? '');
            if (!$s || !$e) continue;
            $os = max($s, $monthStart);
            $oe = min($e, $monthEnd);
            if ($os <= $oe) $days += (int) $os->diff($oe)->days + 1;
        }
        return min($days, (int) $month->format('t'));
    }
}