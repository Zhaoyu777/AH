import notify from 'common/notify';

  class CancelBtn {
    constructor(options) {
      this.handleEl = options.handleEl;
      this.cancelEl = options.cancelEl;
      this.removeEl = options.removeEl;
      this.remove();

    }
    remove() {
      this.handleEl.on('click', this.cancelEl, (e) => {
        let $target = $(e.currentTarget),
        url = $target.data('url');
        if (!confirm(Translator.trans('是否确定删除该课程？'))) {
          return;
        }
        $.post(url, (res) => {
          if (res == true) {
            notify('success',Translator.trans('删除成功'));
            console.log(this.removeEl);
            $target.closest(this.removeEl).remove();
          } else {
            notify('danger',Translator.trans('删除失败'));
          }
        })
      });
    }
  }

export default CancelBtn;