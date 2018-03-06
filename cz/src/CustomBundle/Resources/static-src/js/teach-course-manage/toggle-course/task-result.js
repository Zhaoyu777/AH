import socket from '../../../common/socket';

export default class taskResultPush {
  constructor() {
    this.socket = socket;
  }

  bindEvents() {
    this.socket.on('course task finish', (response) => this.courseTaskFinish(response));
  }

  courseTaskFinish(response) {
    if ($('#userId').val() == response.userId) {
      $('.js-current-course').find(`.js-task-${response.taskId}`).removeClass("es-icon-doing").addClass("es-icon-iccheckcircleblack24px");
    }
  }
}