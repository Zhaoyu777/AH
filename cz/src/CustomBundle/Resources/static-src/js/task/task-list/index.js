import notify from 'common/notify';
import socket from '../../../common/socket';

class TaskList {
  constructor() {
    this.timer;
    this.timeShow = $('.js-time-count');
    this.$startBtn = $('.js-start-course-btn');
    this.hours = Number(this.timeShow.find('.hours').text());
    this.minutes = Number(this.timeShow.find('.minutes').text());
    this.seconds = Number(this.timeShow.find('.seconds').text());
    this.socket = socket;
  }

  init() {
    this.getStoreTime();
  }

  fillNum(num) {
    return (num >= 10) ? num : '0' + num;
  }

  addSeconds() {
    if (this.autoStop() < Date.now()) {
      this.stopTimer();
    } else if (this.seconds < 59) {
      this.seconds ++;
    } else if (this.minutes < 59) {
      this.seconds = 0;
      this.minutes ++;
    } else if (this.hours < 23) {
      this.seconds = 0;
      this.minutes = 0;
      this.hours ++;
    }
    this.timeShow.html(`${this.fillNum(this.hours)}：${this.fillNum(this.minutes)}：${this.fillNum(this.seconds)}`);
  }

  startTimer() {
    if (this.timer == undefined) {
      this.timer = setInterval(() => {
        this.addSeconds()
      }, 1000);
    }
  }

  stopTimer() {
    clearInterval(this.timer);
  }

  autoStop() {
    var startTime;
    if (this.$startBtn.data('time')) {
      startTime = new Date(this.$startBtn.data('time'));
    } else if ($('.js-time-get').data('timeGet')) {
      startTime = new Date($('.js-time-get').data('timeGet')*1000);
    }
    var oneDayEnd = startTime.setHours(23);
    oneDayEnd = startTime.setMinutes(59);
    oneDayEnd = startTime.setSeconds(59);
    return oneDayEnd;
  }

  getStoreTime() {
    if ($('.js-time-get').attr('data-time-get') > 0) {
      let now = Date.now();
      let timeGet = $('.js-time-get').attr('data-time-get') * 1000;
      let duration = now - timeGet;
      this.hours = Math.floor(duration/(3600*1000));
      let HoursRest = duration%(3600*1000);
      this.minutes = Math.floor(HoursRest/(60*1000));
      let minutesRest = HoursRest%(60*1000);
      this.seconds = Math.floor(minutesRest/1000);
      this.startTimer();
    }
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }

}

const taskList = new TaskList();
taskList.init();