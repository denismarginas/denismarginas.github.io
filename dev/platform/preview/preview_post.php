<?php

function platform_find_preview_post(string $postId): array
{
    $types = PlatformDataService::get_data('settings_types');
    if (!$types) {
        return ['post' => null, 'type' => '', 'config' => []];
    }

    foreach (($types['post'] ?? []) as $typeKey => $config) {
        $posts = PlatformDataService::get_all_posts_from_file($typeKey);
        if ($posts === null) continue;
        foreach ($posts as $post) {
            $id = $post['_id'] ?? $post['post_id'] ?? '';
            if ($id === $postId) {
                return ['post' => $post, 'type' => $typeKey, 'config' => $config];
            }
        }
    }

    foreach (($types['collection'] ?? []) as $typeKey => $config) {
        if (($config['routable'] ?? false) !== true) continue;
        $entries = PlatformDataService::get_collection($typeKey);
        if ($entries === null) continue;
        foreach ($entries as $entry) {
            if (($entry['_id'] ?? '') === $postId) {
                return ['post' => $entry, 'type' => $typeKey, 'config' => $config];
            }
        }
    }

    foreach (($types['taxonomy'] ?? []) as $typeKey => $config) {
        $terms = PlatformDataService::get_data('taxonomy_' . $typeKey);
        if ($terms === null) continue;
        foreach ($terms as $term) {
            $id = $term['_id'] ?? '';
            if ($id === $postId) {
                return ['post' => $term, 'type' => 'taxonomy:' . $typeKey, 'config' => $config];
            }
        }
    }

    return ['post' => null, 'type' => '', 'config' => []];
}

function platform_normalize_preview_post(array $postData): array
{
    if (isset($postData['data']['taxonomy'])) {
        $postData['data'] = array_merge($postData['data']['taxonomy'], $postData['data']);
    }

    if (!isset($postData['seo']) && isset($postData['data']['seo'])) {
        $postData['seo'] = $postData['data']['seo'];
    }

    return $postData;
}

function platform_generate_project_seo(array $postData, array $seoConfig, string $postId): array
{
    if (isset($postData['seo'])) return $postData;

    $titleSrc = $postData['data']['title'] ?? $postId;
    $descSrc = $postData['data']['description'] ?? '';
    $titleMax = isset($seoConfig['title_max']) ? (int)$seoConfig['title_max'] : 50;
    $descMax = isset($seoConfig['description_max']) ? (int)$seoConfig['description_max'] : 140;

    $postData['seo'] = [
        'title' => strlen($titleSrc) > $titleMax ? substr($titleSrc, 0, $titleMax - 3) . '...' : $titleSrc,
        'description' => strlen($descSrc) > $descMax ? substr($descSrc, 0, $descMax - 3) . '...' : $descSrc,
        'keywords' => $seoConfig['keywords_source'] ?? $postId,
        'index' => ($seoConfig['index'] ?? 'index') === 'index',
        'slug' => $postData['seo']['slug'] ?? $postData['_id'] ?? '',
    ];

    return $postData;
}
