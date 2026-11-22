<?php

class project_device_render_conditions
{
    private const DEFAULT_MODEL = 'model-03';

    public static function resolve(array $postData, array $deviceModels): array
    {
        return [
            'desktop' => self::resolve_for_type($postData, $deviceModels['desktop'] ?? []),
            'phone' => self::resolve_for_type($postData, $deviceModels['phone'] ?? []),
        ];
    }

    private static function resolve_for_type(array $postData, array $availableModels): string
    {
        $dateStart = (string) ($postData['date']['start'] ?? '');
        if ($dateStart === '') {
            return self::DEFAULT_MODEL;
        }

        $types = is_array($postData['project']['types'] ?? null) ? $postData['project']['types'] : [];
        if (in_array('personal', $types, true) && !in_array("bachelor's thesis", $types, true)) {
            return 'model-01';
        }

        $year = preg_match('/\d{4}/', $dateStart, $m) ? (int) $m[0] : null;
        if ($year !== null && $year < 2022 && !empty($availableModels['model-02'])) {
            return 'model-02';
        }

        return self::DEFAULT_MODEL;
    }
}
