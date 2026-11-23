# Portfolio CMS — Architecture

Portfolio CMS (by Denis Marginas) is a small, file-based CMS and static site generator written in plain PHP.
Content lives in JSON files, pages are built from reusable **components**, and the result is written as static
HTML to `dist/` (published to GitHub Pages at `https://denismarginas.github.io/`).

The code is kept without comments on purpose. This file is the explanation: how the pieces fit, how a page is
rendered, and the conventions to follow when adding something new.

---

## 1. Running it

```
start.bat                                  -> dev/platform/start-platform.bat
```

`start-platform.bat`:

1. installs `scssphp` into `dev/dependencies/scssphp` if missing (`require/install-scssphp.bat`)
2. finds a local PHP in `dev/dependencies/php-v-8.5*/current/php.exe`, installs it if missing (`require/install-php.bat`)
3. enables the GD extension (needed by thumbnails) (`require/enable-php-gd.bat`)
4. falls back to `php` on the PATH
5. when `translation.provider` is `ollama`: installs Ollama if missing (winget, else the ollama.com installer), starts it,
   and pulls `translation.model` in a separate window the first time (`require/install-ollama.bat`); this runs after
   the server has started, so a long first download never delays the admin
6. adds `HOST` to the Windows hosts file the first time, pointing at `BIND` (`require/add-hosts-entry.ps1`, asks
   for admin once; skipped for `localhost` or an ip); when that fails or is declined it opens `BIND` instead
7. starts `php -S <BIND>:<PORT> -t <project root>` and opens `http://<HOST>:<PORT>/dev/platform/` (no `:<PORT>` on 80)

Server address – the variables at the top of `start-platform.bat`:

| variable | default               | meaning                                                         |
|----------|-----------------------|-----------------------------------------------------------------|
| `HOST`   | `portfolio-cms.local` | name opened in the browser (`localhost` or an ip skips the hosts step) |
| `BIND`   | `127.0.0.1`           | address the PHP server listens on (`0.0.0.0` = reachable from the LAN) |
| `PORT`   | `8088`                | port; `80` gives `http://portfolio-cms.local/dev/platform/` when the port is free |

`start.bat` also rebuilds `start.exe` (via `build-start-exe.bat`) when the favicon is newer than the exe, so the
launcher icon follows the site favicon. That step is best-effort and never blocks the server.

The server root is the **project root**, so the admin is `/dev/platform/`, previews are
`/dev/platform/preview/?_id=<id>` and source assets are served from `/src/...`.

---

## 2. Folder map

```
denismarginas.github.io/
├─ start.bat
├─ dist/                         generated static site (HTML, sitemap.xml, robots.txt) – never edit by hand
├─ src/
│  ├─ components/<name>/         the site components (see §4)
│  │  └─ theme/                  global theme: SCSS, fonts, compiled bundle (assets_compiled/)
│  └─ content/
│     ├─ json/data/
│     │  ├─ settings/            site-wide settings (data_settings_*.json)
│     │  └─ <lang>/              content per language (en, ro, …)
│     │     ├─ post/             data_post_<type>.json
│     │     ├─ taxonomy/         data_taxonomy_<type>.json
│     │     ├─ collection/       data_collection_<type>.json
│     │     ├─ content/          data_content_*.json (header menu, footer, cookie notice, …)
│     │     └─ data_types.json   singular / plural labels of the content types
│     ├─ img/  vid/  pdf/  svg/  media files (projects/<media.path>/…, pages, workstation, …)
└─ dev/
   ├─ engine/                    the renderer (framework code, no UI)
   ├─ platform/                  the admin panel ("Portfolio CMS")
   ├─ dependencies/              local PHP, scssphp (installed by require/*.bat); Ollama is a normal Windows app
   └─ migrations/                one-off data migration scripts
```

---

## 3. Content model

### 3.1 Content types — `settings/data_settings_types.json`

Three kinds of content, each a map of `type key -> config`:

| kind         | example types                         | routable                                   |
|--------------|---------------------------------------|--------------------------------------------|
| `post`       | `page`, `project`, `workstation`      | yes (unless `"routable": false`)           |
| `taxonomy`   | `category`                            | yes (unless `"routable": false`)           |
| `collection` | `job`, `education`, `video`, `resume` | only with `"routable": true`               |

Type config keys:

