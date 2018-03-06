import socket from '../../../common/socket';
import {timeCount} from '../activity-common';

export default class raceAnswerPush {
  constructor() {
    this.socket = socket;
    this.timeTick = timeCount();
  }

  bindEvents() {
    this.socket.on('start task', (response) => this.startRaceAnswer(response));
    this.socket.on('end task', (response) => this.endRaceAnswer(response));
    this.socket.on('race answer result', (response) => this.raceAnswerResult(response));
    this.socket.on('task result remark', (response) => this.taskResultRemark(response));
  }

  startRaceAnswer(response) {
    if ($('#taskId').val() == response.task.id) {
      location.reload();
    }
  }

  endRaceAnswer(response) {
    if ($('#taskId').val() == response.task.id) {
      $('.js-rend-end').toggleClass('hidden');
      $('.js-rend-stop').toggleClass('hidden');
      if (this.timeTick !== null) {
        clearInterval(this.timeTick);
      }
    }
  };

  raceAnswerResult(response) {
    if ($('#taskId').val() == response.taskId) {
      const raceAnswer = response.raceAnswer;
      const user = response.user;
      const parent = $('#result-tr');
      parent.find('.js-stu-rank').html(raceAnswer.count).parent().addClass(`stu-rank-${raceAnswer.count}`);
      parent.find('.js-stu-turename').html(user.truename);
      parent.find('.js-stu-number').html(user.number);
      parent.find('.js-stu-avatar').attr('src', `${user.avatar}`);
      parent.find('.js-score-show').attr('id', `${raceAnswer.id}`);
      if ($('#race').val()) {
        $('#result-tr').find('.js-grade-btn').attr({'data-id': user.id, 'data-url': raceAnswer.url}).show();
      }

      if (response.raceAnswer.count === 1) {
        $('.activity-animate').addClass('hidden');
      }

      $('#race-student').append($('#result-tr').html());
    }
  };

  taskResultRemark(response) {
    console.log(response);
    if (response.taskId != $('#taskId').val()) {
      return;
    }
    $('#' + response.resultId).html(`+${response.score}分`).parent().find('a').addClass('hidden');
  }
}