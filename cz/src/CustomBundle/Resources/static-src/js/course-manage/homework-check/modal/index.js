import notify from 'common/notify';
class teacherReviewModal {
  init() {
    this.initFormVerify();
    this.initPreview();
  }
  initFormVerify() {
    let $form = $('#js-teacher-review');
    let validator = $form.validate({
      onkeyup: false,
      currentDom:'#js-teacher-review',
      rules: {
        comment: {
          maxlength: 100,
        }
      },
    });

    $('#js-teacher-review-save').click(function(event) {
      if(validator.form()) {
       $.post($('#js-teacher-review').attr('action'), $('#js-teacher-review').serialize(), () => {
          notify('success',Translator.trans('批阅成功'));
          $('#modal').modal('hide');
          location.reload();
       })
      }
    });
  }
  initPreview() {
    var playerDiv = $('#material-preview-player');
    var url = playerDiv.data("url");

    if (playerDiv.length > 0) {
        var html = '<iframe src=\''+url+'\' id=\'viewerIframe\' width=\'100%\'allowfullscreen webkitallowfullscreen height=\'100%\' style=\'border:0px\'></iframe>';
        playerDiv.html(html);
    }    
  }
}

new teacherReviewModal().init();

