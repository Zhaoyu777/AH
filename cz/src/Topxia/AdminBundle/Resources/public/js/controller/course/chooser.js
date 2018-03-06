define(function (require, exports, module) {
  var Notify = require('common/bootstrap-notify');

  exports.run = function () {

    var $form = $("#message-search-form"),
      $modal = $form.parents('.modal'),
      $table = $("#course-table");

    $form.submit(function (e) {
      e.preventDefault();
      $.get($form.attr('action'), $form.serialize(), function (html) {
        $modal.html(html);
      });
    });

    $table.on('click', '.choose-course', function (e) {
      var courseId = $(this).data('target');
      var courseName = $(this).data('name');
      var html = '<a href="/course_set/' + courseId + '" target="_blank"><strong>' + courseName + '</strong></a>';
      $('#choose-course-input').val(courseId);
      $('#course-display .well').html(html);
      $('#course-display').show();
      $modal.modal('hide');
      Notify.success(Translator.trans('指定课程成功'));
    });

    $modal.on('hidden.bs.modal', function (e) {
      if (!$('#choose-course-input').val()) {
        $('.js-course-radios').button('reset');
      }
      ;
    })
  };
})