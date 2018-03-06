import activityAnimate from '../animate-show/animate';
import callAnimate from './call-animate'
import {titleShow} from '../activity-common';

titleShow();

$('#rollcall-activity').perfectScrollbar();
$('#rollcall-activity').perfectScrollbar('update');

$('body').on('click', '.js-grade-btn', (e) => {
  $(e.target).addClass('active');
});

let roocallAnimate = new activityAnimate();
roocallAnimate.init();

let callAnimate1 = new callAnimate({
  callBtn: $('.js-rend-call'),
  callAnimateWrap: $('.call-animate-warp'),
  selectedContent: $('#rollcall-student'),
});
callAnimate1.init();
