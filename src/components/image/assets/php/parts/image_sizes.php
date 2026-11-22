<?php

class image_sizes
{
    public static function get_sizes(string $absolutePath): ?array
    {
        $size = @getimagesize($absolutePath);
        if (!$size || !isset($size[0], $size[1])) return null;
        return [$size[0], $size[1]];
    }
}


