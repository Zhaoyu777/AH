import {
  taskSortable,
  closeCourse,
  showSettings,
  deleteTask,
  publishTask,
  unpublishTask
} from 'app/js/course-manage/help';

import CancelBtn from '../lesson-cancel/cancel';
import notify from 'common/notify';

taskSortable('#sortable-list-course1');
taskSortable('#sortable-list-course2');
taskSortable('#sortable-list-course3');
closeCourse();
deleteTask();
publishTask();
unpublishTask();
showSettings();

$('.js-lesson-cancel').on('click', (e) => {
  if (!confirm('确定要删除该环节吗？')) {
    return ;
  }
  let $target = $(e.currentTarget);
  $.post($target.data('url'), () => {
    window.location.reload();
  });
});

$('.js-message-push').on('click', (e) => {
  if (!confirm('确认要推送消息给学生吗？')) {
    return ;
  }
  let $target = $(e.currentTarget);
  $.post($target.data('url'), () => {
    notify('success',Translator.trans('推送已发出'));
  });
});

// 监听页面关闭事件
function pageCloseCallBack() {
  if (window.localStorage.getItem('editPlan')) {
    window.localStorage.removeItem('editPlan');
  }
  window.localStorage.setItem('editPlan', 'finish');
  window.removeEventListener('unload', pageCloseCallBack);
}

window.addEventListener('unload', pageCloseCallBack, false);

$('.js-close-page').on('click', function() {
  window.close();
});

//
(function toggleContent() {
  let $toggleBtn = $('.js-toggle-btn');
  let $toggleContent = $('.js-prepare-course-content');
  $toggleBtn.on('click', (e) => {
    let $target = $(e.currentTarget);
    $target.find('i').toggle();
    $toggleContent.slideToggle();
    $toggleBtn.toggleClass('hidden');
  })
}());
