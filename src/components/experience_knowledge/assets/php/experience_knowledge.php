<?php

class experience_knowledge
{
    public static function render(array $data = []): string
    {
        $exp = $data['experience'] ?? PlatformDataService::get_data('content_experience');
        if (!is_array($exp) || empty($exp)) return '';

        $portrait = self::render_portrait($exp['media']['portrait']['img'] ?? '');
        $main = self::render_main($exp);
        $side = self::render_side($exp);

        return PlatformTemplateRenderer::render([
            'portrait' => $portrait,
            'main' => $main,
            'side' => $side,
        ]);
    }

    private static function render_portrait(string $src): string
    {
        if ($src === '') return '';
        $img = PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => 'Portrait', 'class' => 'portrait-img w-100 h-auto']);
        $dots = PlatformComponentRenderer::render('svg', ['icon' => 'dots-graphic', 'class' => 'portrait-dots w-100 h-auto']);
        return '' . $img . '<div class="portrait-graphic">' . $dots . '</div>';
    }

    private static function render_main(array $exp): string
    {
        $title = (string) ($exp['title'] ?? '');
        $textItems = $exp['knowledge_lists_text']['text_items'] ?? [];

        $html = PlatformComponentRenderer::render('text_block', ['elements' => [
            ['title' => $title],
            ['paragraphs' => $textItems],
        ]]);

        $lists = $exp['knowledge_lists_text']['list_items'] ?? [];
        if (!empty($lists) && is_array($lists)) {
            $html .= '<div class="knowledge-lists">';
            foreach ($lists as $cat) {
                $html .= '<div class="knowledge-group"><p class="knowledge-title">' . htmlspecialchars((string)($cat['title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p><ul>';
                foreach ($cat['text_list'] ?? [] as $item) $html .= '<li>' . htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') . '</li>';
                $html .= '</ul></div>';
            }
            $html .= '</div>';
        }

        $icons = PlatformDataService::get_collection('knowledge_item') ?? [];
        if (!empty($icons) && is_array($icons)) {
            $icons = self::sort_icons($icons);
            $html .= '<ul class="knowledge-icons">';
            foreach ($icons as $ic) {
                if (!is_array($ic) || ($ic['settings']['render'] ?? true) === false) continue;
                $svg = PlatformComponentRenderer::render('svg', ['icon' => (string)($ic['svg'] ?? ''), 'class' => 'knowledge-icon']);
                $url = (string)($ic['external_url'] ?? '');
                $text = htmlspecialchars((string)($ic['text'] ?? ''), ENT_QUOTES, 'UTF-8');
                $item = $svg . '<span>' . $text . '</span>';
                $html .= $url !== '' ? '<li><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $svg . '</a></li>' : '<li>' . $svg . '</li>';
            }
            $html .= '</ul>';
        }

        $buttons = $exp['buttons'] ?? [];
        if (!empty($buttons) && is_array($buttons)) {
            $buttonItems = array_map(fn($btn) => [
                'text' => (string) ($btn['text'] ?? ''),
                'link' => $btn['_id'] ?? '',
                'svg' => (string) ($btn['svg'] ?? ''),
            ], $buttons);
            $html .= PlatformComponentRenderer::render('text_block', ['elements' => [
                ['buttons' => $buttonItems],
            ]]);
        }

        return $html;
    }

    private static function sort_icons(array $items): array
    {
        $i = 0;
        foreach ($items as &$item) $item = ['pos' => $i++, 'item' => $item];
        unset($item);
        usort($items, function ($a, $b) {
            $oa = is_array($a['item']) && is_numeric($a['item']['settings']['order'] ?? null) ? (float) $a['item']['settings']['order'] : INF;
            $ob = is_array($b['item']) && is_numeric($b['item']['settings']['order'] ?? null) ? (float) $b['item']['settings']['order'] : INF;
            return $oa <=> $ob ?: $a['pos'] <=> $b['pos'];
        });
        return array_column($items, 'item');
    }

    private static function render_side(array $exp): string
    {
        $html = '';
        $textList = $exp['text_list'] ?? [];
        if (!empty($textList) && is_array($textList)) {
            $inner = PlatformComponentRenderer::render('text_block', ['elements' => [
                ['paragraphs' => $textList],
            ]]);
            $html .= '<div class="text-list w-60 w-md-100">' . $inner . '</div>';
        }
        $banner = $exp['media']['banner']['img'] ?? '';
        if ($banner !== '') {
            $img = PlatformComponentRenderer::render('image', [
                'src' => $banner,
                'alt' => 'Banner',
                'class' => 'banner-img w-100',
                'attributes' => ['data-popup' => 'true'],
            ]);
            $html .= '<div class="banner w-40 w-md-100">' . $img . '</div>';
        }
        return $html;
    }
}