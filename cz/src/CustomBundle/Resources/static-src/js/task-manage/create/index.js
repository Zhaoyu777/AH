import Editor from './editor';
import notify from 'common/notify';

class CustomEditor extends Editor {
  _onSave(event) {

    if (!this._validator(this.step)) {
      return;
    }

    $(event.currentTarget).attr('disabled', 'disabled');

    let postData = $('#step1-form').serializeArray()
      .concat(this.$iframe_body.find('#step2-form').serializeArray())
      .concat($('body').find('#teaching-checkbox-form').serializeArray())
      .concat(this.$iframe_body.find("#step3-form").serializeArray());

    $.post(this.$task_manage_type.data('saveUrl'), postData)
      .done((response) => {
        notify('success', '保存成功');
        document.location.reload();
      })
      .fail((response) => {
        let msg = '';
        let errorResponse = JSON.parse(response.responseText);
        if (errorResponse.error && errorResponse.error.message) {
          msg = errorResponse.error.message;
        }
        notify('warning', '保存出错: ' + msg);
        $("#course-tasks-submit").attr('disabled', null);
      });
  }
}

new CustomEditor($('#modal'));
