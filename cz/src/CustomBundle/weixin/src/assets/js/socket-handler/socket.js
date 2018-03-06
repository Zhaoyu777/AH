import * as types from '@/vuex/mutation-types';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';

const DISCONNECT_COUNT_DOWN = 3;
let DISCONNECT_COUNT = 0;
const socket = {
  // socket 连接
  [allSocketTypes.SOCKET_CONNECT]() {
    console.log('socket--已连接');
    store.commit(types.SOCKET_DISCONNECT_REFRESH, false);
  },
  // socket 断开连接
  [allSocketTypes.SOCKET_DISCONNECT]() {
    console.log('socket--已断开');
  },
  // socket 连接错误
  [allSocketTypes.SOCKET_CONNECT_ERROR](error) {
    console.log('socket错误：', error);
    DISCONNECT_COUNT = DISCONNECT_COUNT + 1;
    if (DISCONNECT_COUNT > DISCONNECT_COUNT_DOWN) {
      store.commit(types.SOCKET_DISCONNECT_REFRESH, true);
    }
  },
};

export default socket;