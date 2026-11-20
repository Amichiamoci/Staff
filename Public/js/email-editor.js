(() => {
    const textarea = document.getElementById('body');
    if (!window.CKEDITOR) {
        return;
    }

    const { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Link, List } = window.CKEDITOR;
    ClassicEditor.create({
        attachTo: textarea,
        licenseKey: textarea.dataset.editorLicenseKey,
        language: 'it',
        plugins: [Essentials, Paragraph, Heading, Bold, Italic, Link, List],
        toolbar: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList'],
    }).then(editor => {
        const error = document.getElementById('email-body-error');
        const editable = editor.ui.getEditableElement();
        textarea.required = false;
        editable.setAttribute('aria-required', 'true');
        editable.setAttribute('aria-describedby', error.id);

        editor.model.document.on('change:data', () => {
            editor.updateSourceElement();
            error.hidden = true;
            editable.removeAttribute('aria-invalid');
        });

        // The existing confirmation modal submits through jQuery.
        $(textarea.form).on('submit', event => {
            editor.updateSourceElement();
            if (!textarea.value.trim()) {
                event.preventDefault();
                error.hidden = false;
                editable.setAttribute('aria-invalid', 'true');
                editor.editing.view.focus();
            }
        });
    }).catch(() => {
        console.warn('CKEditor non disponibile. Il campo HTML rimane attivo.');
    });
})();