| key                                   | meaning                                                                 |
|---------------------------------------|-------------------------------------------------------------------------|
| `label`                               | admin label                                                             |
| `file`                                | data file name, found anywhere under `data/<lang>/`                     |
| `root`                                | output folder in `dist/` (`/project/` → `dist/project/<id>.html`)        |
| `structure`                           | workflow section that picks header / footer / page component            |
| `seo`                                 | workflow section with SEO defaults (`seo_page`, `seo_project`)          |
| `global_content_path` / `_img_path` / `_vid_path` | base media folders passed to every component of that type   |
| `post_types` (taxonomy)               | which post types use the taxonomy                                       |
| `title_path`                          | where the admin listing reads the title (dot path)                      |

Routable = rendered to its own HTML page, listed in the sitemap and in search.

### 3.2 Entry shape

Every data file is a JSON array of entries:

```json
{
  "_id": "about",
  "data": {
    "seo": { "title": "…", "description": "…", "keywords": "…", "slug": "about", "index": true },
    "taxonomy": { "category": ["web"] },
    "media":    { "path": "zozo", "logo": { "img": "…" }, "thumbnail": { "default": "…" } },
    "settings": { "appearance": { "colors": { "primary": "#…" } } }
  },
  "content": [
    { "component": "section", "data": { "variant": "panel" }, "children": [
        { "component": "text_block", "data": { "title": "…" } }
    ] }
  ],
  "settings": { "render": true, "search": true }
}
```

- `_id` is the page id and file name (`about` → `about.html`).
- `content` (or `data.content`) is the page body: a list of **component blocks** `{ component, data, children }`.
  The same block shape is used everywhere (sections, columns, footer columns, popups…).
- `data.taxonomy` is merged into `data` before rendering (`platform_normalize_preview_post`).
- `settings.render: false` skips the entry in bulk render / sitemap / search; `settings.search: false` hides it from search.

### 3.3 Settings files — `data/settings/`

| file                              | holds                                                                                   |
|-----------------------------------|-----------------------------------------------------------------------------------------|
| `data_settings_site.json`         | site title, logo, favicon (`site_logo_favicon`, printed by `utility_favicon`), site url |
| `data_settings_routing.json`      | `url`, `routing.extension` (`.html`), `routing.mappings` (`front_post_id`, `404_post_id`, `search_post_id`, `privacy_post_id`), `theme` paths and default mode |
| `data_settings_languages.json`    | `default` language and the language `list` (`text`, `iso`, `mode`: `static` / `frontend`, §3.4) |
| `data_settings_seo.json`          | Google Analytics id / consent                                                           |
| `data_settings_types.json`        | content types (§3.1)                                                                    |
| `data_settings_transitions.json`  | site-wide entrance animations per component (§6.4)                                      |
| `data_settings_workflow.json`     | build / render switches used by the admin (§8)                                          |

### 3.4 Languages

`PlatformDataService::get_data($name)` looks for `data_<name>.json` (also `<name>.json`, `data-<name>.json`) in
`data/<current lang>/` (recursively), then `data/<default lang>/`, then `data/` itself. The language is the default
one unless `?lang=xx` is passed. The **Generate → Translate** tool fills the other languages from the default one
(§10).

Rendering per language:

- `PlatformPathService::languages()` is the language list (default first), `lang_prefix($lang)` is `''` for the
  default language and `'<lang>/'` for the others, `post_link($id, $lang)` / `post_path()` / `post_url()` build links.
- Bulk render loops over every language: the default goes to `dist/`, the others to `dist/<lang>/` with the same
  file names. Preview uses `?lang=xx` and keeps it in every link.
- `page_constructor` sets `<html lang>` and the `hreflang` alternates (+ `x-default`); the header has a language
  dropdown (`header_language_switcher`, flags from `content/svg/flag_<iso>.svg`); the sitemap lists every language.

**Output mode per language** – `"mode"` on an item of the `list` in `data_settings_languages.json`, set on
**Generate → Translate → Language output**:

| mode                 | output                                                                                       |
|----------------------|----------------------------------------------------------------------------------------------|
| `static` (default)   | a full HTML copy in `dist/<lang>/`, indexed (hreflang, sitemap)                              |
| `frontend`           | no copy in `dist/`; the default pages read `src/content/json/data/<lang>/` and are translated in the browser; not indexed |

The translated data files in `src/content/json/data/<lang>/` (written by **Generate → Translate**) are the source in
both modes; the mode only decides what the HTML render makes of them.

