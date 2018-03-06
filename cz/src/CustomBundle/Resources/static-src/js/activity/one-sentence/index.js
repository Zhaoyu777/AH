import activityAnimate from '../animate-show/animate';
import oneSentencePush from './one-sentence-push';
import {titleShow} from '../activity-common';

titleShow();

let _oneSentencePush = new oneSentencePush();
_oneSentencePush.bindEvents();

$('#one-sentence-activity').perfectScrollbar();
$('#one-sentence-activity').perfectScrollbar('update');

function toggleBtn () {
  $('.activity-content').on('click', '.js-rend-call', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'))
  })
}
toggleBtn();

$('#modal').on('show.bs.modal', function (e) {
  $('#one-sentence-activity').perfectScrollbar('destroy');
});

$('#modal').on('hide.bs.modal', function (e) {
  $('#one-sentence-activity').perfectScrollbar();
});

$('body').on('click', '.js-grade-btn', (e) => {
    $(e.target).addClass('active');
});

let oneSentence = new activityAnimate({
  animateEl: $('.js-rend-call'),
  imgSrc: 'one_word'
});
oneSentence.init();

