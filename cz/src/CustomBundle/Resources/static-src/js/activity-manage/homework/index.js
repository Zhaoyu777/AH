import BatchSelect from 'app/common/widget/batch-select';
import QuestionOperate from 'app/common/component/question-operate';
import Create from './create';
import TeachingAdd from '../teaching';

let $from = $('#step2-form');
new Create($('#iframe-content'));
new BatchSelect($from);
new QuestionOperate($from,$("#attachment-modal",window.parent.document));
new TeachingAdd();

$('.js-source-manage-link').on('click', (e) => {
  window.top.open($(e.target).data('url'));
  $('.close', window.parent.document).click();
})