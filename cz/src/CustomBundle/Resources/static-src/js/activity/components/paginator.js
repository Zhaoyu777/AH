
export default class Paginator {
  constructor(options) {
    this.el = options.el;
    this.loading = false;
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    this.turnPage();
  }

  turnPage() {
    // 视频和音频找不到this.el的DOM，所以做一个兼容
    let $body = $("body");
    let elem = $body.find(this.el);
    if (elem.length > 0) {
      elem.on('click', (e) => {
        this.registerEvent(e)
      });
    } else {
      $body.on('click', this.el, (e) => {
        this.registerEvent(e);
      })
    }
  }

  registerEvent(e) {
    if (this.loading) {
      return;
    }

    $(this.el).parent().css('opacity','.65');
    this.loading = true;
    let $target = $(e.currentTarget);
    $("iframe#task-content-iframe", window.parent.document).attr('src', $target.data('url'));

    if (window.top.taskPipe) {
      window.top.taskPipe._changeInterval($target.data('event-url'));
    }
    let obj = $('.js-task-list', window.parent.document).find('li.active');
    $(obj).removeClass('active');
    let step = $target.data('step');
    let currentElem;

    if (step === 'preview') {
      currentElem = ($(obj).prevAll('.js-task-content'))[0];
    }
    if (step === 'next') {
      currentElem = ($(obj).nextAll('.js-task-content'))[0];
    }

    $(currentElem).addClass('active');

    let text = $(currentElem).find('.js-lesson-task').text();
    let taskNum = $(currentElem).find('.task-index').text();
    text = text.trim();
    let $taskName = $(".js-task-name", window.parent.document);
    $taskName.text(`任务${taskNum}：${text}`);
    $taskName.attr('title', text);
  }
}