<?php

function platform_render_generate_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/generate/assets/css/generate.css');
    platform_asset('js', 'components/generate/assets/js/generate.js');
    platform_asset('js', 'components/generate/assets/js/generate-resumes.js');

    $tabs = array_keys(platform_menu()['generate']['children']);
    $tab = in_array($tab, $tabs, true) ? $tab : 'translate';

    $icon = static function (string $name) {
        return PlatformSvg::render(['name' => $name, 'size' => 16]);
    };

    $cards = [];
    foreach (platform_menu()['generate']['children'] as $childKey => $child) {
        $cards[] = [
            'title'  => $child['label'],
            'desc'   => $child['desc'] ?? '',
            'icon'   => $child['icon'],
            'url'    => $child['url'],
            'active' => $tab === $childKey,
        ];
    }

    $visible = static function (string $panel) use ($tab): string {
        return $tab === $panel ? '' : ' style="display:none"';
    };

    ob_start();
    ?>
    <div class="platform-admin-page" data-generate-root data-generate-tab="<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="platform-page-heading">
            <h2 class="platform-title">Generate</h2>
            <p class="platform-description">Generate site artifacts from content data.</p>
        </div>

        <?php echo platform_render_cards($cards); ?>

        <section class="platform-generate-panel" data-generate-panel-translate<?php echo $visible('translate'); ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">Translate</h2>
                </div>
                <p class="platform-description">Translates every <code>data_*.json</code> of the default language into each target language folder with a local AI model (Ollama). Only new or changed texts are translated; finished translations are kept in <code>dev/platform/data/translations</code>, and texts you edit by hand in a target file are not overwritten while the English source stays the same.</p>
                <div class="platform-translate-modes" data-translate-modes>
                    <div class="platform-generate-toolbar">
                        <strong>Language output</strong>
                        <span class="platform-status" data-translate-modes-status>Loading...</span>
                    </div>
                    <p class="platform-description"><strong>Static HTML pages</strong>: a full copy of the site in <code>dist/&lt;lang&gt;/</code>, indexed by search engines (hreflang + sitemap). <strong>Frontend (JS &amp; JSON)</strong>: no HTML copy; the bulk render writes small dictionaries to <code>dist/i18n/&lt;lang&gt;/</code> (one shared file + one per page, only the texts that differ) and the default pages are translated in the browser behind the preloader. Not indexed. Run <em>Render → HTML Bulk Render</em> after changing this.</p>
                    <div class="platform-generate-toolbar" data-translate-modes-list></div>
                </div>
                <div class="platform-generate-warnings" data-translate-provider></div>
                <div class="platform-generate-toolbar">
                    <select class="platform-input platform-generate-select-sm" data-translate-lang></select>
                    <select class="platform-input platform-generate-select" data-translate-file>
                        <option value="">Loading files...</option>
                    </select>
                    <label class="platform-translate-check" title="On: every text is translated again and replaces what is in the target files, including your hand edits (your custom translations still win). Off: existing translations are kept; only missing texts and texts whose English changed are translated."><input type="checkbox" data-translate-overwrite> Overwrite existing translations</label>
                    <button class="platform-button" type="button" data-generate-action="translate"><?php echo $icon('update'); ?>Run translation</button>
                    <button class="platform-button" type="button" data-generate-action="translate-stop" style="display:none"><?php echo $icon('close'); ?>Stop</button>
                    <span class="platform-status" data-generate-status>Idle</span>
                </div>
                <div class="platform-generate-results" data-generate-results></div>
                <div class="platform-translate-rules" data-translate-rules>
                    <div class="platform-generate-toolbar">
                        <strong>What to translate</strong>
                        <button class="platform-button" type="button" data-generate-action="translate-rules-save"><?php echo $icon('save'); ?>Save rules</button>
                        <span class="platform-status" data-translate-rules-status>Loading...</span>
                    </div>
                    <p class="platform-description">Only text values are translated. Ticked keys are translated wherever they appear; unticked keys (ids, links, classes, dates…) are copied as they are, with everything inside them.</p>
                    <div class="platform-translate-rules-grid">
                        <div class="platform-translate-keys">
                            <div class="platform-generate-toolbar">
                                <input class="platform-input" type="search" placeholder="Filter keys or examples" data-translate-keys-filter>
                                <label class="platform-translate-check"><input type="checkbox" data-translate-keys-empty> Show keys without text</label>
                            </div>
                            <div class="platform-translate-keys-head"><span>Translate</span><span>Key</span><span>Texts</span><span>Example</span></div>
                            <div class="platform-translate-keys-list" data-translate-keys></div>
                        </div>
                        <div class="platform-translate-lists">
                            <label class="platform-translate-field">
                                <span>Never translate these words and names <small>(one per line, kept as written inside any text)</small></span>
                                <textarea class="platform-input" rows="10" data-translate-glossary></textarea>
                            </label>
                            <div class="platform-translate-overrides" data-translate-overrides></div>
                            <label class="platform-translate-field">
                                <span>Never translate these exact texts <small>(one per line, a value equal to one of them is copied)</small></span>
                                <textarea class="platform-input" rows="8" data-translate-skip-values></textarea>
                            </label>
                        </div>
                    </div>
                </div>
                <?php echo platform_render_vars('translation', 'translate'); ?>
            </div>
        </section>

        <section class="platform-generate-panel" data-generate-panel-thumbnails<?php echo $visible('thumbnails'); ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">Thumbnails</h2>
                </div>
                <p class="platform-description">Builds one <code>thumbnail/thumbnail.webp</code> per render:true project (website screenshots when there are any, otherwise media images) from the template in <code>dev/platform/assets/img/project_thumbnail</code>.</p>
                <div class="platform-generate-warnings" data-thumbs-warnings></div>
                <div class="platform-generate-toolbar">
                    <select class="platform-input platform-generate-select" data-thumbs-project>
                        <option value="">Loading projects...</option>
                    </select>
                    <button class="platform-button" type="button" data-thumbs-action="preview"><?php echo $icon('view'); ?>Preview</button>
                    <button class="platform-button" type="button" data-thumbs-action="generate"><?php echo $icon('generate'); ?>Generate</button>
                    <button class="platform-button" type="button" data-thumbs-action="stop" style="display:none"><?php echo $icon('close'); ?>Stop</button>
                    <span class="platform-status" data-thumbs-status>Idle</span>
                </div>
                <div class="platform-generate-thumbs" data-thumbs-results></div>
                <?php echo platform_render_vars('thumbnails', 'generate-thumbnails'); ?>
            </div>
        </section>

        <section class="platform-generate-panel" data-generate-panel-resumes<?php echo $visible('resumes'); ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">Resume Images</h2>
                </div>
                <p class="platform-description">Renders page 1 of each resume PDF (<code>data.media.pdf</code> of the resume items) in the browser with pdf.js and saves <code>img/&lt;output_folder&gt;/&lt;_id&gt;/&lt;_id&gt;.webp</code> (<code>width</code> px, height auto) and <code>&lt;_id&gt;-thumbnail.webp</code> (<code>thumbnail_width</code> px), then points <code>media.image</code> / <code>media.thumbnail</code> of the item to them.</p>
                <div class="platform-generate-warnings" data-resumes-warnings></div>
                <div class="platform-generate-toolbar">
                    <select class="platform-input platform-generate-select" data-resumes-select>
                        <option value="">Loading resumes...</option>
                    </select>
                    <button class="platform-button" type="button" data-resumes-action="preview"><?php echo $icon('view'); ?>Preview</button>
                    <button class="platform-button" type="button" data-resumes-action="generate"><?php echo $icon('generate'); ?>Generate</button>
                    <button class="platform-button" type="button" data-resumes-action="stop" style="display:none"><?php echo $icon('close'); ?>Stop</button>
                    <span class="platform-status" data-resumes-status>Idle</span>
                </div>
                <div class="platform-generate-thumbs" data-resumes-results></div>
                <?php echo platform_render_vars('resumes', 'generate-resumes'); ?>
            </div>
        </section>
    </div>
    <?php
    return ob_get_clean();
}
