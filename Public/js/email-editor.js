(($) => {
    if (!$) {
        return;
    }

    const $form = $('#email-compose');
    const $textarea = $('#body');
    const $label = $('#email-body-label');
    const $error = $('#email-body-error');
    const $submit = $form.find('button[type="submit"]');
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
            quill.setContents(quill.clipboard.convert({ html: $textarea.val() }));
            const $editable = $(quill.root).attr({
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-labelledby': $label.attr('id'),
                'aria-describedby': $error.attr('id'),
                'aria-required': 'true',
            });
            quill.on('text-change', () => {
                $error.prop('hidden', true);
                $editable.removeAttr('aria-invalid');
            });
            $label.text('Corpo del messaggio').removeAttr('for');
            $textarea.prop({ required: false, hidden: true });
            $('#email-editor-wrapper').prop('hidden', false);
            editor = quill;
        } catch (error) {
            console.warn('Editor visuale non disponibile.', error);
        }
    }

    $form.on('submit', event => {
        if (event.isDefaultPrevented() || ($.fn.valid && !$form.valid())) {
            event.preventDefault();
            return;
        }

        if (editor) {
            if (!editor.getText().trim()) {
                event.preventDefault();
                $error.prop('hidden', false);
                $(editor.root).attr('aria-invalid', 'true');
                editor.focus();
                return;
            }
            // Quill serializes ordinary spaces as nonbreaking spaces, which prevents email text wrapping.
            $textarea.val(editor.getSemanticHTML().replaceAll('&nbsp;', ' '));
        }

        if (!window.confirm("Inviare l'email al destinatario indicato?")) {
            event.preventDefault();
            return;
        }

        $submit.prop('disabled', true);
    });
    $(window).on('pageshow', () => {
        $submit.prop('disabled', false);
    });
})(window.jQuery);
