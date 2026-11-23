(() => {
    const form = document.getElementById('email-compose');
    const textarea = document.getElementById('body');
    const label = document.getElementById('email-body-label');
    const error = document.getElementById('email-body-error');
    let editor;

    if (window.Quill) {
        try {
            const quill = new Quill('#email-editor', {
                theme: 'snow',
                formats: ['header', 'bold', 'italic', 'underline', 'link', 'list', 'blockquote'],
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        ['link', 'blockquote'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['clean'],
                    ],
                },
            });
            quill.setContents(quill.clipboard.convert({ html: textarea.value }));
            quill.root.setAttribute('role', 'textbox');
            quill.root.setAttribute('aria-multiline', 'true');
            quill.root.setAttribute('aria-labelledby', label.id);
            quill.root.setAttribute('aria-describedby', error.id);
            quill.root.setAttribute('aria-required', 'true');
            quill.on('text-change', () => {
                error.hidden = true;
                quill.root.removeAttribute('aria-invalid');
            });
            label.textContent = 'Corpo del messaggio';
            label.removeAttribute('for');
            textarea.required = false;
            textarea.hidden = true;
            document.getElementById('email-editor-wrapper').hidden = false;
            editor = quill;
        } catch (error) {
            console.warn('Editor visuale non disponibile.', error);
        }
    }

    form.addEventListener('submit', event => {
        if (editor) {
            if (!editor.getText().trim()) {
                event.preventDefault();
                error.hidden = false;
                editor.root.setAttribute('aria-invalid', 'true');
                editor.focus();
                return;
            }
            // Quill serializes ordinary spaces as nonbreaking spaces, which prevents email text wrapping.
            textarea.value = editor.getSemanticHTML().replaceAll('&nbsp;', ' ');
        }

        if (!window.confirm("Inviare l'email al destinatario indicato?")) {
            event.preventDefault();
            return;
        }

        form.querySelector('button[type="submit"]').disabled = true;
    });
    window.addEventListener('pageshow', () => {
        form.querySelector('button[type="submit"]').disabled = false;
    });
})();