- `PlatformPathService::language_mode()`, `frontend_languages()`, `static_languages()`; `dist_lang_prefix()` is `''`
  for frontend languages, so their links point to the default pages (`post_path`, `post_url`, `post_link` in dist).
  `lang_prefix()` is unchanged and still drives `&lang=` in the preview, which renders frontend languages on the
  server as before.
- Bulk render: static languages are written as usual. Frontend languages are not rendered; the render deletes a
  leftover `dist/<lang>/` (from when the language was static) and a leftover `dist/i18n/<lang>/` (from the older
  dictionary build). Previewing a frontend language never writes to `dist/`.
- Browser (`i18n_frontend` component, only printed in dist pages when a frontend language exists): `window.dmI18n`
  holds the path from the page to the project root (`asset_relative_prefix()`), the data folder, the list of data
  files (`PlatformTranslationBuilder::files()`) that exist in each frontend language and a version from their
  modification times. The inline head script reads `?lang=` or `localStorage.dm_lang`, adds `html.i18n-loading`
  (content hidden behind the `preloader`) and fetches every listed file twice, `data/<default>/<file>` and
  `data/<lang>/<file>`, before the body is parsed. Both are walked in parallel (lists of entries matched by `_id`)
  and every text that differs becomes a pair; values with HTML or line breaks are split at tags / line breaks and
  paired piece by piece when both sides have the same number of pieces. `i18n_frontend.js` replaces text nodes and
  the tracked attributes (`alt`, `title`, `placeholder`, `aria-label`, `aria-description`, `data-tooltip`,
  `data-label*`, meta description / og / twitter, button values) whose whitespace-normalised text equals an English
  value, keeps the originals (switching back to the default needs no request), handles texts added later
  (MutationObserver), sets `<html lang>`, updates the dropdown and fires `dm:i18n-start` / `dm:i18n-done`. Dropdown
  links carry `data-i18n-lang`; on a default page the change happens in place (`?lang=` updated with
  `history.replaceState`), otherwise the link navigates with the preloader shown. Frontend links are
  `<page>.html?lang=<iso>` with `rel="nofollow"`.
- Leave a part untranslated with `translate="no"`, `data-i18n-skip` or `.notranslate` (the dropdown uses
  `translate="no"`). Not covered: text the site builds itself (dates, counts, labels joined from several values),
  text a script builds from JSON, and the search keywords (the `search` index JSON stays in the default language).
  The first Romanian page view downloads the default and translated data files (cached afterwards). Files must be
  served over http(s), and `src/content/json/data/` must be published next to `dist/`, like the other `src/` assets.

> Note: there is a `PlatformDataServiceItems` trait (for `data_items_*.json`), but it is not loaded by
> `bootstrap.php` or used by `PlatformDataService`. The current naming is `data_collection_*`.

---

## 4. Components

A component is a folder in `src/components/<name>/` (site) or `dev/platform/components/<name>/` (admin UI).

```
src/components/button/
├─ component.json
└─ assets/
   ├─ php/button.php         class button { public static function render(array $data = []): string }
   ├─ html/template.html     markup with {{ placeholders }}
   ├─ scss/*.scss            source styles  (files starting with "_" are partials)
   ├─ css/*.css              compiled from scss – generated, do not edit
   └─ js/*.js                browser scripts
```

### 4.1 `component.json`

```json
{
  "name": "button",
  "title": "Button",
  "summary": "Link button with optional svg icon.",
  "description": "Longer explanation of options and behaviour.",
  "post_types": ["page", "project"],
  "assets": {
    "php":  ["assets/php/button.php"],
    "html": ["assets/html/template.html"],
    "css":  ["assets/css/**"],
    "js":   ["assets/js/button.js"]
  },
  "dependencies": ["svg", "theme"]
}
```

- `summary` – one line, what it does. `description` – the details (data options, where it is used).
- `assets.css` / `assets.js` accept globs (`PlatformAssetResolver`): `dir/**` = every file recursively,
  `dir/*.css` = one folder, otherwise a literal path. Files starting with `_` are skipped.
- `dependencies` – components whose PHP is loaded before this one and whose CSS / JS are added to the page
  whenever this one is used.

### 4.2 The PHP class

- The class name is the folder name (`-` becomes `_`): `card_post_project`.
- `render(array $data): string` returns HTML. Static or instance method both work.
- Optional `value(array $data): mixed` – for utility components that return data instead of HTML
  (`PlatformComponentRenderer::value('utility_project_media', …)`).
