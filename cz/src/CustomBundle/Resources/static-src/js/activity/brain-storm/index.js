import activityAnimate from '../animate-show/animate';
import brainStorm from './brain-storm';
import {titleShow} from '../activity-common';

titleShow();

let _brainStorm = new brainStorm();
_brainStorm.bindEvents();

$('#brain-storm-activity').perfectScrollbar();
$('#brain-storm-activity').perfectScrollbar('update');

function toggleBtn () {
  $('.activity-content').on('click', '.js-rend-call', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'), () => {
    })
  })
}
toggleBtn();

$('#modal').on('show.bs.modal', function () {
  $('#brain-storm-activity').perfectScrollbar('destroy');
});

$('#modal').on('hide.bs.modal', function () {
  $('#brain-storm-activity').perfectScrollbar();
});

$('body').on('click', '.js-grade-btn', (e) => {
    $(e.target).addClass('active');
});

// $('.js-back-btn').on('click', (e) => {
//   let self = e.currentTarget;
//   let url = $(self).data('url');
//
//   top.document.location.href = url;
// });

let displaywallAnimate = new activityAnimate({
  animateEl: $('.js-rend-call'),
  imgSrc: 'brain_storming'
});
displaywallAnimate.init();

