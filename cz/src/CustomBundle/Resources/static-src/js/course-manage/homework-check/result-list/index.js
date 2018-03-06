import notify from 'common/notify';
class resultList {
  init() {
    this.initQuickGivePoint();
    this.exportResult();
  }
  initQuickGivePoint() {
    $('.js-quick-give-point').on('change', function() {
      let val = $(this).children('option:selected').val();
      if (val == 0) {
        return;
      }
      if (!confirm('确认给分吗?')) {
        $(this).val(0);
        return;
      }
      let data = {'appraisal':val};
      $.post($('.js-quick-give-point').data('url'), data, () => {
        notify('success',Translator.trans('批阅成功'));
        $(this).parent().parent().parent().remove();
      })
    });

  }

  exportResult() {
    $('#export-result-btn').on('click', function () {
      $('#export-result-btn').button('loading');
      $.get($('#export-result-btn').data('datasUrl'), { start: 0 }, function (response) {
        if (response.status === 'getData') {
          exportStudents(response.start, response.fileName);
        } else {
          $('#export-result-btn').button('reset');
          location.href = $('#export-result-btn').data('url') + '?&fileName=' + response.fileName;
        }
      });
    });
  }

  exportStudents(start, fileName) {
    var start = start || 0,
      fileName = fileName || '';

    $.get($('#export-result-btn').data('datasUrl'), { start: start, fileName: fileName }, function (response) {
      if (response.status === 'getData') {
        exportStudents(response.start, response.fileName);
      } else {
        $('#export-result-btn').button('reset');
        location.href = $('#export-result-btn').data('url') + '&fileName=' + response.fileName;
      }
    });
  }
}

new resultList().init();