- Render other components with `PlatformComponentRenderer::render('name', $data)` – never `require` them.
- Fill the template with `PlatformTemplateRenderer::render([...])`. Without a path it uses the caller's
  `../html/template.html` (or `../../html/template.html` for files in `php/parts/`). Placeholders are
  `{{ key }}` (with the spaces); values are inserted as they are, so escape them before.

### 4.3 Data every component receives

When a component is placed in page content, its `data` is merged with:

| key                    | value                                                        |
|------------------------|--------------------------------------------------------------|
| `post_current_data`    | the whole entry being rendered                               |
| `global_content_path`  | from the content type (`src/content/`)                        |
| `global_img_path`      | e.g. `src/content/img/projects`                              |
| `global_vid_path`      | e.g. `src/content/vid/projects`                              |
| `children`             | the block's `children` (component blocks or an HTML string)  |

Container components (`section`, `columns`, `layout_*`, `section_layout_aside`…) pass these keys on to their
children – use `section::render_children($data)` as the reference implementation.

Special keys handled by the renderer itself:

- `"render": false` – the block is skipped and its assets are not loaded.
- `"transition": …` – entrance animation attributes added to the block's root element (§6.4).

### 4.4 Errors

Unknown component, missing PHP file, missing class or missing `render()` are rendered by
`dev/platform/components/error-renderer` (`PlatformErrorRenderer`) instead of breaking the page.

---

## 5. The engine — `dev/engine`

`bootstrap.php` loads every engine class, initialises `PlatformConfig` and `PlatformDataService`, applies
`?lang=` and sets `$GLOBALS['url_path']` from the site url path.

| class                          | role                                                                                     |
|--------------------------------|------------------------------------------------------------------------------------------|
| `PlatformConfig` (config.php)  | absolute paths (src, dist, components, data, theme, svg, img, vid), theme config, routing mappings |
| `PlatformDataService`          | reads JSON data with language fallback and a per-request cache; posts, collections, component configs |
| `PlatformComponentRenderer`    | loads and renders components, tracks used components, builds asset tags, transitions    |
| `PlatformTemplateRenderer`     | `{{ key }}` templates                                                                    |
| `PlatformAssetResolver`        | glob expansion of component asset lists                                                  |
| `PlatformPathService`          | links between pages and asset prefixes for preview vs dist (§6.3)                        |
| `PlatformUrlService`           | url helpers (image / video paths, add / strip https)                                     |
| `PlatformTextService`          | excerpt, truncate, strip html, date formatting                                           |
| `PlatformImageRenderer`        | plain responsive `<img>`                                                                 |
| `PlatformScssService`          | scssphp wrapper (compile file / string, compressed)                                      |
| `PlatformScssBuilder`          | compiles every SCSS folder, cleans old CSS, refreshes the font list, builds the bundle   |
| `PlatformBundleBuilder`        | concatenates all component CSS / JS into the theme bundle, rebasing `url()`s            |
| `PlatformHeadHoist`            | moves `<link>/<style>/<script src>` printed in `<body>` into `<head>` (§6.5)             |
| `PlatformWorkflowService`      | read / write `data_settings_workflow.json`                                               |
| `PlatformTranslationService` / `Builder` | translate the content files to the target languages                            |
| `PlatformSearchIndexBuilder`   | rows for the search page                                                                 |
| `PlatformSeoFilesBuilder`      | `dist/sitemap.xml` and `dist/robots.txt` (static languages only)                          |
| `PlatformI18nFrontendBuilder`  | client settings of the frontend translator (§3.4); its dictionary builder is no longer used by the render |
| `PlatformThumbnailBuilder`     | project thumbnails from screen masks (GD)                                                |
| `PlatformResumeImageBuilder`   | saves resume images rendered in the browser with pdf.js                                  |

`PlatformComponentRenderer` is one class split into traits: `Loader` (render / value / transitions),
`Config` (component.json cache, name → class), `Assets` (used components, dependency chain, asset tags),
`Class` (loading PHP files of dependencies).

---

## 6. Rendering a page

### 6.1 Preview — `dev/platform/preview/index.php?_id=<id>`

