import notify from 'common/notify';
import socket from '../../../common/socket';

class CourseLesson {
  constructor() {
    this.$startBtn = $('.js-start-course-btn');
    this.$stopBtn = $('.js-stop-course-btn');
    this.$cancelBtn = $('.js-cancel-course-btn');
    this.socket = socket;
  }

  init() {
    this.initEvent();
  }

  initEvent() {
    this.$stopBtn.on('click', (e) => this.stopClick(e));
    this.$cancelBtn.on('click', (e) => this.cancelClick(e));

    this.bindSocketEvents();
  }

  bindSocketEvents() {
    this.on('end lesson', (response) => this.endLesson(response));
    this.on('cancel lesson', (response) => this.cancelLesson(response));
  }

  endLesson(response) {
    console.log('end lesson');
    let $target = $('.js-stop-course-btn');
    window.location.href = $target.data('lesson-url');
  }

  cancelLesson(response) {
    console.log('cancel lesson');
    let $target = $('.js-cancel-course-btn');
    window.location.href = $target.data('lesson-url');
  }

  stopClick(e) {
    let $target = $(e.currentTarget);
    let stopTime = Date.now();

    $.post($target.data('url'), {stopTime: stopTime});

    $('#modal').hide();
  }

  cancelClick(e) {
    let $this = $(e.currentTarget);
    let stopTime = Date.now();

    $.post($this.data('url'), {stopTime: stopTime});

    $('#modal').hide();
    notify('info', '正在撤销上课', {
      delay: 200000
    });
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }

}

const courseLesson = new CourseLesson();
courseLesson.init();