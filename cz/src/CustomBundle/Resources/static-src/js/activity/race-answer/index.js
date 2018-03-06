import activityAnimate from '../animate-show/animate';
import raceAnswerPush from './race-answer.js';
import {titleShow} from '../activity-common';

let _raceAnswerPush = new raceAnswerPush();
_raceAnswerPush.bindEvents();

$('#race-answr-activity').perfectScrollbar();
$('#race-answr-activity').perfectScrollbar('update');

startRace();
endRace();
titleShow();

function startRace () {
  $('.activity-content').on('click', '.js-rend-start', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'),() => {

    });
  })
}

function endRace () {
  $('.activity-content').on('click', '.js-rend-end', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'),() => {

    })
  })
}

$('#modal').on('show.bs.modal', function (e) {
  $('#race-answr-activity').perfectScrollbar('destroy');
});

$('#modal').on('hide.bs.modal', function (e) {
  $('#race-answr-activity').perfectScrollbar();
});

$('body').on('click', '.js-grade-btn', (e) => {
    $(e.target).addClass('active');
});

$('body').on('click', '#grade-submit-btn', () => {
    let dataScore = $('.js-evaluate-grade.active').find('span').data('score');
});

let raceAnswerAnimate = new activityAnimate({
  animateEl: $('.js-rend-call'),
  imgSrc: 'fast_answer'
});
raceAnswerAnimate.init();