```
engine-bridge.php            ENGINE_PROJECT_ROOT, engine/bootstrap.php, url_path = "/"
platform_load_preview_state  reads data_settings_workflow.json: render flags, compile_scss flags,
                             page / project structure, seo defaults, global paths per type
PlatformScssBuilder::run     when compile_scss_everytime is on – every preview recompiles styles
platform_find_preview_post   search posts → routable collections → taxonomies for the _id
                             not found → render the 404 entry (routing 404_post_id) with status 404
platform_normalize_preview_post   merge data.taxonomy into data, lift data.seo to seo
platform_generate_project_seo     projects without seo get one (title_max / description_max)
platform_render_body_sections     every content block → PlatformComponentRenderer::render
                                  (+ site-wide transition rule when the block has none)
platform_wrap_body_if_needed      optional body_wrapper component from the structure
page structure component          page_constructor by default (structure.page_structure)
platform_write_dist_html          also writes dist/ when render.html_compile is on
```

### 6.2 `page_constructor` — the HTML document

1. SEO meta (implicit fields, entry `seo`), `utility_favicon` (entry `seo.favicon`, else `site_logo_favicon`), share image + `utility_seo_ga`
2. `header`, `page_content` (the body), `footer`, `cookie_notice`, `utility_performance_monitor`
3. `overlay_noise`, `animation_grid_background`, `cursor` (styles only)
4. **asset tags last**, so everything rendered above has been marked as used:
   - dev (`compile_assets` off): one `<link>` / `<script>` per used component and its dependencies,
     `theme` always first so component styles win on equal specificity
   - production (`compile_assets` on): `theme/assets_compiled/css/bundle.css` + `js/bundle.js`
5. `theme_mode_init.js` inlined (sets light / dark before paint)
6. fill `page_constructor/assets/html/template.html`
7. `PlatformHeadHoist::maybe_apply`

### 6.3 Preview vs dist links — `$GLOBALS['render_target']`

- **preview**: links to other pages are `/dev/platform/preview/?_id=<id>`, assets use the url path.
- **dist** (`render_target = 'dist'`, `dist_rel_path = 'project/zozo.html'`): links and asset paths are made
  relative to the file being written (`../`), so `dist/` works from any host or sub folder.
- Output path: `<root folder>/<_id><extension>` when `render.html_compile_folder` is on (pages always at the
  root), otherwise `<_id><extension>`.
- `PlatformPathService::post_link($id)` / `front_page_link()` are the only way components should build page links.

### 6.4 Transitions

- Any block can have `"transition": "fade slide-left"` or an object
  `{ effect, duration, delay, easing, distance, angle, scale, blur, threshold, once, target, stagger, each, parts }`.
- The renderer removes the key from the data and adds `data-transition*` attributes to the first element of the
  component's HTML (no wrapper). `transitions.js` plays them with the Web Animations API on scroll into view
  and respects `prefers-reduced-motion`.
- `data_settings_transitions.json` sets defaults per component for blocks placed in content
  (`{ enabled, defaults, components: { text_block: "fade slide-bottom", … } }`). `"transition": false` on a block
  or `"transition_rules": false` on a container turns them off.

### 6.5 Head hoist (`render.hoist_to_head`)

Moved from `<body>` to the end of `<head>`, in document order:
stylesheet / preload links (head duplicates dropped), `<style>`, `<noscript>` holding only links / styles,
`<script src>` (gets `defer` unless already `async` / `defer` / `module`).
Left in place: inline scripts (they run where printed), JSON / ld+json scripts, anything with `data-no-hoist`.
An inline script with `data-hoist` is moved as it is.

### 6.6 Bulk render — `api/compile-html.php` (Render → HTML Bulk Render)

Runs the same pipeline for every entry of every post type, routable collection and taxonomy, writes
`dist/…` for every static language, removes leftover `dist/<lang>/` and `dist/i18n/<lang>/` of frontend languages (§3.4), then
rebuilds the SEO files when `seo_files.build_on_html_render` is on. Requires `render.html_compile`.

### 6.7 Preloader

`preloader` (dependency of `page_constructor`) prints a full-screen loader at the start of `<body>` and a one-line
head script that adds `html.is-loading` before the first paint (no JavaScript → no loader). `preloader.js` removes
it on window `load` (or `DOMContentLoaded` with `"wait": "dom"`), and waits for `dm:i18n-done` while the frontend
translation runs; `max_ms` always ends it. `window.dmPreloader.show(key)` / `hide(key)` for other scripts.
Options in `data_settings_site.json`: `"preloader": { "enabled": true, "wait": "load", "max_ms": 4000 }` or
`"preloader": false`.

---

## 7. Styles and scripts

- Write styles in `assets/scss/`. `PlatformScssBuilder` compiles every non-partial `.scss` to the matching
  `assets/css/*.css` (compressed). Import paths: the theme SCSS folder and the file's own folder, so any
  component can `@import` theme variables and mixins.
