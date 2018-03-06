import Text from './text';
import TeachingAdd from '../teaching';

new TeachingAdd();
new Text();

$('.js-source-manage-link').on('click', (e) => {
  window.top.open($(e.target).data('url'));
  $('.close', window.parent.document).click();
})