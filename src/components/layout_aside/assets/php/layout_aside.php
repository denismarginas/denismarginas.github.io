<?php

class layout_aside
{
    public static function render(array $data = []): string
    {
        $html = section::render_children($data);
        return $html === '' ? '' : '<div class="layout-aside">' . $html . '</div>';
    }
}
