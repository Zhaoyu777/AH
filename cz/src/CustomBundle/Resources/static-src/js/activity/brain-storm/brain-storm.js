import socket from '../../../common/socket';
import {timeCount} from '../activity-common';

export default class brainStorm {
  constructor() {
    this.socket = socket;
    this.timeTick = timeCount();
  }

  bindEvents() {
    this.socket.on('start task', (response) => this.startTask(response));
    this.socket.on('end task', (response) => this.endTask(response));
    this.socket.on('brain storm change', (response) => this.brainStormChange(response));
    this.socket.on('task result remark', (response) => this.taskResultRemark(response));
    this.socket.on('join task group', (response) => this.joinGroup(response));
  }

  joinGroup(response) {
    console.log(response.groupId,response.memberCount);
    if ($('#taskId').val() == response.taskId) {
      $('.js-group-members-'+response.groupId).find('.js-member-count').html(response.memberCount);
    }
  }

  startTask(response) {
    if ($('#taskId').val() == response.task.id) {
      location.reload();
    }
  }

  endTask(response) {
    if ($('#taskId').val() == response.task.id) {
      $('.js-rend-call').hide();
      $('.js-rend-stop').toggleClass('hidden');
      if (this.timeTick !== null) {
        clearInterval(this.timeTick);
      }
    }
  };

  brainStormChange(res) {
    if (res.taskId != $('#taskId').val()) {
      return;
    }
    let leader = $(`.js-${res.brainStorm.groupId}-group`);
    if (!leader.text()) {
      leader.text(res.brainStorm.userName);
    }
    if (res.brainStorm.submitWay === 'person') {
      const parent = $("#brain-storm-production").find(`.js-group-members-${res.groupId}`);
      let replyCount = $('.js-group-members-'+res.groupId).find('.js-reply-count').html();

      $('.js-group-members-'+res.groupId).find('.js-reply-count').html(parseInt(replyCount)+1);
      const stuName = res.result.truename;
      parent.find('.group-members').append(stuName + '、');
      if ($('#race').val()) {
        let scoreBtn = parent.find('.js-grade-btn');
        if (scoreBtn.hasClass('hidden')) {
          scoreBtn.removeClass('hidden');
        }
        scoreBtn.attr('data-url', res.brainStorm.url);
      }
    } else {
      const parent = $("#brain-storm-production").find(`.js-group-members-${res.groupId}`);
      let group = $('.js-group-way-submit');
      let commitInfo = parent.find('.js-last-commit-info');
      // 组提交信息部分
      if (commitInfo.hasClass('hidden')) {
        commitInfo.remove('hidden');
      }
      parent.find('.js-group-way-name').text(res.brainStorm.userName);
      parent.find('.js-group-way-time').text(res.brainStorm.createdTime);
      // 组提交内容部分
      if (res.brainStorm.score != 0) {
        group.find('.js-grade-btn').addClass('hidden');
        group.find('.js-score-show').html(`+ ${res.brainStorm.score} 分`).attr('id', res.brainStorm.id);
      } else {
        group.find('.js-grade-btn').attr('data-url', res.brainStorm.url);
        group.find('.js-score-show').attr('id', res.result.id);
      }
      group.find('.js-group-way-content').text(res.brainStorm.content);
      let groupHtml = group.html();
      $(`.group-${res.brainStorm.groupId}-content`).html(groupHtml);
    }
  }

  taskResultRemark(response) {
    if (response.taskId != $('#taskId').val()) {
      return;
    }
    $('#' + response.resultId).html(`+ ${response.score} 分`).parent().find('a').addClass('hidden');
  }
}