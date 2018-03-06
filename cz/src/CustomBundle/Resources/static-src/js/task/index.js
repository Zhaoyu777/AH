import TaskSidebar from "./sidebar";
import TaskUi from "app/js/task/widget/task-ui";
import TaskPipe from "./widget/task-pipe";
import Emitter from "common/es-event-emitter";
import socket from '../../common/socket';
import {fullScreen, exitFullScreen} from '../../common/util';
import notify from "common/notify";

class TaskShow extends Emitter {
  constructor({element, mode}) {
    super();
    this.element = $(element);
    this.mode = mode;
    this.$startBtn = $('.js-start-course-btn');
    this.socket = socket;
    this.lessonStatus = $('#js-lesson-status').val();

    this.ui = new TaskUi({
      element: '.js-task-dashboard-page'
    });

    this.init();
  }

  init() {
    this.initFullScreen();
    this.initSidebar();
    this.hideSideBar();

    if (this.lessonStatus === 'created' && this.mode !== 'preview') {
      this.initStartLesson();
      return;
    }

    this.initPlugin();
    if (this.mode !== 'preview') {
      this.initTaskPipe();
      this.initLearnBtn();
    } else {
      this.previewTips();
    }
  }

  initStartLesson() {
    if (this.$startBtn) {
      this.$startBtn.on('click', (e) => this.startClick(e));
      this.socket.on('start lesson', (response) => this.startLesson(response));
    }
  }

  startClick(e) {
    let $target = $(e.currentTarget);
    $target.attr('disabled','disabled');
    $.post($target.data('url'))
      .then((res) => {
        if (res.status == 'failed') {
          notify('danger', Translator.trans(res.message));
          $target.removeAttr('disabled');
          return;
        }

        window.location.reload();
      })
      .fail((response) => {
        $target.removeAttr('disabled');
        if (response.status === 500) {
          notify('danger', '上课操作只能由课程老师发起');
        }
      });
    $target.attr('data-time', Date.now());
  }

  startLesson() {
    window.location.reload();
  }

  initPlugin() {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover({
      html: true,
      trigger: 'hover'
    });
  }

  initSidebar() {
    this.sidebar = new TaskSidebar({
      element: this.element.find('#dashboard-sidebar'),
      url: this.element.find('#js-hidden-data [name="plugins_url"]').val(),
      mode: this.mode
    });
  }

  initFullScreen() {
    $('.js-full-screen').on('click', function () {
      const fullscreenElement = document.fullscreenElement ||
        document.mozFullScreenElement ||
        document.webkitFullscreenElement ||
        document.msFullscreenElement;

      if (fullscreenElement) {
        exitFullScreen(document);
        $(this).find('.js-full-screen-icon').toggleClass('hidden');
      } else {
        $(this).find('.js-full-screen-icon').toggleClass('hidden');
        const fullscreenEnabled = document.fullscreenEnabled ||
          document.mozFullScreenEnabled ||
          document.webkitFullscreenEnabled ||
          document.msFullscreenEnabled;
        if (fullscreenEnabled) {
          fullScreen(document.documentElement);
        } else {
          alert('浏览器不支持全屏!');
        }
      }
    });
  }

  initTaskPipe() {
    window.taskPipe = new TaskPipe(this.element.find('#task-content-iframe'));
    window.taskPipe.addListener('finish', response => {
      this._receiveFinish(response);
    });
  }

  _receiveFinish(response) {
    const status = $('input[name="task-result-status"]', $('#js-hidden-data')).val();
    if (status !== 'finish') {
      $.get($(".js-learned-prompt").data('url'), html => {
        $(".js-learned-prompt").attr('data-content', html);
        this.ui.learnedWeakPrompt();
        this.ui.learned();
        this.sidebar.reload();
        $('input[name="task-result-status"]', $('#js-hidden-data')).val('finish');
      });
    }
  }

  initLearnBtn() {
    const $modal = $('#modal');
    this.element.on('click', '#learn-btn', () => {
      $.post($('#learn-btn').data('url'), response => {
        $modal.modal('show');
        $modal.html(response);
        $('input[name="task-result-status"]', $('#js-hidden-data')).val('finish');
        this.ui.learned();
      });
    });
  }

  hideSideBar() {
    $('body').on('click', (e) => {
      const $toElement = $(e.target);
      const $sidebar = $toElement.closest('#dashboard-sidebar');

      if($sidebar && $sidebar.length > 0) {
        return;
      }

      const $navBarParent = $("#dashboard-sidebar");
      const $childmenu = $navBarParent.find('#dashboard-toolbar-nav').find('li.active');

      if($childmenu.length > 0) {
        $navBarParent.removeClass('spread');
        $childmenu.removeClass('active');
      }
    });
  }

  previewTips() {
    $('.js-task-dashboard-page').on('click', '.js-finish-class', () => {
      notify('warning', '预览中，不可操作……');
    })
  }
}

new TaskShow({
  element: $('body'),
  mode: $('body').find('#js-hidden-data [name="mode"]').val()
});

