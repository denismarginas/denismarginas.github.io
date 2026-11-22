<?php

class utility_age
{
    private const FORMATS = ['d-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'];

    public static function value(array $params = []): ?int
    {
        $format = is_scalar($params['format'] ?? null) ? trim((string) $params['format']) : '';

        $start = self::parse($params['date'] ?? $params['start'] ?? '', $format);
        if ($start === null) return null;

        $endRaw = $params['end'] ?? '';
        $end = (is_scalar($endRaw) && trim((string) $endRaw) !== '')
            ? self::parse($endRaw, $format)
            : new DateTimeImmutable('today');
        if ($end === null || $end < $start) return null;

        return (int) $start->diff($end)->y;
    }

    public static function render(array $data = []): string
    {
        $years = self::value($data);
        return $years === null ? '' : (string) $years;
    }

    private static function parse(mixed $raw, string $format = ''): ?DateTimeImmutable
    {
        if (!is_scalar($raw)) return null;
        $date = trim((string) $raw);
        if ($date === '') return null;

        $formats = $format !== '' ? array_merge([$format], self::FORMATS) : self::FORMATS;
        foreach ($formats as $f) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $f, $date);
            if ($parsed !== false && $parsed->format($f) === $date) return $parsed;
        }
        return null;
    }
}
