(function () {
    'use strict';

    var formEl = document.getElementById('complaint-letter-form');
    var textareaEl = document.getElementById('complaint_body');
    if (!formEl || !textareaEl) {
        return;
    }

    var editorWrap = document.querySelector('.cl-complaint-editor-wrap');
    var errorEl = document.getElementById('complaint-body-error');
    var editorReady = false;

    function clearValidation() {
        if (editorWrap) {
            editorWrap.classList.remove('is-invalid');
        }
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    function getPlainText(html) {
        return String(html || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function syncEditorHeight(editor) {
        if (!editor || !editor.getBody()) {
            return;
        }
        var body = editor.getBody();
        var doc = editor.getDoc();
        if (!doc || !doc.documentElement) {
            return;
        }
        body.style.overflowY = 'auto';
        body.style.minHeight = '360px';
        body.style.height = 'auto';
        doc.documentElement.style.overflowY = 'auto';
        editor.execCommand('mceAutoResize', false, null, { skip_focus: true });
    }

    function initEditor() {
        if (!window.tinymce) {
            return;
        }

        tinymce.init({
            selector: '#complaint_body',
            license_key: 'gpl',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.0',
            suffix: '.min',
            width: '100%',
            menubar: false,
            statusbar: true,
            branding: false,
            promotion: false,
            resize: true,
            min_height: 380,
            max_height: 560,
            autoresize_bottom_margin: 24,
            autoresize_overflow_padding: 16,
            convert_urls: false,
            relative_urls: false,
            paste_data_images: true,
            plugins: 'lists advlist autolink link image table charmap preview anchor searchreplace wordcount autoresize',
            toolbar: [
                'undo redo | bold italic underline strikethrough | fontsize fontfamily | alignleft aligncenter alignright alignjustify',
                'numlist bullist | outdent indent | removeformat | link image table charmap | fullscreen preview'
            ],
            font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 24pt 36pt',
            font_family_formats: 'Times New Roman=Times New Roman,Times,serif; Arial=Arial,Helvetica,sans-serif; Helvetica=Helvetica,Arial,sans-serif; Courier New=Courier New,Courier,monospace',
            block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3',
            content_style: [
                'body {',
                '  font-family: "Times New Roman", Times, serif;',
                '  font-size: 12pt;',
                '  line-height: 1.6;',
                '  color: #212529;',
                '  margin: 0;',
                '  padding: 12px 14px;',
                '  overflow-y: auto;',
                '  box-sizing: border-box;',
                '}',
                'p { margin: 0 0 0.75rem; }',
                'ul, ol { margin: 0 0 0.75rem; padding-left: 1.5rem; }',
                'table { border-collapse: collapse; width: 100%; }',
                'td, th { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }'
            ].join('\n'),
            setup: function (editor) {
                editor.on('init', function () {
                    editorReady = true;
                    textareaEl.classList.add('d-none');
                    syncEditorHeight(editor);
                    window.setTimeout(function () {
                        syncEditorHeight(editor);
                    }, 100);
                });
                editor.on('change keyup undo redo setcontent input paste NodeChange', function () {
                    editor.save();
                    clearValidation();
                    syncEditorHeight(editor);
                });
                editor.on('FullscreenStateChanged', function () {
                    syncEditorHeight(editor);
                });
            }
        });
    }

    formEl.addEventListener('submit', function (evt) {
        var editor = window.tinymce ? tinymce.get('complaint_body') : null;
        if (editor && editorReady) {
            editor.save();
            if (!getPlainText(editor.getContent())) {
                evt.preventDefault();
                if (editorWrap) {
                    editorWrap.classList.add('is-invalid');
                }
                if (errorEl) {
                    errorEl.style.display = 'block';
                }
                editor.focus();
                return;
            }
        } else if (!getPlainText(textareaEl.value)) {
            evt.preventDefault();
            if (editorWrap) {
                editorWrap.classList.add('is-invalid');
            }
            if (errorEl) {
                errorEl.style.display = 'block';
            }
            textareaEl.focus();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
})();
