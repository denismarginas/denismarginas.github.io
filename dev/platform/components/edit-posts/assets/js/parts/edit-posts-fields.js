function renderFields() {
  fieldsContainer.innerHTML = '';
  const post = currentPost;
  const dataObj = post.data || {};

  const generalFields = ['title', 'description'].filter(k => k in dataObj);
  addFieldGroup('General', generalFields, dataObj, post);

  const taxonomyObj = dataObj.taxonomy || {};
  const taxFields = Object.keys(taxonomyObj).length ? Object.keys(taxonomyObj) : [];
  if (taxFields.length) {
    addFieldGroup('Taxonomy', taxFields, dataObj, post, 'taxonomy');
  }

  if (dataObj.date) {
    addDateFields(dataObj.date);
  }

  if (dataObj.project) {
    addFieldGroup('Project', Object.keys(dataObj.project), dataObj, post, 'project');
  }

  if (dataObj.web) {
    addFieldGroup('Web', Object.keys(dataObj.web), dataObj, post, 'web');
  }

  if (dataObj.media) {
    addFieldGroup('Media', Object.keys(dataObj.media), dataObj, post, 'media');
  }

  const seoObj = post.data?.seo || post.seo;
  if (seoObj) {
    addFieldGroup('SEO', Object.keys(seoObj), null, post, 'seo');
  }
}

function getFieldVal(key, dataObj, post, prefix) {
  if (prefix) {
    if (prefix === 'seo') return post.data?.seo?.[key] ?? post.seo?.[key] ?? '';
    if (typeof dataObj[prefix] === 'object' && dataObj[prefix] !== null) return dataObj[prefix][key] ?? '';
    return '';
  }
  if (dataObj) return dataObj[key] ?? '';
  return '';
}

function addFieldGroup(title, keys, dataObj, post, prefix) {
  const group = document.createElement('div');
  group.className = 'platform-ep-field-group';
  group.innerHTML = `<div class="platform-ep-field-group-title">${escapeHtml(title)}</div>`;

  keys.forEach(key => {
    const val = getFieldVal(key, dataObj, post, prefix);
    const row = makeFieldRow(key, val, dataObj, post, prefix);
    group.appendChild(row);
  });

  fieldsContainer.appendChild(group);
}

function addDateFields(dateObj) {
  const group = document.createElement('div');
  group.className = 'platform-ep-field-group';
  group.innerHTML = `<div class="platform-ep-field-group-title">Date</div>`;

  Object.keys(dateObj).forEach(key => {
    const label = document.createElement('label');
    label.className = 'platform-form-label';
    label.textContent = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

    const input = document.createElement('input');
    input.className = 'platform-ep-input';
    input.style.width = '100%';
    input.value = dateObj[key] ?? '';
    input.dataset.fieldKey = key;
    input.dataset.fieldPrefix = 'date';

    input.addEventListener('change', () => {
      currentPost.data.date[input.dataset.fieldKey] = input.value;
      markDirty();
    });

    const row = document.createElement('div');
    row.className = 'platform-form-row';
    row.appendChild(label);
    row.appendChild(input);
    group.appendChild(row);
  });

  fieldsContainer.appendChild(group);
}
