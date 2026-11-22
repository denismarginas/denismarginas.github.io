let epRoot, postTypesEl, postListWrap, postListEl, searchInput,
    editorEl, postTitleEl, postIdEl, fieldsContainer, contentListEl,
    paletteEl, paletteListEl, paletteSearchInput;
let routableWrap, routableInput;

let postTypes = [];
let posts = [];
let components = [];
let currentPostType = '';
let currentPost = null;
let currentFile = '';
let originalPostId = '';
let paletteVisible = false;
let jsonEditorInstance = null;

let componentsApiUrl;
