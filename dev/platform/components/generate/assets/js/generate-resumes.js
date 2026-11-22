const PDFJS_DIR = 'components/generate/assets/vendor/pdfjs/';

function resumesResolveUrl(path) {
  const href = window.location.href;
  const qIdx = href.indexOf('?');
  const base = qIdx === -1 ? href : href.substring(0, qIdx);
  const lastSlash = base.lastIndexOf('/');
  const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
  return dir + path.replace(/^\//, '');
}

let pdfjsPromise = null;

function loadPdfjs() {
  if (!pdfjsPromise) {
    pdfjsPromise = import(resumesResolveUrl(PDFJS_DIR + 'pdf.min.js')).then((pdfjs) => {
      pdfjs.GlobalWorkerOptions.workerSrc = resumesResolveUrl(PDFJS_DIR + 'pdf.worker.min.js');
      return pdfjs;
    });
    pdfjsPromise.catch(() => {
      pdfjsPromise = null;
    });
  }
  return pdfjsPromise;
}

function resizeCanvas(source, width) {
  let current = source;
  while (current.width / 2 >= width) {
    const half = document.createElement('canvas');
    half.width = Math.round(current.width / 2);
    half.height = Math.round(current.height / 2);
    const ctx = half.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(current, 0, 0, half.width, half.height);
    current = half;
  }
  const out = document.createElement('canvas');
  out.width = width;
  out.height = Math.max(1, Math.round((current.height * width) / current.width));
  const ctx = out.getContext('2d');
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';
  ctx.drawImage(current, 0, 0, out.width, out.height);
  return out;
}

function canvasToWebp(canvas, quality) {
  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => {
        if (!blob || blob.type !== 'image/webp') {
          reject(new Error('this browser cannot encode WebP (use Chrome or Edge)'));
          return;
        }
        const reader = new FileReader();
        reader.onload = () => resolve({ dataUri: reader.result, bytes: blob.size, width: canvas.width, height: canvas.height });
        reader.onerror = () => reject(reader.error || new Error('could not read the image'));
        reader.readAsDataURL(blob);
      },
      'image/webp',
      Math.max(0.01, Math.min(1, quality / 100))
    );
  });
}

async function renderResumePdf(url, settings) {
  const pdfjs = await loadPdfjs();
  const dir = resumesResolveUrl(PDFJS_DIR);
  const task = pdfjs.getDocument({
    url,
    wasmUrl: dir + 'wasm/',
    iccUrl: dir + 'iccs/',
    standardFontDataUrl: dir + 'standard_fonts/',
  });
  const doc = await task.promise;
  try {
    const pageNr = Math.min(Math.max(1, settings.page || 1), doc.numPages);
    const page = await doc.getPage(pageNr);
    const base = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: (settings.width * 2) / base.width });

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(viewport.width);
    canvas.height = Math.round(viewport.height);
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    await page.render({ canvas, canvasContext: ctx, viewport, background: '#ffffff' }).promise;

    const full = resizeCanvas(canvas, settings.width);
    const thumb = resizeCanvas(full, settings.thumbnail_width);
    return {
      image: await canvasToWebp(full, settings.quality),
      thumbnail: await canvasToWebp(thumb, settings.thumbnail_quality),
    };
  } finally {
    task.destroy();
  }
}

window.platformRenderResumePdf = renderResumePdf;

