import "babel-polyfill";
import Vue from "vue";
import FastClick from "fastclick";
import VueRouter from "vue-router";
import App from "./App.vue";
import routes from "./router";
import store from "./vuex/store";
import VueScroller from "vue-scroller";
// 待删除
import VueQuillEditor from "vue-quill-editor";
import VueHtml5Editor from "vue-html5-editor";
import VueBus from "vue-bus";

import {
  AjaxPlugin,
  WechatPlugin,
  ConfirmPlugin,
  LoadingPlugin,
  ToastPlugin
} from "vux";

import * as utils from "@/assets/js/utils";
import { getSocket, closeSocket } from "@/assets/js/socket";
import * as types from "@/vuex/mutation-types";
import { generatePipe } from "@/assets/js/task-pipe";

Object.assign(Vue.prototype, {
  $endLoading: utils.endLoading,
  $isLoading: utils.isLoading,
  $ajaxError: utils.ajaxError,
  $jssdk: utils.jssdk,
  $dateFormatFn: utils.dateFormatFn,
  $setCookie: utils.setCookie,
  $getCookie: utils.getCookie,
  $ajaxMessage: utils.ajaxMessage,
  $numTransStr: utils.numTransStr,
  $ignoreTag: utils.ignoreTag,
  $getSocket: getSocket,
  $closeSocket: closeSocket,
  $generatePipe: generatePipe
});

Vue.use(AjaxPlugin);
if (window.WeixinJSBridge) {
  window.WeixinJSBridge = undefined; //解决企业微信手机app的问题
}
Vue.use(WechatPlugin);
Vue.use(ConfirmPlugin);
Vue.use(LoadingPlugin);
Vue.use(ToastPlugin);
Vue.use(VueScroller);
Vue.use(VueBus);
Vue.use(VueQuillEditor);
Vue.use(VueHtml5Editor, {
  showModuleName: true,
  image: {
    sizeLimit: 512 * 1024,
    compress: true,
    width: 500,
    height: 500,
    quality: 80
  }
});

Vue.use(VueRouter);

const router = new VueRouter({
  routes
});

router.beforeEach((to, from, next) => {
  // 判断是否登录
  const isLogin = !!utils.getCookie("role") && !!utils.getCookie("userId");
  const isDev = process.env.NODE_ENV !== "production";

  store.dispatch(types.HOST_NAME).then(res => {
    document.getElementById("host-name").innerHTML = res.data;
  });

  if (!isLogin && isDev && to.name !== "login") {
    next({ name: "login" });
  } else if (!isDev && to.name !== "noAccount") {
    store.dispatch(types.CURRENT_USER_INFO).then(
      res => {
        utils.setCookie("role", res.data.role);
        utils.setCookie("userId", res.data.userId);
        utils.setCookie("host", res.data.host);
        utils.setCookie("XSRF-TOKEN", res.data.csrf_token);

        next();
      },
      res => {
        store.dispatch(types.UPDATE_LOADING_STATUS, { isLoading: false });
        next({ name: "noAccount" });
      }
    );
  } else {
    next();
  }
});

// 消除点击延时提高程序的运行效率
FastClick.attach(document.body);

Vue.config.productionTip = false;

// 设置根元素字体大小
let ww =
  window.innerWidth ||
  document.documentElement.clientWidth ||
  document.body.clientWidth;
ww = ww / 375 * 16;
document.documentElement.style.fontSize = (ww > 16 ? 16 : ww) + "px";

/* eslint-disable no-new */
new Vue({
  router,
  store,
  render: h => h(App)
}).$mount("#app-box");