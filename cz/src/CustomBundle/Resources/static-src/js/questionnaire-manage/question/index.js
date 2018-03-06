import BatchSelect from 'app/common/widget/batch-select';
import DeleteAction from 'app/common/widget/delete-action';
import sortList from 'common/sortable';
import notify from 'common/notify';

class QuestionnaireManage
{
  constructor($deleteBtn, $sortableList) {

    this.$deleteBtn = $deleteBtn;
    this.deleteQuestionnaire();
    this.$sortableList = $sortableList;
    this.taskSortable();

  }
  deleteQuestionnaire(){
    this.$deleteBtn.on('click', (e) => {
      let target = e.currentTarget;
      if (!confirm(Translator.trans('确认删除该调查问卷题目？'))) {
        return;
      }
      $.post($(target).data('url')).done(() => {
        $(target).closest('tr').remove();
      }).fail((data) => {
        notify('danger', data.responseJSON.error.message);
      });
    })
  }

  taskSortable(){
  let list = this.$sortableList;
  if (list.length) {
    sortList({
      group: '.questionnaire-question-list',
      element: list,
      containerSelector:'table',
      ajax: false,
      itemPath: ' > tbody',
      itemSelector: 'tr.drag',
      placeholder: '<tr class="placeholder"><td></td><td></td><td></td><td></td><td></td></tr>'
    }, (data) => {
      let arr = [];
      $(list).find('tr').each((i,el)=> {
        let data1 = $(el).attr('id');
        if (data1) {
          console.log(el);
          arr.push(data1);
          return data1;
        }
        return;
      });

      $.post($(list).data('sortUrl'), { ids: arr }, (response) => {
        $(list).find('tr.js-table-list-head').next('tr').addClass('group-leader').attr('data-stu', 1);
      });
    });
  }    
  }
}
let $container = $('#quiz-table-container');
let $deleteBtn = $('.js-delete-question-btn');
let sortableList = 'table.questionnaire-question-list';
new QuestionnaireManage($deleteBtn, sortableList);
new BatchSelect($container);
new DeleteAction($container);