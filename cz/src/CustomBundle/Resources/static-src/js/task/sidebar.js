import Emitter from "component-emitter";
import { chapterAnimate } from 'app/common/widget/chapter-animate';
import $clamp from '../../common/clamp';
import notify from 'common/notify';

// 本文件中，所有注释代码一直保留至项目交付 !!!!!!!
export default class TaskSidebar extends Emitter {
  constructor({ element, url, mode }) {
    super();
    this.url = url;
    this.mode = mode;
    this.isManualOperation = true;
    this.element = $(element);
    this.init();
  }

  init() {
    this.fixIconInChrome();
    this.fetchPlugins()
      .then((plugins) => {
        this.plugins = plugins;
        this.renderToolbar();
        this.renderPane();
        this.element.hide().show();
        this.bindEvent();
        this.element.find('#dashboard-toolbar-nav .js-task-list').click(); // 初始化时，展示任务列表
      })
      .fail(error => {});
  }

  fetchPlugins() {
    return $.post(this.url);
  }

  overflowHidden() {
    let doms = document.getElementsByClassName('js-task-item');
    let dom;
    for (let i = 0; i < doms.length; i++) {
      dom = doms[i].getElementsByClassName('js-lesson-task-name')[0];
      if(dom) {
        $clamp(dom, {clamp: 2, useNativeClamp: false});
      }
    }
  }

  // 修复字体图标在chrome下，加载两次从而不能显示的问题
  fixIconInChrome() {
    let html = `<i class="es-icon es-icon-chevronleft"></i>`;
    this.element.html(html);
  }

  renderToolbar() {
      let html = `
    <div class="dashboard-toolbar">
      <ul class="dashboard-toolbar-nav text-bold clearfix" id="dashboard-toolbar-nav">
        ${this.plugins.reduce((html, plugin) => {
            return html += `
              <li class="fr clearfix ${plugin.code} js-${plugin.code}" data-plugin="${plugin.code}" data-url="${plugin.url}">
                <span class="fl split-cutline"></span>
                <div class="nav-item fl plr15 text-18">
                  <i class="cz-icon cz-icon-mission ${plugin.icon}"></i>
                  <span>${plugin.name}</span>
                </div>
              </li>`;
          }, '')}
      </ul>
    </div>`;
    this.element.html(html);
  }

  renderPane() {
    let html = this.plugins.reduce((html, plugin) => {
      return html += `<div data-pane="${plugin.code}" class=" ${plugin.code}-pane js-sidebar-pane" ><div class="${plugin.code}-pane-body js-sidebar-pane-body"></div></div>`;
    }, '');
    this.element.append(html);
  }

  bindEvent() {
    const $element = this.element;
    const mode = this.mode;
    $element.find('#dashboard-toolbar-nav').on('click', 'li', (event) => {
      let $btn = $(event.currentTarget);
      let pluginCode = $btn.data('plugin');
      let url = $btn.data('url');
      let $pane = this.element.find(`[data-pane="${pluginCode}"]`);
      let $paneBody = $pane.find('.js-sidebar-pane-body');

      if(mode === 'preview' && pluginCode === 'sign-list') {
        notify('warning', '预览中，不可操作……');
        return;
      }

      if (pluginCode === undefined || url === undefined) {
        return;
      }

      if(this.isManualOperation){
          this.operationContent($btn);
      }

      if ($btn.data('loaded')) {
        return;
      }

      $btn.data('loaded', true);

      $.get(url)
        .then(html => {
          $paneBody.html(html);
          this.overflowHidden();
          $pane.perfectScrollbar({wheelSpeed: 20});
          this.taskListHoverEvent(html);
          this.listEvent();
          this.isManualOperation = true;
        })
        .fail(() => {
          $btn.data('loaded', false);
        });
    });

    $element.find('[data-pane=task-list]').mouseenter((event) => {
      let $btn = $(event.currentTarget);
      $element.addClass('spread');
      $element.find('#dashboard-toolbar-nav li').removeClass('active');
      $element.find('#dashboard-toolbar-nav li[data-plugin=task-list]').addClass('active');
      $element.find('[data-pane]').removeClass('main');
      $btn.addClass('main');
    })

    $element.find('[data-pane=task-list]').mouseleave((event) => {
      let $btn = $(event.currentTarget);
      $element.removeClass('spread');
      $btn.removeClass('main');
      $element.find('#dashboard-toolbar-nav li[data-plugin=task-list]').removeClass('active');
    })


    $element.on('click', '.js-close-btn', function() {
      $element.removeClass('spread');
      let pane = $(this).closest('.js-sidebar-pane').data('pane');
      $element.find(`li[data-plugin=${pane}]`).removeClass('active');
    });



    // $('body').on('click', function(e) {
    //   const $target = $(e.currentTarget);
    //   const $parent = $('#dashboard-toolbar-nav');
    //   const $currentSideBar = $parent.find('li.active');

    //   if($currentSideBar.length === 0 || $currentSideBar.hasClass('js-sign-list') || $currentSideBar.hasClass('js-task-list')) {
    //     return;
    //   }

    //   $currentSideBar.removeClass('active');
    //   $currentSideBar.closest('#dashboard-sidebar').removeClass('spread');

    // })
  }

  taskListHoverEvent() {
    let key;
    const $items = $('.js-task-list').find('.js-lesson-task');

    $items.hover(
      function() {
        key = setTimeout(() => {
          $(this).closest('.js-task-item').addClass('task-item-hover');
        }, 200);
      },
      function() {
        $(this).closest('.js-task-item').removeClass('task-item-hover');
        key && clearTimeout(key);
      }
    );
  }

  operationContent($btn) {
    if ($btn.hasClass('active')) {
      $('#dashboard-sidebar').removeClass('spread');
      // this.foldContent();
      $btn.removeClass('active');
    } else {
      $('#dashboard-sidebar').addClass('spread');
      this.element.find('#dashboard-toolbar-nav li').removeClass('active');
      $btn.addClass('active');
      this.element.find('[data-pane]').removeClass('main');
      this.element.find(`[data-pane="${$btn.data('plugin')}"]`).addClass('main');
      // this.popupContent();
    }
  }

  // popupContent(time = 500) {
  //   let width = $('#dashboard-sidebar').width();
  //   let content_right = width +'px';
  //
  //   this.emit('popup', content_right, time);
  // }
  //
  // foldContent(time = 500) {
  //   let content_right = '0';
  //
  //   this.emit('fold', content_right, time);
  // }

  reload() {
    const $currentPane = this.element.find('.js-sidebar-pane:visible');
    const pluginCode = $currentPane.data('pane');
    $currentPane.undelegate();
    this.element.find('#dashboard-toolbar-nav').children(`[data-plugin="${pluginCode}"]`)
      .data('loaded', false)
      .click();
    this.isManualOperation = false;
  }

  listEvent() {
    if($('.js-sidebar-pane:visible .task-list-pane-body').length) {
      chapterAnimate();
    }
  }
}