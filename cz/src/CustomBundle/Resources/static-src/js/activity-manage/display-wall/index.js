import DisplayWall from './display-wall';
import TeachingAdd from '../teaching';

new TeachingAdd();
new DisplayWall();
groupResultShow();

function groupResultShow() {
  let groupBtn = $("input[name=groupWay]:radio");
  groupBtn.change(() => {
    if ($('.js-none-btn')[0].checked) {
        $('.js-submit-way').hide();
    } else {
        $('.js-submit-way').show();
    }

    if ($('.js-random-btn')[0].checked) {
      $('.js-random-number').show();
    } else {
      $('.js-random-number').hide();
    }
  });
}
