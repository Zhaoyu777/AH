let $step2_form = $('#message-search-form');

function inItStep2form() {
    console.log(1);
  var validator = this.$step2_form.validate({
    onkeyup: false,
    rules: {
      title: {
        maxlength: 10,
        course_title: true,
      },
      truename: {
        maxlength: 10,
        course_title: true,
      },
    },
    messages: {
      description: Translator.trans("activity.homework_manage.question_homework_hint"),
      questionLength: Translator.trans("搜索字符大于100"),
    },
  });
  this.validator2 = validator;
  this.initCkeditor(validator);
  this.$step2_form.data('validator', validator);

}

inItStep2form();