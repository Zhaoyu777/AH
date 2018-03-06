import displayWallSocket from './display-wall-socket';

class DisplayWall {
  constructor() {
    this.index = 0;
    this.$modal = $("#modal");
    this.$content = $('.js-cz-activity-content');
  }

  init() {
    new displayWallSocket();
    const $elem = $('#display-wall-activity');
    $elem.perfectScrollbar();
    $elem.perfectScrollbar('update');

    this.$modal.on('show.bs.modal', function() {
      $elem.perfectScrollbar('destroy');
    });

    this.$modal.on('hide.bs.modal', function() {
      $elem.perfectScrollbar();
    });

    this.$content.on('click', '.js-rend-call', (e) => {
      let $target = $(e.target);
      $.post($target.data('url'));
    });

    $('body').on('click', '.js-grade-btn', (e) => {
      $(e.target).addClass('active');
    });

    this.$content.on('click', '.js-wall-post', function() {
      $(this).closest('.js-wall-stu-info').find('.js-wall-image').click();
    });
  }
}

const displayWall = new DisplayWall();
displayWall.init();