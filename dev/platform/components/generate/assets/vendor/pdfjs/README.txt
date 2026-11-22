pdf.js (pdfjs-dist 6.2.108, Apache-2.0) - https://github.com/mozilla/pdf.js
Used by generate-resumes.js to render resume PDFs to WebP in the browser.
The files are the legacy ES module builds (legacy/build/pdf.min.mjs, legacy/build/pdf.worker.min.mjs, with polyfills for current browsers) renamed to .js,
so PHP's built-in server sends them with a JavaScript MIME type.
