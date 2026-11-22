<?php

require_once __DIR__ . '/../../../../card_post_project_visual_media/assets/php/parts/project_media_finder.php';
require_once __DIR__ . '/../../../../utility_post_thumbnail/assets/php/utility_post_thumbnail.php';
require_once __DIR__ . '/../../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';

class project_default_preview
{
    private const PROJECTS_DIR = 'src/content/img/projects/';
    private const WEB_CATEGORY = 'Web Development Projects';

    public static function find(array $postData): string
    {
        $media = is_array($postData['media'] ?? null) ? $postData['media'] : [];

        $path = utility_post_thumbnail::get($postData, 'default');
        if ($path !== '') return $path;

        $mediaPath = trim((string) ($media['path'] ?? ''), '/');
        $categories = (array) ($postData['taxonomy']['category'] ?? []);
        if ($mediaPath !== '' && in_array(self::WEB_CATEGORY, $categories, true)) {
            $path = utility_project_web_screens::first($mediaPath, 'desktop');
            if ($path !== '') return $path;
        }

        return project_media_finder::photo($postData);
    }

}
