import * as types from './mutation-types';
import * as utils from '@/assets/js/utils';

const mutations = {
  [types.USER_LOGIN](state) {
    state.role = utils.getCookie('role');
    state.host = utils.getCookie('host');
    state.userId = utils.getCookie('userId');
  },
  [types.UPDATE_LOADING_STATUS] (state, payload) {
    state.isLoading = payload.isLoading;
  },
  [types.CURRENT_USER_INFO](state, payload) {
    state.role = payload.role;
    state.host = payload.host;
  },
  [types.SOCKET_DISCONNECT_REFRESH](state, payload) {
    state.isRefresh = payload;
  }
};

export default mutations;
