import TaskPipe from "./../../task/widget/task-pipe";
import taskResultPush from './task-result.js';
import lodash from 'lodash';

let _taskResultPush = new taskResultPush();
_taskResultPush.bindEvents();

lessonTaskClick();
coursedToggle();
currentClick();
collectBeforeTask();

function currentClick() {
  let $currentCourse = $('.js-current-course');
  let $loadCourse = $('.js-load-all-course');

  $loadCourse.on('click', '.return-current-course', (e) => {
    console.log($currentCourse, $loadCourse)
    $currentCourse.show();
    $loadCourse.hide();
  });
}

function lessonTaskClick() {
  let $task = $('.js-lesson-task');
  $('body').on('click', '.js-lesson-task', (e) => {
    let $target = $(e.currentTarget);
    let taskId = $target.data('taskId');
    let courseId = $target.data('courseId');
    let taskNum = $target.siblings('.task-index').text();
    let taskName = $target.text(),
      mode = $('input[name=mode]').val(),
      lessonNumber = $target.parents('.course-detail-content').prev().data('lesson-number'),
      $currentPlace = $('#js-current-place'),
      src;
    taskName = taskName.trim();
    if (mode == 'preview') {
      src = `/instant/course/${courseId}/task/${taskId}/preview`;
    } else {
      src = `/course/${courseId}/task/${taskId}/activity_show`;
      let $icon = $target.prev();
      if ($target.data('role') == 'student' && $icon.hasClass('es-icon-undone-check')) {
        $icon.removeClass().addClass('es-icon es-icon-doing color-primary left-menu js-task-'+taskId);
      }
    }
    let currentTime = Date.parse(new Date());
    currentTime /= 1000;

    let iframe = `<iframe id="task-content-iframe" class="task-content-iframe"
                    data-event-enable="1"
                    data-event-url="/course/${courseId}/task/${taskId}/trigger"
                    data-last-time="${currentTime}"
                    src="${src}"
                    style="width:100%;height:100%;border:0px"
                    allowfullscreen webkitallowfullscreen>
                  </iframe>`;
    $target.parents('.js-current-course').find('li').removeClass('active');
    $target.closest('li').addClass('active');
    $currentPlace.text(`当前位置：课次${lessonNumber}`);
    $(e.delegateTarget).find('.js-dashboard-body').html(iframe);
    $(e.delegateTarget).find('.js-task-name').text(`任务${taskNum}：${taskName}`);
    $(e.delegateTarget).find('.js-task-name').attr('title', taskName);

    // window.taskPipe._changeInterval(`/course/${courseId}/task/${taskId}/trigger`);
    window.taskPipe && window.taskPipe._clearInterval();
    window.taskPipe = new TaskPipe($('#task-content-iframe'));
  })
}


function coursedToggle() {
  let $coursedHead = $('.js-teached-course-head');
  $('body').on('click', 'li.js-teached-course-head', (e) => {
    let target = e.currentTarget;
    let url = $(target).data('url');
    e.stopPropagation();
    if (!$(target).hasClass('open') && !$(target).hasClass('active')) {
      $.get(url, (data) => {
        $(target).after(data);
        $(target).find('.js-course-close').show();
        $(target).find('.js-course-open').hide();
        $(target).addClass('active');
      });
      $(target).addClass('open');
    } else if ($(target).hasClass('open') && $(target).hasClass('active')) {
      $(target).nextUntil($('.js-teached-course-head')).slideToggle();
      $(target).find('.js-toggle-icon').slideToggle();
    }
  });
}

function collectBeforeTask() {
  $('body').on('click', '.js-collect-before-task', function(e) {
    let src = $(this).data('url');
    let currentTime = Date.parse(new Date());
    currentTime /= 1000;
    let iframe = `<iframe id="task-content-iframe" class="task-content-iframe"
                    data-event-enable="1"
                    data-last-time="${currentTime}"
                    src="${src}"
                    style="width:100%;height:100%;border:0"
                    allowfullscreen webkitallowfullscreen>
                  </iframe>`;
    $(e.delegateTarget).find('.js-dashboard-body').html(iframe);
    $(e.delegateTarget).find('.js-task-name').text('课前活动汇总');
    $(e.delegateTarget).find('.js-current-course').find('li').removeClass('active');
  });
}