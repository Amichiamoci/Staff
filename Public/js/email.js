/*
CKEDITOR.ClassicEditor
    .create( document.querySelector( '#body-editor' ))
    .then(editor => {
        $('form').on('submit', function (){
            $('#body').val(editor.getData());
        });
    })
*/
CKEDITOR.replace('body');