document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('[data-generate-panel-resumes]');
  if (!panel) return;

  const MISSING = '__missing__';
  const ALL = '__all__';
  const select = panel.querySelector('[data-resumes-select]');
  const statusEl = panel.querySelector('[data-resumes-status]');
  const resultsEl = panel.querySelector('[data-resumes-results]');
  const warningsEl = panel.querySelector('[data-resumes-warnings]');
  const previewBtn = panel.querySelector('[data-resumes-action="preview"]');
  const generateBtn = panel.querySelector('[data-resumes-action="generate"]');
  const stopBtn = panel.querySelector('[data-resumes-action="stop"]');

  let state = { resumes: [], settings: {} };
  let stopRequested = false;

  async function apiJson(path, options) {
    const res = await fetch(resumesResolveUrl(path), Object.assign({ headers: { Accept: 'application/json' } }, options));
    const text = await res.text();
    let data = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = null;
    }
    if (!data) {
      const plain = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
      throw new Error(plain ? plain.slice(0, 300) : `Invalid response (${res.status})`);
    }
    if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
  }

  const pdfUrl = (r) => resumesResolveUrl('../../' + r.pdf);
  const isDone = (r) => r.has_image && r.has_thumbnail && r.json_up_to_date;
  const missing = () => state.resumes.filter((r) => !isDone(r));

  function label(r) {
    const mark = isDone(r) ? ' ✓' : r.has_image && r.has_thumbnail ? ' (json paths differ)' : '';
    const info = [r.lang, r.description].filter(Boolean).join(', ');
    return `${r._id}${mark}${info ? ' (' + info + ')' : ''}`;
  }

  function warn(messages) {
    warningsEl.innerHTML = '';
    messages.forEach((msg) => {
      const w = document.createElement('div');
      w.className = 'platform-generate-result-item is-error';
      w.textContent = msg;
      warningsEl.appendChild(w);
    });
  }

  async function load(prefix = '') {
    statusEl.textContent = 'Loading resumes...';
    try {
      const data = await apiJson('api/generate-resumes.php');
      state = data;
      warn(data.resumes.filter((r) => !r.has_pdf).map((r) => `${r._id}: PDF not found (${r.pdf || 'data.media.pdf is empty'})`));

      const selected = select.value;
      select.innerHTML = '';
      const add = (value, text) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        select.appendChild(opt);
      };
      add(MISSING, `Resumes without images (${missing().length})`);
      add(ALL, `All resumes (${data.resumes.length})`);
      data.resumes.forEach((r) => add(r._id, label(r)));
      if (selected && [...select.options].some((o) => o.value === selected)) select.value = selected;

      const s = data.settings;
      const summary = `${data.resumes.length} resumes in ${data.collection_file}, ${missing().length} without images · ${s.width}px + ${s.thumbnail_width}px thumbnail → img/${s.output_folder}/`;
      statusEl.textContent = prefix ? `${prefix} · ${summary}` : summary;
      updateButtons();
    } catch (e) {
      statusEl.textContent = 'Error loading resumes: ' + e.message;
    }
  }

  function chosen() {
    const v = select.value;
    if (v === MISSING) return missing();
    if (v === ALL) return state.resumes.slice();
    return state.resumes.filter((r) => r._id === v);
  }

  function updateButtons() {
    previewBtn.disabled = [MISSING, ALL].includes(select.value);
  }

  function setRunning(running) {
    stopBtn.style.display = running ? '' : 'none';
    generateBtn.disabled = running;
    previewBtn.disabled = running || [MISSING, ALL].includes(select.value);
    select.disabled = running;
  }

  const kb = (bytes) => `${(bytes / 1024).toFixed(1)} KB`;

  function addResult(r) {
    const item = document.createElement('figure');
    item.className = 'platform-generate-thumb' + (r.error ? ' is-error' : r.skipped ? ' is-skipped' : '');
    if (r.thumbSrc) {
      const link = document.createElement('a');
      link.href = r.imageSrc || r.thumbSrc;
      link.target = '_blank';
      link.rel = 'noopener';
      const img = document.createElement('img');
      img.src = r.thumbSrc;
      img.alt = r._id;
      link.appendChild(img);
      item.appendChild(link);
    }
    const cap = document.createElement('figcaption');
    const title = document.createElement('strong');
    title.textContent = r._id;
    cap.appendChild(title);
    (r.lines || []).forEach((line) => {
      const span = document.createElement('span');
      span.textContent = line;
      span.title = line;
      cap.appendChild(span);
    });
    item.appendChild(cap);
    resultsEl.prepend(item);
  }

  async function render(r) {
    if (!r.has_pdf) throw new Error('PDF not found: ' + (r.pdf || 'data.media.pdf is empty'));
    return renderResumePdf(pdfUrl(r), state.settings);
  }

  async function runPreview() {
    const list = chosen();
    if (list.length !== 1) return;
    const r = list[0];
    setRunning(true);
    statusEl.textContent = `Rendering ${r._id}...`;
    try {
      const out = await render(r);
      addResult({
        _id: r._id,
        thumbSrc: out.thumbnail.dataUri,
        imageSrc: out.image.dataUri,
        lines: [
          `preview · ${out.image.width}×${out.image.height} · ${kb(out.image.bytes)}`,
          `thumbnail ${out.thumbnail.width}×${out.thumbnail.height} · ${kb(out.thumbnail.bytes)}`,
        ],
      });
      statusEl.textContent = 'Preview ready (nothing was saved, click the thumbnail for the full image)';
    } catch (e) {
      addResult({ _id: r._id, error: true, lines: [e.message] });
      statusEl.textContent = 'Preview failed: ' + e.message;
    } finally {
      setRunning(false);
    }
  }

  async function runGenerate() {
    const list = chosen();
    if (!list.length) {
      statusEl.textContent = 'Nothing to generate.';
      return;
    }
    const single = ![MISSING, ALL].includes(select.value);
    const overwrite = single ? true : null;

    stopRequested = false;
    setRunning(true);
    let done = 0;
    let saved = 0;
    let skipped = 0;
    let errors = 0;
    for (const r of list) {
      if (stopRequested) break;
      statusEl.textContent = `Generating ${done + 1}/${list.length} · ${r._id}`;
      try {
        let res;
        if (!single && state.settings.skip_existing && r.has_image && r.has_thumbnail) {
          res = await apiJson('api/generate-resumes.php', { method: 'POST', body: JSON.stringify({ _id: r._id }) });
        } else {
          const out = await render(r);
          res = await apiJson('api/generate-resumes.php', {
            method: 'POST',
            body: JSON.stringify({ _id: r._id, image: out.image.dataUri, thumbnail: out.thumbnail.dataUri, overwrite }),
          });
          if (res.success) res.sizes = out;
        }
        if (!res.success) throw new Error(res.message || 'error');

        const v = Date.now();
        const src = (file) => resumesResolveUrl('../../' + file) + '?v=' + v;
        const lines = res.skipped
          ? [res.message, res.json_updated ? 'JSON paths OK' : '']
          : [
              `${res.image} · ${res.sizes ? res.sizes.image.width + '×' + res.sizes.image.height + ' · ' : ''}${kb(res.bytes.image)}`,
              `${res.thumbnail} · ${kb(res.bytes.thumbnail)}`,
              res.json_updated === null ? 'JSON not updated (update_json is off)' : res.json_updated ? 'JSON media paths updated' : 'JSON NOT updated',
            ];
        if (res.skipped) skipped++;
        else saved++;
        addResult({ _id: r._id, skipped: res.skipped, thumbSrc: src(res.thumbnail), imageSrc: src(res.image), lines: lines.filter(Boolean) });
      } catch (e) {
        errors++;
        addResult({ _id: r._id, error: true, lines: [e.message] });
      }
      done++;
    }
    const summary = `${stopRequested ? 'Stopped' : 'Done'}: ${saved} saved, ${skipped} skipped, ${errors} errors (${done}/${list.length})`;
    statusEl.textContent = summary;
    setRunning(false);
    load(summary);
  }

  select.addEventListener('change', updateButtons);
  previewBtn.addEventListener('click', runPreview);
  generateBtn.addEventListener('click', runGenerate);
  stopBtn.addEventListener('click', () => {
    stopRequested = true;
    statusEl.textContent = 'Stopping after the current resume...';
  });

  const root = document.querySelector('[data-generate-root]');
  if (root && root.dataset.generateTab === 'resumes') load();
});
