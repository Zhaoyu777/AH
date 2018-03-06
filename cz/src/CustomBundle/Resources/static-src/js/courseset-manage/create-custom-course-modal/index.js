import notify from 'common/notify';

let validator = $('#custom-course-set-create-form').validate({
  ajax: true,
  rules: {
    title: {
      required: true,
      maxlength: 100
    },
    termCode: 'required',
    lessonCount: {
      required: true,
      positive_integer: true,
      maxlength: 4
    },
    courseTitle: {
      required: true,
      maxlength: 100,
    }
  },
  submitSuccess(data) {
    notify('success',Translator.trans('创建成功'));
    window.location.reload();
  }
})

$('#submit-btn').on('click', function() {
  if(validator.form()) {
    $(this).button('loading');
    $('#custom-course-set-create-form').submit();
  }
})