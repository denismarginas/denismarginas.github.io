<?php

class tab_items_listing_text_visibility
{
    public static function should_render_text(string $svg, array $data): bool
    {
        if (array_key_exists('external_links_text', $data) && $data['external_links_text'] === false) {
            return false;
        }
        if (array_key_exists('external_links_social_text', $data) && $data['external_links_social_text'] === false) {
            if (str_starts_with($svg, 'socials')) {
                return false;
            }
        }
        return true;
    }
}