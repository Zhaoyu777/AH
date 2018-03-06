import socket from '../../../common/socket';
import {timeCount} from '../activity-common';

export default class oneSentencePush {
  constructor() {
    this.socket = socket;
    this.timeTick = timeCount();
  }

  bindEvents() {
    this.on('one sentence result', (response) => this.oneSentenceResult(response));
    this.on('start task', (response) => this.startOneSentence(response));
    this.on('end task', (response) => this.endOneSentence(response));
  }

  oneSentenceResult(response) {
    console.log(response);
    if ($('#js-taskId').val() == response.taskId) {
      const groupId = response.groupId;
      const result = response.result;
      const groupDom = $(`#js-group-${groupId}`);
      let parent = $('#js-student-answer');
      let answeredNumDom = groupDom.find('.js-group-answered-num');
      // 已答人数+1
      const num = parseInt(answeredNumDom.text());
      answeredNumDom.text(num + 1);

      groupDom.find('.js-not-answered-alert').hide();

      parent.find('.js-answered-student-name').text(result.truename);
      parent.find('.js-answered-content').text(result.content);
      parent.find('.js-answered-student-avatar').attr('src', result.avatar);
      let time = new Date(parseInt(result.createdTime) * 1000);
      time = time.toTimeString();
      parent.find('.js-student-answer-time').text(time.split(' ')[0]);
      groupDom.find('.js-results-list').prepend(parent.html());
    }
  }

  startOneSentence(response) {
    if ($('#js-taskId').val() == response.task.id) {
      $('.js-rend-call').toggleClass('hidden');
      location.reload();
    }
  }

  endOneSentence(response) {
    if ($('#js-taskId').val() == response.task.id) {
      $('.js-rend-call').hide();
      $('.js-rend-stop').toggleClass('hidden');
      if (this.timeTick !== null) {
        clearInterval(this.timeTick);
      }
    }
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }
}