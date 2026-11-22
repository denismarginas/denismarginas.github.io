<?php

class layout_content
{
    public static function render(array $data = []): string
    {
        $html = section::render_children($data);
        return $html === '' ? '' : '<div class="layout-content">' . $html . '</div>';
    }
}
