import { dateFormat, cookie } from 'vux';
import api from './api';
import * as types from '@/vuex/mutation-types';

export const setCookie = (name, value, expires = 2) => {
  cookie.set(name, value, {
    expires,
  });
};

export const getCookie = (name) => {
  return cookie.get(name);
};

export const dateFormatFn = (date) => {
  return dateFormat(parseInt(date, 10) * 1000, 'YYYY-MM-DD HH:mm:ss');
};

// 初始化微信sdk
export const jssdk = function(callback) {
  this.$http.get(api.jsjdk()).then((response) => {
    this.$wechat.config({
      // debug: true,
      appId: response.data.appId,
      timestamp: response.data.timestamp,
      nonceStr: response.data.nonceStr,
      signature: response.data.signature,
      jsApiList: ['getLocation', 'previewImage', 'chooseImage', 'uploadImage']
    });
    typeof callback === 'function' && callback();
  }, (response) => {
    console.log("js-sdk:",response.data);
  });
};

export const endLoading = function() {
  this.$store.dispatch(types.UPDATE_LOADING_STATUS, { isLoading: false });
}

export const isLoading = function() {
  this.$store.dispatch(types.UPDATE_LOADING_STATUS, { isLoading: true });
}

export const ajaxError = function() {
  this.$vux.toast.show({
    type: 'warn',
    text: '请求出错'
  })
}

export const ajaxMessage = function(message) {
  this.$vux.toast.show({
    type: 'warn',
    text: message
  })
}

export const numTransStr = function(num) {
  return String.fromCharCode(65 + num);
}

export const ignoreTag = function(str) {
  str = str.replace(/<p>/,"");

  return str.replace(/<\/p>/,"");
}

// utils.goToTask.call(this, res);
export const goToTask = function(res) {
  const currentActivityType = res.activityType;
  const courseId = res.courseId;
  const lessonId = res.lessonId;
  const taskId = res.taskId;
  const currentRouteTaskId = this.$route.params.taskId;
  let activityId = res.activityId;
  let currentActivityRoute;

  if(res.taskId === '0') {
    activityId = '0';
    currentActivityRoute = {
      name: `learning-activity`,
      params: {courseId, lessonId, taskId, activityId}
    };
  } else {
    currentActivityRoute = {
      name: `learning-${currentActivityType}`,
      params: {courseId, lessonId, taskId, activityId}
    }
  }

  // 在教室内时
  if(currentRouteTaskId !== res.taskId) {
    this.$router.replace(currentActivityRoute);
  }
}
