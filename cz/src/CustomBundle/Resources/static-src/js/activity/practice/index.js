import PracticeSocket from './practice-socket';
import notify from 'common/notify';

export default class Practice {
  constructor() {
    this.lock = false;
    this.$elem = $("#practice-activity");
    this.$modal = $('#modal');
    this.$content = $('.js-cz-activity-content');
    this.$currentResult = null;
    this.init();
  }

  init() {
    this.initScroll();
    this.bindEvents();
    // this.saveMaterialAnimate();
    this.saveMaterialListener();
    new PracticeSocket();

    $('.js-activity-content').on('click', '.js-rend-call', (e) => {
        let $target = $(e.target);
        $.post($target.data('url'));
    });

  }

  initScroll() {
    this.$elem.perfectScrollbar({wheelSpeed: 30});
  }

  bindEvents() {
    this.$modal.on('show.bs.modal', () => {
      if (this.$currentResult) {
        this.$currentResult.find('.js-grade-btn').attr('disabled', 'true');
      }
    });

    this.$modal.on('hide.bs.modal', () => {
      if (this.$currentResult) {
        this.$currentResult.find('.js-grade-btn').removeAttr('disabled');
        this.$currentResult.find('.js-info-box').css({ top: 0, height: "60px"});
        this.$currentResult = null;
      }
    });

    this.$content.on('click', '.js-wall-post', function() {
      $(this).closest('.js-wall-stu-info').find('.js-wall-image').click();
    });

    const _this = this;
    this.$content.on('click', '.js-grade-btn', function() {
      _this.$currentResult = $(this).closest('.js-wall-stu-info');
    });
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

  saveMaterialListener() {
    this.$content.on('click', '.js-save-photo', function() {
      const contentId = $(this).data('content-id');
      const url = `/practice/content/${contentId}/material/save`;

      $.post(url)
        .success(() => {
          const $this = $(this);
          const $elem = $this.find('.js-save-photo-tip');
          $this.removeClass('js-save-photo');
          $this.addClass('opacity-5 cursor-not-allowed');
          $this.parent().removeClass('active');

          $elem.text('已保存');
          notify('success', '保存成功');
        });
    });
  }
}

new Practice();