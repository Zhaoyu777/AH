import notify from 'common/notify';
import ReactDOM from 'react-dom';
import React from 'react';
import MultiInput from './aim-multi-input';

class TeachingInfoEdit {
  constructor() {
    this.init();
  }
  init() {
    this.isEdit();
    if ($("#abilityAims")[0]) {
      this.renderMultiGroupComponent('abilityAims', 'abilityAim');
      this.renderMultiGroupComponent('knowledgeAims', 'knowledgeAim');
      this.renderMultiGroupComponent('qualityAims', 'qualityAim');
    }
    this.submitForm();
  }

  isEdit() {
    let self = this;
    if ($('#teachAim').length > 0) {
      var areas = Array('teachAim', 'tasksCase', 'difficult', 'referenceMaterial', 'afterKnow');
    } else {
      var areas = Array('afterKnow');
    }

    $.each(areas, function (i, area) {
      if ($(`#${area}`).text().trim() == '') {
        $(`[name="${area}Input"]`).focus((e) => {
          $(e.target).hide();
          self.initCkeditor(area);
        });
      } else {
        $(`[name="${area}Input"]`).hide();
        self.initCkeditor(area);
      }
    })

  }

  initCkeditor(area) {
    let editor = CKEDITOR.replace(area,{
      toolbar: 'SimpleMini',
      allowedContent: true
    });
    editor.on('change', () => {
      $('#'+ area).val(editor.getData());
    });
  }

  renderMultiGroupComponent(elementId, name) {
    let datas = $('#' + elementId).data('init-value');

    ReactDOM.render(<MultiInput
      blurIsAdd={true}
      sortable={true}
      dataSource={datas}
      inputName={name + "[]"}
      outputDataElement={name} />,
      document.getElementById(elementId)
    );
  }

  submitForm() {
    $('#submit-btn').on('click', function() {
      $(this).button('loading');
      $.post($('#lesson-edit-form').data('url'),$('#lesson-edit-form').serialize(),(data)=> {
        console.log(data);
        if (data === true) {
          notify('success', '保存成功');
          window.location.reload();
        } else {
          notify('danger', data);
        }
      }).fail((res) => {
        $(this).button('reset');
        res = JSON.parse(res.responseText);
        notify('danger', res.error.message);
      })
    })
  }
}

new TeachingInfoEdit();

