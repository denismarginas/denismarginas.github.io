<?php

class project_page_transitions
{
    private const RULES = [
        'project_overview' => ['effect' => 'none', 'parts' => [
            ['target' => '.project-overview-intro', 'effect' => 'fade slide-bottom', 'distance' => '24px'],
            ['target' => '.project-overview-body', 'effect' => 'fade slide-bottom', 'distance' => '24px', 'delay' => 120],
        ]],
        'project_screens' => ['effect' => 'none', 'parts' => [
            ['target' => '.section-head', 'effect' => 'fade slide-bottom', 'distance' => '20px'],
            ['target' => '.project-screens', 'effect' => 'fade slide-bottom', 'distance' => '24px', 'delay' => 120],
        ]],
        'project_brand' => ['effect' => 'none', 'parts' => [
            ['target' => '.section-head', 'effect' => 'fade slide-bottom', 'distance' => '20px'],
            ['target' => '.project-gallery-grid', 'effect' => 'fade slide-bottom', 'distance' => '24px', 'delay' => 100],
        ]],
        'project_gallery' => ['effect' => 'none', 'parts' => [
            ['target' => '.section-head', 'effect' => 'fade slide-bottom', 'distance' => '20px'],
            ['target' => '.project-gallery-filters', 'effect' => 'fade', 'delay' => 100],
        ]],
        'project_videos' => ['effect' => 'none', 'parts' => [
            ['target' => '.section-head', 'effect' => 'fade slide-bottom', 'distance' => '20px'],
            ['target' => '.project-videos', 'effect' => 'fade slide-bottom', 'distance' => '24px', 'delay' => 120],
        ]],
        'project_related' => ['effect' => 'none', 'parts' => [
            ['target' => '.section-head', 'effect' => 'fade slide-bottom', 'distance' => '20px'],
            ['target' => '.project-related-listing > li', 'effect' => 'fade slide-bottom', 'distance' => '24px', 'stagger' => 100, 'delay' => 100],
        ]],
    ];

    public static function rule(string $component): ?array
    {
        return PlatformComponentRenderer::transition_rule($component) ?? (self::RULES[$component] ?? null);
    }
}
