import BatchSelect from 'app/common/widget/batch-select';
import DeleteAction from 'app/common/widget/delete-action';

class QuestionnaireManage
{
  constructor($deleteBtn) {

    this.$deleteBtn = $deleteBtn;
    this._initEvent();
  }
  _initEvent(){
    this.$deleteBtn.on('click', (e) => {
      let target = e.currentTarget;
      if (!confirm(Translator.trans('确认删除该调查问卷？'))) {
        return;
      }
      $.post($(target).data('url'), () => {
        $(target).closest('tr').remove();
      })
    })
  }
}
let $container = $('#quiz-table-container');
let $deleteBtn = $('.js-delete-questionnaire-btn');
new QuestionnaireManage($deleteBtn);
new BatchSelect($container);
new DeleteAction($container);