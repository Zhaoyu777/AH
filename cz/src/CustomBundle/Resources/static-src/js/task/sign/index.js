import notify from 'common/notify';
import SignPush from './sign-push';

let signPush = new SignPush();
signPush.init();

(function startSign() {
  $('.js-start-sign-btn').on('click', (e) => {
    let $target = $(e.currentTarget);
    $.post($target.data('url'), (response) => {
      if (response.success == 'error') {
        notify('warning', response.message);
      }
    }).fail(function (response) {
      console.log(response);
      notify('danger',Translator.trans(response.responseJSON.error.message));
    });
  });
}());

(function signEndClick() {
  $('.js-sign-end').on('click', (e) => {
    let $target = $(e.currentTarget);
    if (!confirm('确定结束本次签到？')) {
      return;
    };

    $.post($target.data('url'), { signInId: $('input[name=signInId]').val() });
  })

}());

(function signCancelClick() {
  $('.js-sign-cancel').on('click', (e) => {
    let $target = $(e.currentTarget);
    if (!confirm('确定取消本次签到？取消将清除之前的签到记录。')) {
      return;
    };

    $.post($target.data('url'), { signInId: $('input[name=signInId]').val() });
  })
}());