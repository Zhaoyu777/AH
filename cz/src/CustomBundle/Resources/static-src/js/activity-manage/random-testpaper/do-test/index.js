import DoTestBase from './do-test-base';
import { 
  initScrollbar,
  testpaperCardFixed,
  testpaperCardLocation,
  onlyShowError,
  initWatermark } from 'app/js/testpaper/widget/part';

initScrollbar();
testpaperCardFixed();
testpaperCardLocation();
onlyShowError();
initWatermark();

new DoTestBase($('.js-task-testpaper-body'));

$('#finishPaper').on('click', (event) => {
  let $btn = $(event.currentTarget),
      url = $btn.data('url');
  $('iframe', parent.document).attr('src', url);
});