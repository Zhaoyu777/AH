import notify from 'common/notify';

let validator = $('#custom-course-chapter-form').validate({
  ajax: true,
  rules: {
    title: {
      required: true,
      maxlength: 100
    }
  },
  submitSuccess(data) {
    notify('success',Translator.trans('提交成功'));
    window.location.reload();
  }
})

$('#submit-btn').on('click', function() {
  if(validator.form()) {
    $(this).button('loading');
    $('#custom-course-chapter-form').submit();
  }
})