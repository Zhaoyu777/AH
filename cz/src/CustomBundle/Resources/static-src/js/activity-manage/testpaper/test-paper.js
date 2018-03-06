import socket from '../../../common/socket';
import {timeCount} from '../../activity/activity-common';

export default class testPaper {
  constructor() {
    this.socket = socket;
    this.timeTick = timeCount();
    this._inItStep2form();
  }

  bindEvents() {
    this.socket.on('start task', (response) => this.startTask(response));
    this.socket.on('end task', (response) => this.endTask(response));
  }

  startTask(response) {
    if ($('#taskId').val() == response.task.id) {
        location.reload();
    }
  }

  endTask(response) {
    if ($('#taskId').val() == response.task.id) {
        $('.js-rend-call').hide();
        $('.js-start-test').hide();
        $('.js-content').replaceWith('<div class="text-16 ">该测验已关闭，请联系教师！</div>');
        $('.js-rend-stop').toggleClass('hidden');
        if(this.timeTick !== null) {
          clearInterval(this.timeTick);
        }
    }
  };

  _inItStep2form() {
    const $step2_form = $('#step2-form');
    let validator = $step2_form.data('validator');
    validator = $step2_form.validate({
      rules: {
        title: {
          required: true,
          maxlength: 50,
          trim: true,
          course_title: true,
        },
        mediaId: {
          required: true,
          trim: true,
        },
      },
    });
    const $content = $('[name="content"]');
    this._contentCache = $content.val();
  }
}