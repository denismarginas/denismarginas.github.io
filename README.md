# Portfolio CMS

Denis Marginas' portfolio: a small file-based CMS and static site generator in plain PHP.
Content is JSON, pages are trees of reusable **components**, and the output is static HTML in `dist/`
(published to GitHub Pages: https://denismarginas.github.io/portfolio/).

The code has no comments on purpose. This file is the starting point; the full reference
(every setting, generator and endpoint) is [`dev/platform/README.md`](dev/platform/README.md).

---

## Run it

```
start.bat
```

Installs what is missing (local PHP 8.5 + GD, scssphp, Ollama when translation uses it), adds
`portfolio-cms.local` to the hosts file, starts `php -S 127.0.0.1:8088` on the project root and opens
the admin at `http://portfolio-cms.local:8088/dev/platform/` (falls back to `127.0.0.1:8088`).

| URL                                   | what                          |
|---------------------------------------|-------------------------------|
| `/dev/platform/`                      | admin panel ("Portfolio CMS") |
| `/dev/platform/preview/?_id=<id>`     | live preview of one page      |
| `/dev/platform/preview/?_id=<id>&lang=ro` | preview in another language |

---

## Folders

```
src/components/<name>/      site components (one folder each)
src/components/theme/       global SCSS, fonts, compiled bundle
src/content/json/data/
  settings/                 site-wide settings (data_settings_*.json)
  <lang>/                   content per language (en = default, ro, …)
    post/  taxonomy/  collection/  content/
src/content/img|vid|pdf|svg media
dev/engine/                 renderer (no UI)
dev/platform/               admin panel, preview, API, generators
dist/                       generated site – never edit by hand
```

---

## How a page is made

```
JSON entry  ──►  content: [ {component, data, children}, … ]
             ──►  each block → PlatformComponentRenderer::render(name, data)
             ──►  page_constructor wraps it (head, header, footer, assets)
             ──►  preview in the browser, or written to dist/<root>/<_id>.html
```

An entry (a page, project, category…):

```json
{
  "_id": "about",
  "data": { "seo": { "title": "…", "description": "…" }, "taxonomy": { "category": ["…"] } },
  "content": [
    { "component": "section", "data": { "variant": "panel" }, "children": [
      { "component": "text_block", "data": { "title": "…" } }
    ] }
  ],
  "settings": { "render": true }
}
```

- `_id` = file name (`about` → `about.html`).
- Content types (`post`, `taxonomy`, `collection`) and their output folders are defined in
  `settings/data_settings_types.json`. Adding a type there adds it to the admin, preview, render and sitemap.
- Any block can have `"render": false` (skipped) or `"transition": "fade slide-left"` (entrance animation).

---

## Components

```
src/components/button/
├─ component.json          name, title, summary, description, assets, dependencies
└─ assets/
   ├─ php/button.php       class button { public static function render(array $data = []): string }
   ├─ html/template.html   markup with {{ placeholders }}
   ├─ scss/*.scss          styles (files starting with _ are partials)
   ├─ css/*.css            compiled – do not edit
   └─ js/*.js              plain browser script, initialises itself
```

Rules:

- Class name = folder name. `render()` returns HTML; `value()` returns data (utility components).
- Render other components with `PlatformComponentRenderer::render('name', $data)`, never `require`.
- Fill templates with `PlatformTemplateRenderer::render([...])` and escape values first
  (`htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`).
- Page links: `PlatformPathService::post_link($id)`. Data: `PlatformDataService` (has language fallback).
- `component.json` `summary` / `description` document the component's options – keep them up to date.
- `dependencies` load other components' PHP / CSS / JS together with this one.

Every component gets `post_current_data` (the entry being rendered), the global media paths and `children`.
Containers (`section`, `columns`, …) pass them on – see `section::render_children()`.

Cursor: use the custom cursor variables, e.g. `cursor: var(--cursor-pointer, pointer);`
(`pointer`, `zoom-in`, `zoom-out`, `grab`, `text`, `not-allowed`, `view`, `play`, …).

---

## Styles and scripts

- Write SCSS only; the admin compiles it (`Render → Compile SCSS`, or automatically on preview when
  `compile_scss_everytime` is on). Any component can `@import "theme";` for variables and mixins.
- Production uses one bundle (`theme/assets_compiled/css/bundle.css`, `js/bundle.js`) when
  `compile_assets` is on; in development each used component loads its own files.

---

## Admin panel (`dev/platform/`)

| page        | what                                                    |
|-------------|---------------------------------------------------------|
| Home        | dashboard, content overview                             |
| Content     | listing and editors for posts, taxonomies, collections  |
| Render      | Compile SCSS, Live Preview, HTML Bulk Render, SEO files |
| Generate    | Translate, project thumbnails, resume images            |
| Settings    | JSON editor for every data / settings file              |

Build switches live in `settings/data_settings_workflow.json` (render, compile, translation, SEO files…).

---

## Languages

- `en` is the default. Other languages live in `data/<lang>/` with the same file structure.
- **Generate → Translate** fills them from English (local Ollama by default); manual edits are kept.
- Per language output mode: `static` (full HTML copy in `dist/<lang>/`) or `frontend`
  (translated in the browser from JSON).

---

## Publishing

1. `Render → HTML Bulk Render` writes every page to `dist/` (plus `sitemap.xml`, `robots.txt`).
2. Push to `main`: the GitHub Action `.github/workflows/static.yml` deploys `dist/` to GitHub Pages.

---

## Conventions

- No comments in code. Explanations go here, in `dev/platform/README.md`, or in `component.json`.
- Never edit generated files: `dist/**`, compiled `css/*.css`, `theme/assets_compiled/**`, the font-face SCSS.
- `snake_case` for site components and classes, `kebab-case` for admin components.
- Text shown to visitors comes from JSON, not from PHP, so it can be translated.

## Quick how-to

- **New component** – create the folder + `component.json` + `assets/php/<name>.php`, then use
  `{ "component": "<name>", "data": { … } }` in any content. Styles compile on the next preview.
- **New page** – add an entry to `data/en/post/data_post_page.json` (or via the admin), translate, render.
- **New admin page** – `dev/platform/components/<page>/index.php` with
  `platform_render_<page>_fragment(string $tab)`, register it in `platform_pages()` in `core/shell.php`.
- **New API endpoint** – `dev/platform/api/<name>.php`, answer JSON `{ ok, … }`.
