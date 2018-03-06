import socket from '../../../common/socket';
import { timeCount } from '../activity-common';
require('../../../common/masonry.pkgd.min');
require('../../../common/imagesloaded.pkgd.min');

export default class practiceWorkSocket {
  constructor() {
    this.socket = socket;
    this.taskId = $('input[name=taskId]').val();
    this.timeTick = timeCount();
    this.$elem = $('#student-picture-show');
  }

  init() {
    this.$grid = this.$elem.masonry({
      itemSelector: '.js-practice-work-card',
      percentPosition: true,
      transitionDuration: '0.5s',
      resize: true,
    });
    this.$grid.imagesLoaded().progress(() => {
      this.$grid.masonry('layout');
    });
  }

  bindEvents() {
    this.init();
    this.on('start task', (response) => this.taskStart(response));
    this.on('end task', (response) => this.taskEnd(response));
    this.on('create practice work result', (response) => this.uploadPicture(response));
    this.on('update practice work result', (response) => this.changeUploadPicture(response));
  }

  taskStart(response) {
    if (this.taskId === response.task.id) {
      window.location.reload();
    }
  }

  taskEnd(response) {
    if ($('#taskId').val() === response.task.id) {
      $('.js-rend-call').hide();
      $('.js-rend-stop').toggleClass('hidden');
      if (this.timeTick !== null) {
        clearInterval(this.timeTick);
      }
    }
  };

  uploadPicture(response) {
    const $replyNumber = $('.js-reality-member');
    const $grid = this.$grid;

    let html =
      `<div class="practice-work-card js-practice-work-card" id="js-result-${response.result.id}" >
        <img class="wall-img inline-block js-wall-img" src="${response.url}" />
        <div class="practice-work-bottom">${response.user.truename}</div>
      </div>`;
    const $html = $(html);

    $grid
      .append($html)
      .imagesLoaded()
      .progress(() => {
        $grid.masonry('layout');
      })
      .masonry('appended', $html);

    $replyNumber.html(parseInt($replyNumber.text()) + 1);
  }

  changeUploadPicture(response) {
    $(`#js-result-${response.result.id} .js-wall-img`).attr('src', response.url);
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }
}