<?php

class page_content
{
    public static function render(array $data = []): string
    {
        $bodyContent = $data['body_content'] ?? $data['content'] ?? $data['children_html'] ?? '';
        return '<main>' . $bodyContent . '</main>';
    }
}
