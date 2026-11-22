<?php

class utility_calc_date_interval
{
    public static function value(array $params = []): array
    {
        $start = '';
        $end = '';

        if (isset($params['date']) && is_array($params['date'])) {
            $start = (string) ($params['date']['start'] ?? '');
            $end = (string) ($params['date']['end'] ?? '');
        } else {
            $start = (string) ($params['start'] ?? '');
            $end = (string) ($params['end'] ?? '');
        }

        $endKey = strtolower(trim($end));
        $endKey = str_replace(['-', ' '], '_', $endKey);
        if ($endKey === 'working') $endKey = 'in_progress';
        $currentDateData = PlatformDataService::get_data('dates');
        $ongoingKeys = $currentDateData['current_date'] ?? null;
        $isOngoing = is_array($ongoingKeys) && !empty($ongoingKeys) ? isset($ongoingKeys[$endKey]) : in_array($endKey, ['present', 'in_progress', 'currently', 'current_date'], true);
        if ($isOngoing) {
            $end = date('Y-m-d');
        }

        if ($start === '' || $end === '') {
            return ['years' => 0, 'months' => 0, 'weeks' => 0, 'days' => 0, 'total_days' => 0];
        }

        $startDate = DateTime::createFromFormat('Y-m-d', $start);
        $endDate = DateTime::createFromFormat('Y-m-d', $end);

        if (!$startDate || !$endDate) {
            return ['years' => 0, 'months' => 0, 'weeks' => 0, 'days' => 0, 'total_days' => 0];
        }

        $interval = $startDate->diff($endDate);
        $totalDays = (int) $interval->format('%a');
        $years = (int) $interval->y;
        $months = (int) $interval->m;
        $d = (int) $interval->d;
        $months += intdiv($d, 28);
        $years += intdiv($months, 12);
        $months %= 12;
        $d %= 28;
        $weeks = intdiv($d, 7);
        $days = $d % 7;

        $result = [
            'years' => $years,
            'months' => $months,
            'weeks' => $weeks,
            'days' => $days,
            'total_days' => $totalDays,
        ];

        $timeUnits = self::loadTimeUnits();

        $result['labels'] = [
            'years' => $years === 1 ? $timeUnits['year']['singular'] : $timeUnits['year']['plural'],
            'months' => $months === 1 ? $timeUnits['month']['singular'] : $timeUnits['month']['plural'],
            'weeks' => $weeks === 1 ? $timeUnits['week']['singular'] : $timeUnits['week']['plural'],
            'days' => $days === 1 ? $timeUnits['day']['singular'] : $timeUnits['day']['plural'],
        ];

        return $result;
    }

    private static function loadTimeUnits(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $data = PlatformDataService::get_data('dates');
        if (isset($data['time_units']) && is_array($data['time_units'])) {
            $cached = $data['time_units'];
            return $cached;
        }

        return [
            'year' => ['singular' => 'Year', 'plural' => 'Years'],
            'month' => ['singular' => 'Month', 'plural' => 'Months'],
            'week' => ['singular' => 'Week', 'plural' => 'Weeks'],
            'day' => ['singular' => 'Day', 'plural' => 'Days'],
        ];
    }
}