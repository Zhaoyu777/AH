import RandomTestpaper from './random-testpaper';
import Paginator from '../../activity/components/paginator';
import Back from '../../activity/components/back';
import TeachingAdd from '../teaching';

new TeachingAdd();

let _paginator = new Paginator({
  el: '.js-turn-btn'
});
_paginator.init();

let _back = new Back({
  el: '.js-back'
});
_back.init();

new RandomTestpaper();
checkQuestionNum();

$('[name="range[courseId]"]').change(function () {
  checkQuestionNum();
})

$('[name="range[lessonId]"]').change(function () {
  checkQuestionNum();
})

$('[name="difficulty"]').change(function () {
  checkQuestionNum();
})

makeTotalScore();
makeItemCount();

function checkQuestionNum() {
  let url = $('[name="range[courseId]"]').data('checkNumUrl');
  let courseId = $('[name="range[courseId]"]').val();
  let lessonId = $('[name="range[lessonId]"]').val();
  let difficulty = $('[name="difficulty"]').val();
  let totalItemCount = 0;

  $.post(url, { courseId: courseId, lessonId: lessonId, difficulty: difficulty }, function (data) {
    $('[role="questionNum"]').text(0);

    $.each(data, function (i, n) {
      $("[type='" + i + "']").text(n.questionNum);
      if ($.inArray(i, ['single_choice', 'fill', 'choice', 'uncertain_choice', 'determine']) != -1) {
        totalItemCount += parseInt(n.questionNum);
      }
    });
    $("#totalItemCount").text(totalItemCount);
  })
}

$(".item-number").change(function(){
  makeTotalScore();
  makeItemCount();
});

$(".item-score").change(function(){
  makeTotalScore();
});

function makeTotalScore() {
  let types = $('[name="types"]').val(),
      totalScore = 0;
  $.each($.parseJSON(types), function (n, value) {
    let counts = '[name="counts['+ value +']"]',
        scores = '[name="scores['+ value +']"]',
        score = '[name="score['+ value +']"]';
        counts = $(counts).val();
        scores = $(scores).val();
        if (isNaN(scores)) {
          scores = 0;
        }
        if (isNaN(counts)) {
          counts = 0;
        }
      totalScore += (scores*counts);
      $(score).text(scores*counts);
  });
  $("#totalScore").text(totalScore);
  $("[name='totalScore']").val(totalScore);
}

function makeItemCount() {
  let types = $('[name="types"]').val(),
      itemCount = 0;
  $.each($.parseJSON(types), function (n, value) {
    let counts = '[name="counts['+ value +']"]';
        counts = $(counts).val();
        counts = $.trim(counts);
        if (counts.length == 0 || isNaN(counts)) {
          counts = 0;
        }
        itemCount += parseInt(counts);
  });
  $("#itemCount").text(itemCount);
  $("[name='itemCount']").val(itemCount);
  $('[name="questioncount"]').val(itemCount > 0 ? itemCount : null);
}

$('.js-source-manage-link').on('click', (e) => {
  window.top.open($(e.target).data('url'));
  $('.close', window.parent.document).click();
})