- `compile_scss_everytime`: old CSS in the target folder is deleted first and everything is recompiled on each
  preview / render.
- Compile targets (workflow `compile_scss`): site components, platform components, platform assets, and the
  production bundle (`compile_scss_assets`, forced on when `compile_assets` is on).
- `theme/assets/php/get_font_list.php` scans the theme fonts and regenerates the `@font-face` SCSS before each
  compile (`theme.auto_extract_fonts`).
- The bundle (`PlatformBundleBuilder`): `theme` first, then every component alphabetically, CSS and JS
  concatenated into `src/components/theme/assets_compiled/{css/bundle.css, js/bundle.js}`; relative `url()`s are
  rewritten so fonts and images still resolve from the bundle folder.
- Component JS is plain browser script (no build step, no modules); each file finds its own elements by class /
  `data-` attributes and initialises itself.

---

## 8. Workflow switches — `data_settings_workflow.json`

| section             | keys                                                                                                   |
|---------------------|--------------------------------------------------------------------------------------------------------|
| `render`            | `debug_post_data` (show the entry JSON instead of the body), `hoist_to_head`, `html_compile` (write dist), `html_compile_folder` (use type root folders) |
| `compile_scss`      | `compile_scss_src_components`, `compile_scss_platform_components`, `compile_scss_platform_assets`, `compile_scss_assets`, `compile_assets` (pages use the bundle), `compile_scss_everytime` |
| `live_preview`      | last post type / `_id` opened in Render → Live Preview                                                  |
| `page_structure` / `project_structure` | `header`, `footer`, `page_structure` (document component), `body_wrapper`                 |
| `seo_page` / `seo_project` | `index`, `title_max`, `description_max`, `keywords_source`                                      |
| `translation`       | `default_lang`, `target_langs` (comma separated)                                                       |
| `thumbnails`        | `skip_projects_with_thumbnail`, `overwrite`, `overwrite_if_bigger`, `overwrite_bigger_than_kb`, `quality` |
| `resumes`           | `output_folder`, `width`, `thumbnail_width`, `quality`, `thumbnail_quality`, `page`, `skip_existing`, `update_json` |
| `seo_files`         | `sitemap`, `robots`, `site_url`, `include_noindex`, `front_page_as_root`, `lastmod`, `robots_disallow`, `build_on_html_render` |

---

## 9. The admin panel — `dev/platform`

```
dev/platform/
├─ index.php              shell: ?page=<page>&tab=<tab>
├─ core/
│  ├─ autoload.php        PlatformData, PlatformSettings, svg, cards, vars, topbar, error-renderer, posts helpers
│  ├─ shell.php           platform_asset(), platform_menu(), platform_pages(), menu rendering
│  ├─ PlatformData.php    UI strings from data/strings.json (PlatformData::getString('a.b', 'default'))
│  └─ PlatformSettings.php admin preferences in data/settings.json (listing paging, platform tools)
├─ components/<name>/     admin UI pieces: index.php with platform_render_<name>_fragment($tab)
├─ api/                   JSON endpoints used by the admin JS
├─ preview/               the page preview / render pipeline (§6.1)
├─ assets/                admin scss / css / js / svg icons / fonts / img (thumbnail template)
├─ data/                  strings.json (all UI texts), settings.json (admin preferences)
└─ require/               installers for PHP, scssphp, GD
```

### 9.1 How a page is built

`index.php` reads `?page`, looks it up in `platform_pages()` (`title`, `render` function, `file`), requires the
file and calls the render function with the tab. Components call `platform_asset('css'|'js', $path)` to queue
their files; the shell prints them once. The header is `platform_render_topbar()` (logo, title, "View Frontpage"
button when `front_post_id` is set), the left side is `platform_render_menu(platform_menu(), …)`.

### 9.2 Pages

| page                | what it does                                                                                  |
|---------------------|-----------------------------------------------------------------------------------------------|
| `home`              | dashboard cards + content and settings search (§9.4) + "Compile HTML"                            |
| `content-types`     | content search (§9.4) + overview of post / taxonomy / collection types                         |
| `content`           | listing of one type (`&kind=post&type=project`): search, paging / infinite scroll, edit / view   |
| `edit-posts`        | post editor: fields + content builder (drag blocks, add children, palette of components)        |
| `edit-taxonomies`, `edit-collections` | editors for terms and collection entries                                     |
| `render`            | tabs: Compile SCSS, Live Preview, HTML Bulk Render, SEO Files                                  |
| `generate`          | tabs: Translate, Thumbnails, Resume Images                                                     |
| `settings`          | platform tools (performance monitor) + JSON editor for every data file                         |

