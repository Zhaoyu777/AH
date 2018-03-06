import socket from '../../../common/socket';
import {timeCount} from '../../activity/activity-common';

export default class question {
  constructor() {
    this.socket = socket;
    this.timeTick = timeCount();
  }

  bindEvents() {
    this.socket.on('start task', (response) => this.startTask(response));
    this.socket.on('end task', (response) => this.endTask(response));
  }

  startTask(response) {
    console.log(response);
    if ($('#taskId').val() == response.task.id) {
        location.reload();
    }
  }

  endTask(response) {
    console.log(response);
    console.log($('#taskId').val());
    if ($('#taskId').val() == response.task.id) {
        $('.js-rend-call').hide();
        $('.js-start-test').hide();
        $('.js-rend-stop').toggleClass('hidden');
        if(this.timeTick !== null) {
          clearInterval(this.timeTick);
        }
    }
  };
}