import socket from '../../../common/socket';
import { timeCount } from '../activity-common';
require('../../../common/masonry.pkgd.min');
require('../../../common/imagesloaded.pkgd.min');

export default class PracticeSocket {
  constructor() {
    this.lock = false;
    this.userId = $('.js-current-userId').val();
    this.role = $('.js-current-role').val().trim();
    this.socket = socket;
    this.taskId = $('input[name=taskId]').val();
    this.timeTick = timeCount();
    this.$startBtn = $('.js-start-task');
    this.$endBtn = $('.js-end-task');
    this.init();
  }

  init() {
    if(this.role === 'teacher') {
      this.saveMaterialAnimate();
    }
    
    this.initMasonry();
    this.bindEvents();
  }

  initMasonry() {
    const $stuElem = $("#student-photo-list");
    const $teacherElem = $('#teacher-photo-list');

    if($stuElem.length) {
      this.$stuGrid = $stuElem.masonry({
        itemSelector: '.js-wall-stu-info',
        percentPosition: true,
        transitionDuration: '0.5s',
        resize: true,
      });
      this.$stuGrid.imagesLoaded().progress(() => {
        this.$stuGrid.masonry();
      });
    }

    if($teacherElem.length) {
      this.$teacherGrid = $teacherElem.masonry({
        itemSelector: '.js-wall-stu-info',
        percentPosition: true,
        transitionDuration: '0.5s',
        resize: true,
      });
      this.$teacherGrid.imagesLoaded().progress(() => {
        this.$teacherGrid.masonry();
      });
    }
  }

  saveMaterialAnimate() {
    $('.js-wall-img-mask').hover(function() {
      const $elem = $(this).find('.js-info-box');
      $elem.css({ top: "-50px", height: "110px"});
    }, function() {
      const $elem = $(this).find('.js-info-box');
      $elem.css({ top: 0, height: "60px"});
    })
  }

  bindEvents() {
    this.on('start task', (response) => this.taskStart(response));
    this.on('end task', (response) => this.taskEnd(response));
    this.on('like practice content', (response) => this.likePracticeContent(response));
    this.on('cancel like practice content', (response) => this.cancelLikePracticeContent(response));
    this.on('create practice result', (response) => this.practiceUpload(response));
    this.on('change practice image', (response) => this.practiceChange(response));
    this.on('practice post num', (response) => this.practicePost(response));
    this.on('task result remark', (response) => this.taskResultRemark(response));

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
    if (this.taskId === response.task.id) {
      this.$endBtn.addClass('hidden');
      $('.js-rend-stop').removeClass('hidden');
      if (this.timeTick !== null) {
        clearInterval(this.timeTick);
      }
    }
  }

  practicePost(response) {
    if (this.taskId === response.result.courseTaskId) {
      let $postNum = $(`.js-result-${response.result.id}`).find('.js-wall-post-num');
      $postNum.text(response.postNum);
    }
  }

  likePracticeContent(response) {
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

  cancelLikePracticeContent(response) {
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

  practiceUpload(response) {
    // 如果开发出分组提交的情况，参考展示墙的代码
    const result = response.result;
    if (this.taskId === result.courseTaskId) {
      const $html = this.setHtmlValue(response);
      this.addNewItem($html, response);
    }
  }

  practiceChange(response) {
    if (this.taskId === response.result.courseTaskId) {
      const $parent = $(`.js-result-${response.result.id}`);
      const $savePhoto = $parent.find('.js-save-photo-box');

      $parent.find('.js-wall-image').attr('src', response.content.thumb);
      $savePhoto.addClass('js-save-photo');
      $savePhoto.removeClass('opacity-5 cursor-not-allowed');
      $savePhoto.parent().addClass('active');
      $('.js-save-photo-tip').text('保存为教学资料');
    }
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }

  setHtmlValue(response) {
    if(!response) {
      return;
    }

    const routings = response.routings;
    const content = response.content;
    const result = response.result;

    let copyElem = result.isTeacher === '1' ?  $('.js-teacher-result-copy') :  $('.js-stu-result-copy');
    copyElem = copyElem.clone();

    copyElem.find('.js-wall-image').attr('src', content.thumb);
    copyElem.find('.js-wall-image').attr('data-url', routings.contentShow);
    copyElem.find('.js-wall-like-num').text(content.likeNum);
    copyElem.find('.js-wall-post-num').text(content.postNum);
    copyElem.find('.js-wall-result-number').text(result.number);
    copyElem.find('.js-wall-stu-info').addClass(`js-result-${result.id}`);
    copyElem.find('.js-save-photo').attr('data-content-id', content.id);
    if(result.isTeacher === '0') {
      $('.js-student-photo-list').removeClass('hidden');
      copyElem.find('.js-zan-like').attr({'data-url': routings.cancelLike, 'data-result-id': result.id });
      copyElem.find('.js-cancel-like').attr({'data-url': routings.like, 'data-result-id': result.id});
      copyElem.find('.js-stu-truename').text(response.user.truename);
      copyElem.find('.js-grade-btn').attr('data-url', routings.remark);
      copyElem.find('.js-score-show').attr('id', `${result.id}`);
    } else {
      $('.js-teacher-photo-list').removeClass('hidden');
    }

    return $(copyElem.html());
  }

  addNewItem($html, response) {
    if(!$html || !response) {
      return;
    }

    const $grid = response.result.isTeacher === '1' ? this.$teacherGrid : this.$stuGrid;

    $grid
      .append($html)
      .imagesLoaded()
      .progress(() => {
        $grid.masonry('layout');
      })
      .masonry('appended', $html);

    this.saveMaterialAnimate();
  }
}