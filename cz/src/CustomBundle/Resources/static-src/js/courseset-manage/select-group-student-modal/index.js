import blurSelect from '../../../common/blur-select';

let blurStu = new blurSelect();
blurStu.init();

$('.js-btn-save').on('click', (e) => {
  $("#submit").click();
});

inItStep2form();

function inItStep2form() {
  const $step2_form = $('#group-form');
  let validator = $step2_form.data('validator');
  validator = $step2_form.validate({
    rules: {
      title: {
        maxlength: 40,
        course_title: true,
      }
    },
    submitHandler: function(e) {
      var $form = $('#group-form'),
          $title = $('input[name=title]'),
          $target = $(e.currentTarget);
      if (blurStu.getStudentIds().length != 0) {
        $target.button('loading').addClass('disabled');
        $.post($form.attr('action'), {title: $title.val(),memberIds: blurStu.getStudentIds()}, () => {
          location.reload();
        })
      } else {
        $('.js-help-block').fadeIn('slow');
        $('.js-help-block').fadeOut(2000);
      }
      return false;
    },
    invalidHandler: function(form, validator) {return false;}
  });
}