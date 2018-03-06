import Paginator from './components/paginator';
import notify from 'common/notify';

class Activity {
  constructor() {
    this.mode = $('#js-hidden-data [name="mode"]', window.parent.document).val();
  }

  init() {
    this.clearScroll();
    this.hideSideBar();
    this.initPaginator();
    if(this.mode === 'preview') {
      this.previewTips();
    }
  }

  clearScroll() {
    // 清除iframe内html和body的滚动条, 限制在包含了活动的iframe内使用
    // $("body,html").css('overflow','hidden');
  }

  hideSideBar() {
    // iframe内部点击时，隐藏上课页面的侧边栏
    $("body").on('click', () => {
      const $navBarParent = $("#dashboard-sidebar", window.parent.document);
      const $childmenu = $navBarParent.find('#dashboard-toolbar-nav').find('li.active');

      if($childmenu.length > 0) {
        $navBarParent.removeClass('spread');
        $childmenu.removeClass('active');
      }
    });
  }

  initPaginator() {
    const paginator = new Paginator({
      el: '.js-turn-btn'
    });
    paginator.init();
  }

  previewTips() {
    $('body').on('click', '.js-rend-call', () => {
      notify('warning', '预览中，不可操作……');
    });
  }
}

const activity = new Activity();
activity.init();