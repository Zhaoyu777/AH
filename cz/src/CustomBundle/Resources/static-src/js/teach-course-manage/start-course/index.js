import { chapterAnimate } from 'app/common/widget/chapter-animate';

chapterAnimate();

(function initTeachChart() {
  let colorPrimary = $('.es-main-default .color-primary').css('color');
  let colorWarning = $('.es-main-default .color-warning').css('color');
  $('#courseprogress').easyPieChart({
    easing: 'easeOutBounce',
    trackColor: '#ebebeb',
    barColor: '#4591ec',
    scaleColor: false,
    lineWidth: 14,
    size: 110,
    onStep: function (from, to, percent) {
      $('canvas').css('height', '110px');
      $('canvas').css('width', '110px');
      if (Math.round(percent) == 100) {
        $(this.el).addClass('done');
      }
      $(this.el).find('.percent').html('教学进度' + '<br><span class="num">' + Math.round(percent) + '%</span>');
    }
  });
}());

(function courseToggle() {
  let $courseHead = $('.js-teach-course-head');
  $courseHead.on('click', (e) => {
    let target = e.currentTarget;
    let $otherHead = $(target).parent().siblings().find('.js-teach-course-head');

    $otherHead.next().slideUp();
    $otherHead.find('.js-toggle-icon.es-icon-keyboardarrowdown').show();
    $otherHead.find('.js-toggle-icon.es-icon-keyboardarrowup').hide();
    $(target).nextUntil($courseHead).slideToggle();
    $(target).find('.js-toggle-icon').toggle();
    e.stopPropagation();
  });
}());

(function coursedToggle() {
  let $coursedHead = $('.js-teached-course-head');
  $coursedHead.on('click', (e) => {
    let target = e.currentTarget;

    $(target).nextUntil($coursedHead).slideToggle();
    $(target).find('.js-toggle-icon').toggle();
    e.stopPropagation();
  });
}());

(function () {
  let $preventBtn = $('.js-prevent-btn');
  $preventBtn.on('click', (e) => {
    e.stopPropagation();
  })
}());

