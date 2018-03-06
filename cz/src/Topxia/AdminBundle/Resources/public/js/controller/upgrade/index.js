define(function(require, exports, module) {
  var Notify = require('common/bootstrap-notify');

  $('#js-start-upgrade').on('click', function() {
    $.post($(this).data('url'), function() {
      Notify.success(Translator.trans('开始更新'));
    });
  });

  $('#js-end-upgrade').on('click', function() {
    $.post($(this).data('url'), function() {
      Notify.success(Translator.trans('结束更新'));
    });
  })
});