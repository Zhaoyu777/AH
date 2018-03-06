import socket from '../../../common/socket';
import { timeCount } from '../activity-common';
require('../../../common/masonry.pkgd.min');
require('../../../common/imagesloaded.pkgd.min');

export default class displayWallSocket {
  constructor() {
    this.lock = false;
    this.userId = $('.js-current-userId').val();
    this.socket = socket;
    this.taskId = $('input[name=taskId]').val();
    this.timeTick = timeCount();
    this.init();
  }

  init() {
    this.initMasonry();
    this.bindEvents();
  }

  initMasonry() {
    this.$grid = $('.js-wall-container').masonry({
      itemSelector: '.js-wall-stu-info',
      percentPosition: true,
      transitionDuration: '0.5s',
      resize: true,
    });
    this.$grid.imagesLoaded().progress(() => {
      this.$grid.masonry('layout');
    });
  }

  bindEvents() {
    this.on('start task', (response) => this.taskStart(response));
    this.on('end task', (response) => this.taskEnd(response));
    this.on('like display wall content', (response) => this.likeDisplayWallContent(response));
    this.on('cancel like display wall content', (response) => this.cancelLikeDisplayWallContent(response));
    this.on('create display wall result', (response) => this.displayWallUpload(response));
    this.on('change display wall image', (response) => this.displayWallChange(response));
    this.on('display wall post num', (response) => this.displayWallPost(response));
    this.on('task result remark', (response) => this.taskResultRemark(response));
    this.on('join task group', (response) => this.joinGroup(response));

    const $parentDom = $('.js-cz-activity-content, #modal');
    $parentDom.on('click', '.js-zan-like, .js-cancel-like', this.callBack.bind(this));
    $parentDom.on('click', '.js-wall-like-num', this.clickLikeNum);
  }

  clickLikeNum() {
    const $parent = $(this).parent();
    const $likeDom = $parent.find('.js-zan-like');
    const $cancelLikeDom = $parent.find('.js-cancel-like');

    if($likeDom.hasClass('hidden')) {
      $cancelLikeDom.click();
    } else {
      $likeDom.click();
    }
  }

  callBack (e) {
    if (this.lock) {
      return;
    }
    this.lock = true;
    const url = $(e.target).data('url');

    $.post({ url });
  }

  joinGroup(response) {
    if (this.taskId === response.taskId) {
      $(`.js-${response.groupId}-group`).text(response.captain);
      $(`.js-number-${response.groupId}`).find('.js-memberCount').text(response.memberCount);
    }
  }

  taskResultRemark(response) {
    if (this.taskId === response.taskId) {
      let scoreElem = $('#' + response.resultId);
      let elem = scoreElem.parents('.js-iframe-new');
      if (elem) {
        // 改版后
        scoreElem.html(`+${response.score}`).parent('.js-score-parent').removeClass('hidden');
        $('.js-result-'+response.resultId).find('.js-grade-btn').addClass('hidden');
      } else {
        // 改版前
        scoreElem.html(`+${response.score}分`).parent().find('a').addClass('hidden');
      }
    }
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

  displayWallPost(response) {
    if (this.taskId === response.result.courseTaskId) {
      let $postNum = $(`.js-result-${response.result.id}`).find('.js-wall-post-num');
      $postNum.text(response.postNum);
    }
  }

  likeDisplayWallContent(response) {
    if (this.taskId === response.result.courseTaskId) {
      const $parent = $(`.js-result-${response.result.id}`);
      const $likeNum = $parent.find('.js-wall-like-num');
      const likeNum = response.content.likeNum;
      const resultId = response.result.id;
      const $elem = $(`.js-result-${resultId}`);

      if (this.userId === response.likeUserId) {
        $elem.find('.js-cancel-like').addClass('hidden');
        $elem.find('.js-zan-like').removeClass('hidden');
      }
      $likeNum.text(likeNum);
      this.lock = false;
    }
  }

  cancelLikeDisplayWallContent(response) {
    if (this.taskId === response.result.courseTaskId) {
      const $parent = $(`.js-result-${response.result.id}`);
      const $likeNum = $parent.find('.js-wall-like-num');
      const likeNum = response.content.likeNum;
      const resultId = response.result.id;
      const $elem = $(`.js-result-${resultId}`);

      if (this.userId === response.likeUserId) {
        $elem.find('.js-cancel-like').removeClass('hidden');
        $elem.find('.js-zan-like').addClass('hidden');
      }
      $likeNum.text(likeNum);
      this.lock = false;
    }
  }

  displayWallUpload(response) {
    if (this.taskId === response.result.courseTaskId) {
      const routings = response.routings;
      const copyElem = $('.js-wall-result-content').clone();
      const result = response.result;
      const content = response.content;
      const $groupItem = $(`#group-${result.groupId}`);

      copyElem.find('.js-wall-content-show').attr('data-url', routings.contentShow);
      copyElem.find('.js-zan-like').attr({'data-url': routings.cancelLike, 'data-result-id': result.id});
      copyElem.find('.js-cancel-like').attr({'data-url': routings.like, 'data-result-id': result.id});
      copyElem.find('.js-wall-image').attr('src', content.uri);
      copyElem.find('.js-wall-like-num').text(content.likeNum);
      copyElem.find('.js-wall-post-num').text(content.postNum);
      copyElem.find('.js-wall-content-truename').text(response.user.truename);
      copyElem.find('.js-grade-btn').attr('data-url', routings.remark);
      copyElem.find('.js-score-show').attr('id', `${result.id}`);
      copyElem.find('.js-wall-stu-info').addClass(`js-result-${result.id}`);

      const $html = $(copyElem.html());
      if ($groupItem.length > 0) {
        const $numberCount = $(`.js-number-${result.groupId}`);
        const $replyCount = $numberCount.parent().find('.js-wall-replay-count');

        if($replyCount.length) {
          let replyCount = $replyCount.text();
          replyCount++;
          $replyCount.text(replyCount);
        }
        this.addNewItem($html,response);
      } else {
        this.$grid.append($html).masonry( 'appended', $html);
      }
      this.$grid.masonry('reloadItems');
      this.$grid.masonry('layout');
    }
  }

  displayWallChange(response) {
    if (this.taskId === response.result.courseTaskId) {
      $(`.js-result-${response.result.id} .js-wall-image`).attr('src', response.content.uri);
    }
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }

  addNewItem($html, response) {
    const grid = this.$grid;
    const index = [].findIndex.call(grid, item => {
      return $(item).attr('id') === `group-${response.result.groupId}`;
    });
    const currentParent = $(grid[index]);

    if(index > -1) {
      currentParent
        .append($html)
        .imagesLoaded()
        .progress(() => {
          currentParent.masonry('layout');
        })
        .masonry('appended', $html);
    }
  }
}