The content menu is built from `data_settings_types.json`, so a new content type appears automatically.

### 9.3 API endpoints — `dev/platform/api`

| endpoint                 | methods        | purpose                                                                    |
|--------------------------|----------------|----------------------------------------------------------------------------|
| `posts.php`              | GET/POST/DELETE| list types, list entries of a type, get / save / delete one entry           |
| `content-list.php`       | GET            | rows for the content listing (`?kind=&type=`)                              |
| `content-search.php`     | GET            | every entry of every post / taxonomy / collection type, for the dashboard search |
| `settings-search.php`    | GET            | every value of the settings files and site data files, plus admin pages / tools, for the settings search |
| `types.php`              | GET            | content types                                                              |
| `list-components.php`    | GET            | site components with description and php / html presence                   |
| `json-file.php`          | GET/POST       | read / write any data or settings JSON file (no `..` paths)                |
| `data-files.php`         | GET            | list of data + settings JSON files                                         |
| `validate-files.php`     | GET            | checks every JSON file in a project folder (`?path=`) is valid             |
| `serve-file.php`         | GET            | serves an image / video from `src/content/img` or `vid` (`?type=&path=`)   |
| `workflow.php`           | GET/POST       | read / save workflow sections                                              |
| `platform-settings.php`  | GET/POST       | admin preferences (`listing`, `tools`)                                     |
| `compile-scss.php`       | GET/POST       | run the SCSS build                                                         |
| `compile-html.php`       | POST           | bulk render to dist                                                        |
| `generate-seo-files.php` | POST           | sitemap.xml + robots.txt                                                   |
| `generate-thumbnails.php`| GET/POST       | project thumbnail state / generate one                                     |
| `generate-resumes.php`   | GET/POST       | resume state / save images rendered by pdf.js                              |
| `translate-data.php`     | GET/POST       | language config / run the translation                                      |
| `cards.php`              | GET/POST       | node-graph canvas state (`data/cards.json`)                                |

All endpoints answer JSON (`{ ok, … }`, HTTP status on errors).

### 9.4 Content search

The content search (`components/content-search`, on the dashboard and above the overview on **Content Types**) loads every entry once from
`api/content-search.php` and filters in the browser, so results appear while typing. Each row is the listing row
(`platform_content_row`) plus `kind`, `kind_label`, `type_label`, `description` (seo description / description /
excerpt / summary) and `text` (up to 3000 characters of the entry's readable strings; urls, paths, file names,
colours and keys like `component`, `class`, `img` are left out).

- Every word must match; matches in the id / title rank first, then type and taxonomies, then description, then text.
  Diacritics are ignored (`romania` finds `România`).
- The chips filter by kind (All / Posts / Taxonomies / Collections) and show the counts; at most 60 results are shown.
- `Ctrl+K` or `/` focuses the search, `↑` / `↓` move, `Enter` opens the editor (`Ctrl+Enter` in a new tab),
  `Esc` clears; **View** opens the preview of routable entries.

The settings search next to it uses the same component (`platform_render_search_box`) with `api/settings-search.php`:
one row per file and per value of `data/settings/*.json` and of the default-language data files that are not a content type
(`data_content_*`, `data_types.json`, …), plus the Render / Generate / Settings pages. `Ctrl+J` focuses it. A result opens
**Settings** with `&file=<file>&path=<dot.path>`: the file is loaded, the row (`data-je-path` on every JSON editor row) is
scrolled into view, highlighted and its field focused.

---

## 10. Generators

- **SEO files** – `sitemap.xml` and `robots.txt` in `dist/`, same urls as the bulk render. Leaves out
  `settings.render: false`, the 404 and search pages, `seo.index: false` (unless `include_noindex`) and pages that
  only duplicate another (`utility_duplicate_content`, e.g. `index` → `home`).
- **Search index** – one row per routable entry: title (seo title → title → `_id`), plain-text description,
  dist url, type. Rendered hidden by the `search` component and filtered in the browser.
- **Thumbnails** – for each project with `settings.render`, the website screenshots (or media images) are
  cover-fitted into three tilted screen masks from `dev/platform/assets/img/project_thumbnail`, clipped and topped
  with the overlay → `img/projects/<media.path>/thumbnail/thumbnail.webp`. Needs GD with WebP.
