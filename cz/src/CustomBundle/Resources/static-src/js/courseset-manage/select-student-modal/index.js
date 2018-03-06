import blurSelect from '../../../common/blur-select';

let blurStu = new blurSelect();
blurStu.init();

$('.js-btn-save').on('click', (e) => {
  let $form = $('#teachers-form'),
      $title = $('input[name=title]'),
      $target = $(e.currentTarget);
      if (blurStu.getStudentIds().length != 0) {
        $target.button('loading').addClass('disabled');
        $.post($form.attr('action'), {ids: blurStu.getStudentIds()}, () => {
          location.reload();
        })
      } else {
        $('.js-help-block').fadeIn('slow');
        $('.js-help-block').fadeOut(2000);
      }
});