<?php

class PlatformComponentRenderer
{
    use PlatformComponentRendererLoader, PlatformComponentRendererConfig, PlatformComponentRendererAssets, PlatformComponentRendererClass;

    private static array $component_cache = [];
    private static array $loaded_classes = [];
    private static array $used_components = [];
}
