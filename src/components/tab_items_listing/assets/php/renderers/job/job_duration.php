<?php

class tab_items_listing_job_duration
{
    public static function render_job_duration(array $date): string
    {
        $interval = PlatformComponentRenderer::value('utility_calc_date_interval', ['date' => $date]);
        if (empty($interval['total_days'])) return '';

        return PlatformComponentRenderer::render('utility_datetime_amount', [
            'interval' => $interval,
        ]);
    }
}
