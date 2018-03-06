/**
 * Created by wubo on 2017/8/21.
 */
import {calcTime} from '../../common/util';

// 上课时间
export const timeCount = function () {
  let taskStatus = $(".js-task-status").val();
  let timeTick = null;

  let time = $("#js-classed-time").val();
  $(".js-time-num").text(calcTime(time));
  
  if (taskStatus === 'start') {
    timeTick = setInterval(() => {
      time++;
      $(".js-time-num").text(calcTime(time));
    }, 1000);
  }
  return timeTick;
};

// 悬浮title
export const titleShow = function () {
  let opeBtn = $(".js-show-activity-name");
  if (opeBtn) {
    opeBtn.off('click');
    opeBtn.on('click', function () {
      let elem = $(".js-activity-name");
      if (elem.hasClass('fold')) {
        elem.css('marginTop', 0);
        $(this).text('隐藏题干');
      } else {
        let height = elem.height() + 15;
        height = `-${height}px`;
        elem.css('marginTop', height);
        $(this).text('显示题干');
      }
      elem.toggleClass('fold');
    });
  }

  $(window).on('resize', function () {
    let elem = $(".js-activity-name");
    if (elem.hasClass('fold')) {
      let height = elem.height() + 15;
      height = `-${height}px`;
      elem.css('marginTop', height);
    }
  });
};