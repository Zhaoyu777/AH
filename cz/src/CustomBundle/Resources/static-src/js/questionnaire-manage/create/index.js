let $form = $('#questionnaire-form');

let validator = $form.validate({
  onkeyup: false,
  rules: {
    title: {
      required: true,
    },
  },
  messages: {
    title: {
      remote: Translator.trans('请输入标题')
    }
  }
})
