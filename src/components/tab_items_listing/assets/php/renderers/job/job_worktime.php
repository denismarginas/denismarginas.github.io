<?php

class tab_items_listing_job_worktime
{
    public const FULL_DAY_HOURS = 8;

    public static function render_worktime_types(array $workTimeTypes): string
    {
        if (empty($workTimeTypes)) return '';
        $items = '';
        foreach ($workTimeTypes as $wt) {
            $name = htmlspecialchars((string) ($wt['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $hours = htmlspecialchars((string) ($wt['hours'] ?? ''), ENT_QUOTES, 'UTF-8');
            $date = tab_items_listing_education_date::education_date_range(['start' => $wt['start'] ?? '', 'end' => $wt['end'] ?? '']);
            $items .= tab_items_listing_loader::load_job_partial('worktime_type.html', [
                'name' => $name,
                'icon' => self::icon((string) ($wt['hours'] ?? '')),
                'hours' => $hours,
                'date' => htmlspecialchars($date, ENT_QUOTES, 'UTF-8'),
            ]);
        }
        if ($items === '') return '';
        return tab_items_listing_loader::load_job_partial('worktime_types_container.html', ['items' => $items]);
    }

    private static function icon(string $hours): string
    {
        if (!preg_match('/(\d+(?:[.,]\d+)?)/', $hours, $m)) return '';
        $value = (float) str_replace(',', '.', $m[1]);
        if ($value <= 0) return '';
        $percent = round(min(1, $value / self::FULL_DAY_HOURS) * 100, 1);

        return '<svg class="tab-items-listing-worktime-icon" viewBox="0 0 16 16" aria-hidden="true">'
            . '<circle class="tab-items-listing-worktime-icon-ring" cx="8" cy="8" r="7"></circle>'
            . '<circle class="tab-items-listing-worktime-icon-fill" cx="8" cy="8" r="2.75" pathLength="100" stroke-dasharray="' . $percent . ' 100" transform="rotate(-90 8 8)"></circle>'
            . '</svg>';
    }
}
