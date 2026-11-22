<?php

class work_graph_timeline extends work_graph_experience
{
    public static function render(array $data = []): string
    {
        $list = (string) ($data['list'] ?? 'job');
        $jobs = PlatformDataService::get_collection($list) ?? [];
        $jobs = PlatformComponentRenderer::value('utility_sort', ['items' => $jobs, 'rules' => ['by' => 'date.start', 'order' => 'asc']]);

        $range = self::date_range($jobs);
        $workTimeline = self::build_work_timeline($range);
        $jobsTimeline = self::build_jobs_timeline($range, $jobs);
        $summary = self::build_summary($jobs, $data);

        return PlatformTemplateRenderer::render([
            'work_timeline' => $workTimeline,
            'jobs_timeline' => $jobsTimeline,
            'experience_summary' => $summary,
        ]);
    }

    public static function date_range(array $jobs): array
    {
        $earliest = null;
        $latest = null;
        foreach ($jobs as $job) {
            if (($job['settings']['render'] ?? true) === false) continue;
            $date = $job['data']['date'] ?? [];
            $s = self::parse_date($date['start'] ?? '');
            $e = self::parse_date($date['end'] ?? '');
            if (!$s || !$e) continue;
            if ($earliest === null || $s < $earliest) $earliest = $s;
            if ($latest === null || $e > $latest) $latest = $e;
        }
        return [$earliest, $latest];
    }

    public static function build_work_timeline(array $range): string
    {
        [$earliest, $latest] = $range;
        if (!$earliest || !$latest) return '<div class="work-timeline"></div>';
        $html = '<div class="work-timeline">';
        for ($y = (int) $earliest->format('Y'); $y <= (int) $latest->format('Y'); $y++) {
            $html .= '<div class="year"><span>' . $y . '</span>';
            for ($m = 1; $m <= 12; $m++) {
                $html .= '<div class="month"><span>' . PlatformTextService::format_date("$y-$m-01", 'M.Y') . '</span></div>';
            }
            $html .= '</div>';
        }
        return $html . '</div>';
    }

    public static function build_jobs_timeline(array $range, array $jobs): string
    {
        [$earliest, $latest] = $range;
        if (!$earliest || !$latest) return '<div class="jobs-timeline"></div>';
        $html = '<div class="jobs-timeline">';
        for ($y = (int) $earliest->format('Y'); $y <= (int) $latest->format('Y'); $y++) {
            $html .= '<div class="year"><span>' . $y . '</span>';
            for ($m = 1; $m <= 12; $m++) {
                $cur = new DateTime("$y-$m-01");
                $days = self::calculate_days_in_month($cur, $jobs);
                $cls = $days >= 28 ? 'month worked' : 'month';
                $label = PlatformTextService::format_date($cur->format('Y-m-d'), 'M.Y');
                $html .= '<div class="' . $cls . '" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"><span>' . $label . '</span></div>';
            }
            $html .= '</div>';
        }
        return $html . '</div>';
    }

    public static function build_summary(array $jobs, array $data): string
    {
        $totals = self::calculate_totals($jobs);
        $expText = (string) ($data['total_work_exp_text'] ?? 'Total Work Experience:');
        $timelineText = (string) ($data['total_work_timeline_text'] ?? 'Total Work Timeline:');
        $html = '';
        if ($totals['exp_years'] > 0 || $totals['exp_months'] > 0) {
            $html .= '<div><span>' . htmlspecialchars($expText, ENT_QUOTES, 'UTF-8') . ' </span> <span>' . PlatformComponentRenderer::render('utility_datetime_amount', ['interval' => ['years' => $totals['exp_years'], 'months' => $totals['exp_months'], 'weeks' => 0, 'days' => 0, 'total_days' => $totals['exp_years'] * 365 + $totals['exp_months'] * 30], 'style' => 'minimal']) . ' </span></div>';
        }
        if ($totals['timeline_years'] > 0 || $totals['timeline_months'] > 0) {
            $html .= '<div><span>' . htmlspecialchars($timelineText, ENT_QUOTES, 'UTF-8') . ' </span> <span>' . PlatformComponentRenderer::render('utility_datetime_amount', ['interval' => ['years' => $totals['timeline_years'], 'months' => $totals['timeline_months'], 'weeks' => 0, 'days' => 0, 'total_days' => $totals['timeline_years'] * 365 + $totals['timeline_months'] * 30], 'style' => 'minimal']) . ' </span></div>';
        }
        return $html;
    }
}

