<?php

class tab_items_listing_loader
{
    public static function load_education_partial(string $name, array $vars): string
    {
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/education/' . $name, $vars);
    }

    public static function load_job_partial(string $name, array $vars): string
    {
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/job/' . $name, $vars);
    }
}
