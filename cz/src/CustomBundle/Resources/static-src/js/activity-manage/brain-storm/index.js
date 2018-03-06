import BrainStrom from './brain-storm';
import TeachingAdd from '../teaching';

new TeachingAdd();
new BrainStrom();

let groupBtn = $("input[name=groupWay]:radio");
groupBtn.change(() => {
  if ($('.js-group-btn')[0].checked) {
    $('.js-random-number').hide();
  } else {
    $('.js-random-number').show();
  }
});