import DoTestBase from 'app/js/testpaper/widget/do-test-base';
import {
  initScrollbar,
  testpaperCardFixed,
  testpaperCardLocation,
  initWatermark,
  onlyShowError
} from 'app/js/testpaper/widget/part';



class DoTestpaper extends DoTestBase {
  constructor($container) {
    super($container);
  }
}

new DoTestpaper($('.js-task-testpaper-body'));