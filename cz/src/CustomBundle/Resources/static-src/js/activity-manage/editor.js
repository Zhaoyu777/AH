export const initEditor = ($item, validator) => {
  var editor = CKEDITOR.replace("text-content-field", {
    toolbar: "Task",
    filebrowserImageUploadUrl: $("#text-content-field").data("imageUploadUrl"),
    filebrowserFlashUploadUrl: $("#text-content-field").data("flashUploadUrl"),
    allowedContent: true,
    height: 280,
    contentsCss: ".cke_editable{font-size: 34px}",
    fontSize_defaultLabel: '34px'
  });

  editor.on("change", () => {
    $item.val(editor.getData());
    if (validator) {
      validator.form();
    }
  });

  //fix ie11 中文输入
  editor.on("blur", () => {
    $item.val(editor.getData());
    if (validator) {
      validator.form();
    }
  });

  return editor;
};