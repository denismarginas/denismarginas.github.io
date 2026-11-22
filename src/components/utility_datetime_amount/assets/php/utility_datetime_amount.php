<?php

class utility_datetime_amount
{
    public static function render(array $params = []): string
    {
        $interval = $params['interval'] ?? [];
        if (!is_array($interval) || empty($interval['total_days']) || $interval['total_days'] <= 0) return '';

        $years = (int) ($interval['years'] ?? 0);
        $months = (int) ($interval['months'] ?? 0);
        $weeks = (int) ($interval['weeks'] ?? 0);
        $labels = $interval['labels'] ?? null;
        if (!is_array($labels) || empty($labels['years'])) {
            $tu = PlatformDataService::get_data('dates')['time_units'] ?? [];
            $labels = [
                'years' => $years === 1 ? ($tu['year']['singular'] ?? 'Year') : ($tu['year']['plural'] ?? 'Years'),
                'months' => $months === 1 ? ($tu['month']['singular'] ?? 'Month') : ($tu['month']['plural'] ?? 'Months'),
                'weeks' => $weeks === 1 ? ($tu['week']['singular'] ?? 'Week') : ($tu['week']['plural'] ?? 'Weeks'),
            ];
        }
        $style = (string) ($params['style'] ?? 'default');
        if ($style === 'minimal') {
            $plain = [];
            if ($years > 0) {
                $plain[] = $years . ' ' . $labels['years'];
                if ($months > 0) $plain[] = $months . ' ' . $labels['months'];
            } else {
                if ($months > 0) $plain[] = $months . ' ' . $labels['months'];
                if ($weeks > 0) $plain[] = $weeks . ' ' . $labels['weeks'];
            }
            if (empty($plain)) return '';
            return implode(', ', $plain);
        }

        $parts = [];
        if ($years > 0) {
            $parts[] = '<amount-primary class="datetime-amount primary">' . $years . '</amount-primary> <span class="datetime-text primary">' . htmlspecialchars($labels['years'], ENT_QUOTES, 'UTF-8') . '</span>';
            if ($months > 0) $parts[] = '<amount-secondary class="datetime-amount secondary">' . $months . '</amount-secondary> <span class="datetime-text secondary">' . htmlspecialchars($labels['months'], ENT_QUOTES, 'UTF-8') . '</span>';
        } else {
            if ($months > 0) $parts[] = '<amount-primary class="datetime-amount primary">' . $months . '</amount-primary> <span class="datetime-text primary">' . htmlspecialchars($labels['months'], ENT_QUOTES, 'UTF-8') . '</span>';
            if ($weeks > 0) {
                $tag = $months > 0 ? 'amount-secondary' : 'amount-primary';
                $cls = $months > 0 ? 'secondary' : 'primary';
                $parts[] = '<' . $tag . ' class="datetime-amount ' . $cls . '">' . $weeks . '</' . $tag . '> <span class="datetime-text ' . $cls . '">' . htmlspecialchars($labels['weeks'], ENT_QUOTES, 'UTF-8') . '</span>';
            }
        }

        if (empty($parts)) return '';
        $html = implode(' ', $parts);

        return PlatformTemplateRenderer::render(['content' => $html]);
    }
}

