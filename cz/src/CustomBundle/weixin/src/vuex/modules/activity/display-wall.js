import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.DISPLAY_WALL_STATUS](state, status) {
    state.displayWall.status = status;
  },
  [types.DISPLAY_WALL_RANDOM_JOIN](state, data){
    state.displayWall.hasGroup = data;
  },
  [types.DISPLAY_WALL_CLEAR](state) {
    state.displayWall.status = null;
    state.displayWall.groupWay = null;
    state.displayWall.submitWay = null;
    state.displayWall.groups = null;
    state.displayWall.selfData = [];
    state.displayWall.hasGroup = null;
  },
  [types.DISPLAY_WALL_INIT](state, displayWallData) {
    state.displayWall.status = displayWallData.status;
    state.displayWall.groupWay = displayWallData.groupWay;
    state.displayWall.submitWay = displayWallData.submitWay;
    state.displayWall.groups = displayWallData.groups;
    state.displayWall.selfData = displayWallData.selfData;
    state.displayWall.hasGroup = displayWallData.hasGroup;
  },
  [types.DISPLAY_WALL_LIKE](state, item) {
    state.displayWall.groups.map(group => {
      if (!group.results) {
        return;
      }
      group.results.map(result => {
        if (result.content.id === item.content.id) {
          result.content.likeNum = parseInt(item.content.likeNum);
          if (state.userId === item.likeUserId) {
            result.isStar = true;
          }
        }
      })
    })
  },
  [types.DISPLAY_WALL_CANCEL_LIKE](state, item) {
    state.displayWall.groups.map(group => {
      if (!group.results) {
        return;
      }
      group.results.map(result => {
        if (result.content.id === item.content.id) {
          result.content.likeNum = parseInt(item.content.likeNum);
          if (state.userId === item.likeUserId) {
            result.isStar = false;
          }
        }
      })
    })
  },
  [types.SET_DISPLAY_WALL_REVIEW_DIALOG](state, isDialogShow) {
    state.displayWall.isDialogShow = isDialogShow;
  },
  [types.SET_DISPLAY_WALL_REVIEW_CURRENT_ID](state, currentReviewId) {
    state.displayWall.currentReviewId = currentReviewId;
  },
  [types.DISPLAY_WALL_REVIEW](state, res) {
    state.displayWall.groups.map(group => {
      if (group.groupId === res.groupId) {
        group.results.map(result => {
          if (result.id === res.resultId) {
            result.score = res.score;
          }
        })
      }
    })
  },
  [types.DISPLAY_WALL_POST](state, item) {
    if (state.displayWall) {
      state.displayWall.groups.map(group => {
        if (group.results) {
          group.results.map(result => {
            if (result.id === item.result.id) {
              result.content.postNum = parseInt(item.postNum);
            }
          })
        }
      })
    }
  },
  [types.DISPLAY_WALL_RESULT](state, res) {
    if (!state.displayWall) {
      return;
    }
    state.displayWall.groups.map(group => {
      if (group.groupId === res.result.groupId) {
        if (Array.isArray(group.results) && group.results.length) {
          let results = group.results.slice(0);
          const index = results.findIndex(result => {
            return result.id === res.result.id;
          });
          if (index < 0) {
            results.push(res.result);
          } else {
            results[index] = res.result;
          }
          group.results = results;
        } else {
          group.results = [res.result];
        }
        group.replyCount = group.results.length;
      }
    });
  }
};

export const actions = {
  [types.DISPLAY_WALL_START]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.start(courseId, lessonId, taskId));
  },
  [types.DISPLAY_WALL_END]({commit}, {taskId, activityId}) {
    return Vue.http.get(api.displayWall.end(taskId, activityId)).then((res) => {
      commit(types.DISPLAY_WALL_STATUS, res.data);
    })
  },
  [types.DISPLAY_WALL_INIT]({commit}, taskId) {
    return Vue.http.get(api.displayWall.result(taskId)).then((res) => {
      commit(types.DISPLAY_WALL_INIT, res.data);
    })
  },
  [types.DISPLAY_WALL_CLEAR]({commit}) {
    commit(types.DISPLAY_WALL_CLEAR);
  },
  [types.DISPLAY_WALL_LIKE]({commit}, item) {
    // 数据更新放入到socket中
    return Vue.http.get(api.displayWall.like(item.content.id));
  },
  [types.DISPLAY_WALL_CANCEL_LIKE]({commit}, item) {
    // 数据更新放入到socket中
    return Vue.http.get(api.displayWall.cancelLike(item.content.id)).then(res => {
      commit(types.DISPLAY_WALL_CANCEL_LIKE, 'local');
    });
  },
  [types.SET_DISPLAY_WALL_REVIEW_DIALOG]({commit}, isDialogShow) {
    commit(types.SET_DISPLAY_WALL_REVIEW_DIALOG, isDialogShow);
  },
  [types.SET_DISPLAY_WALL_REVIEW_CURRENT_ID]({commit}, currentReviewId) {
    commit(types.SET_DISPLAY_WALL_REVIEW_CURRENT_ID, currentReviewId);
  },
  [types.DISPLAY_WALL_REVIEW]({commit}, {resultId, groupWay, submitWay, score, remark}) {
    return Vue.http.get(api.displayWall.remark(resultId, groupWay, submitWay), {
      params: {
        score,
        remark
      }
    })
  },
  [types.DISPLAY_WALL_RANDOM_JOIN]({commit}, {taskId, groupId}) {
    return Vue.http.post(api.activity.join(taskId, groupId),
      qs.stringify({}), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true
      }).then((res) => {
      if (!res.data.message) {
        commit(types.DISPLAY_WALL_RANDOM_JOIN, true);
        return res;
      }
    })
  },
  [types.DISPLAY_WALL_POST]({commit}, item){
    commit(types.DISPLAY_WALL_POST, item);
  }
};
