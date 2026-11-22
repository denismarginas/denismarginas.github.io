<?php

class PlatformDataService
{
    use PlatformDataServiceCore, PlatformDataServicePosts, PlatformDataServiceCollections, PlatformDataServiceComponents;

    private static array $cache = [];
    private static ?string $data_dir = null;
    private static ?string $language = null;
}