- **Resume images** – the browser renders each resume PDF page with pdf.js
  (`components/generate/assets/vendor/pdfjs`), the API saves the WebP image + thumbnail and updates the resume
  collection entry.
- **Translate** – `PlatformTranslationBuilder` + `PlatformTranslationService`, params in the workflow `translation`
  section (`default_lang`, `target_langs`, `provider`, `model`, `ollama_url`). What gets translated is edited on the
  Translate tab and saved in `dev/platform/data/translations/rules.json`: `skip_keys` (keys kept as they are, with
  everything inside them), `glossary` (words and names kept as written inside any text) and `skip_values` (exact
  texts that are copied) and `overrides` (your own translations). Without the file the defaults are `SKIP_KEYS` and `DEFAULT_GLOSSARY`.
  - Provider `ollama` (default, local, model `translategemma:4b`), `google` (needs `dev/dependencies/google-translate-php`)
    or `copy` (sync the structure only).
  - Files: every `data_[a-z0-9_]*.json` of the default language except `data_settings_*`. The target file always
    follows the source structure; only text values are translated. Keys in `skip_keys` (by default ids, urls,
    classes, dates, tags, types, categories, company and game names, form values…) are copied as they are. Urls,
    paths, file names, e-mails, colours, numbers and slug-like single words are never translated.
  - HTML is split at block tags / line breaks; inline tags and `{{ … }}` / `{name}` placeholders must come back
    unchanged, otherwise the text between them is translated piece by piece. Glossary terms are kept as written.
  - `dev/platform/data/translations/<lang>.cache.json` – every translated text (by source text), so a text is
    translated once for all files and runs are incremental. Texts the model failed on are listed under `failed` and
    stay in English; delete the cache file to translate everything again.
  - `dev/platform/data/translations/<lang>.manifest.json` – per file and value path (`#<_id>` for list items with an
    id) the hash of the source and of the text that was written. A target text that differs from what was written
    is a manual edit and is kept.
  - Without **Overwrite**, existing translations in the target files are never replaced: only missing texts, texts
    still equal to English and machine texts whose English changed are translated. With **Overwrite** every text is
    translated again (a run id makes the cache skip only what this run already did) and replaces the target value.
  - `overrides` in `rules.json` (`{ "ro": { "English text": "my text" } }`, edited as `English => mine` lines) win over
    everything: a value or line equal to the English text (case-insensitive) gets your text, with or without Overwrite.
  - The API (`api/translate-data.php`) works one file at a time in ~20 s steps; the Translate tab loops files and
    steps, shows progress and can stop. The file is written when all its texts are done.

---

## 11. How to …

**Add a site component**
1. `src/components/<name>/component.json` with `name`, `title`, `summary`, `description`, `assets`, `dependencies`.
2. `assets/php/<name>.php` with `class <name> { public static function render(array $data = []): string }`.
3. Optional `assets/html/template.html`, `assets/scss/<name>.scss`, `assets/js/<name>.js`
   (list css as `assets/css/**` and add `theme` to dependencies if it uses theme variables / classes).
4. Use it in content: `{ "component": "<name>", "data": { … } }`. Styles compile on the next preview.

**Add a content type**
Add it to `data_settings_types.json` and create the data file under `data/<lang>/<kind>/`. Labels go in
`data/<lang>/data_types.json`. The admin menu, listing, editor, preview, bulk render, sitemap and search pick it up.

**Add an admin page**
Create `dev/platform/components/<page>/index.php` with `platform_render_<page>_fragment(string $tab): string`,
register it in `platform_pages()` (and `platform_menu()` if it needs a menu entry), put texts in `data/strings.json`.

**Add an API endpoint**
`dev/platform/api/<name>.php`: JSON header, `require_once __DIR__ . '/../core/autoload.php'` (and
`../preview/engine-bridge.php` when it needs the engine), answer `{ ok, … }` with a proper status code.

---

## 12. Conventions

- No comments in code; this README (and each component's `summary` / `description`) is the documentation.
- Generated files – never edit: `dist/**`, `src/components/*/assets/css/*.css` that have an scss source,
  `theme/assets_compiled/**`, the theme `@font-face` SCSS.
- Escape every value with `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` before putting it in a template.
- Build page links with `PlatformPathService::post_link()` and asset urls with its prefixes, never hard-coded.
- Data is read only through `PlatformDataService` (cache + language fallback).
- Component and class names use `snake_case`; admin component folders use `kebab-case`.
