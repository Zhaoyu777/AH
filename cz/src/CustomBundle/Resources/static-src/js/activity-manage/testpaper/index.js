import testPaper from './test-paper.js';
import Back from '../../activity/components/back';
import TeachingAdd from '../teaching';

new TeachingAdd();
let _back = new Back({
  el: '.js-back'
});
_back.init();

let _testPaper = new testPaper();
console.log(_testPaper);
_testPaper.bindEvents();

function toggleBtn () {
  $('.activity-content').on('click', '.js-rend-call', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'), () => {
    })
  })
}
toggleBtn();

let url = $("input[name='fetchQuestionUrl']").val();

window.setInterval("fetch()", 5000);

window.fetch = () => {
  $.get(url, (response) => {
    $('.js-testpaper-questions').html(response)
    let num = $('.js-testpaper-questions').find('.js-current-finish-number').text();
    $('.js-actual-num').text(num)
  });
}

$('.js-source-manage-link').on('click', (e) => {
  window.top.open($(e.target).data('url'));
  $('.close', window.parent.document).click();
})