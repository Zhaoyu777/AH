import FileChooser from 'app/js/file-chooser/file-choose';
import notify from 'common/notify';

class PracticeWork {
  constructor() {
  }

  init() {
    const fileChooser = new FileChooser();
    fileChooser.on('select', this.fileSelect.bind(this));
  }

  fileSelect(file) {
    $('.js-practice-work-file-name').text(file.name);
    $('.js-practice-work-file-reset-title').removeClass('hidden');
    this.ajaxSavePracticeWork(file.id);
  }

  ajaxSavePracticeWork (fileId) {
    let data = {'fileId':fileId, 'taskId':$('#taskId').val(), 'activityId':$('#activityId').val(), 'practiceWorkId':$('#practiceWorkId').val()};
    console.log(data);
    console.log($('#chooser-upload-panel').data('url'));
    $.post($('#chooser-upload-panel').data('url'), data, function(res){
      if (res.result == true) {
        notify('success', '上传文件成功');
        location.reload();
      }
      if (res.result == false) {
        notify('warning', '老师正在批阅中，不能再上传');
      }
    })
  }
}

const practiveWork = new PracticeWork();
practiveWork.init();

