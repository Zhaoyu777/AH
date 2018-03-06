export default class Back {
  constructor(options) {
    this.el = options.el;
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    this.back();
  }

  back() {
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
    let self = e.currentTarget;
    let url = $(self).data('url');

    console.log(self);

    top.document.location.href = url;
  }
}