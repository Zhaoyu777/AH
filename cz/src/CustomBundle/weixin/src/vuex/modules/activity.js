import Vue from 'vue';
import api from '@/assets/js/api';
import * as types from '../mutation-types';
import * as rollcall from './activity/rollcall';
import * as displayWall from './activity/display-wall';
import * as displayWallDetail from './activity/display-wall-detail';
import * as oneSentence from './activity/one-sentence';
import * as raceAnswer from './activity/race-answer';
import * as questionnaire from './activity/question-naire';
import * as brainStorm from './activity/brain-storm';
import * as testpaper from './activity/testpaper';
import * as randomTestpaper from './activity/random-testpaper';
import * as practice from './activity/practice';
import * as practiceDetail from './activity/practice-detail';
import * as utils from '@/assets/js/utils';
import * as practiceWork from './activity/practice-work';
import { activityMenuData } from '@/assets/js/data';
import { ACTIVITY_NAME, OLD_ACTIVITY_NAME } from '@/assets/js/config/activityTypes';

export default {
  state: {
    userId: utils.getCookie('userId'),
    activityData: {},
    currentActivity: {
      courseId: null,
      lessonId: null,
      taskId: null,
      activityType: null,
      lastUpdateTime: 0,
    },
    activityName: ACTIVITY_NAME,
    oldActivityName: OLD_ACTIVITY_NAME,
    rollcall: {
      stuData: [],
      canRand: true,
      isRollcall: null,
      isDialogShow: false,
      currentReviewId: null,
      score: 0,
      status: false
    },
    displayWall: {
      groupWay: null,
      submitWay: null,
      groups: null,
      selfData: [],
      isDialogShow: false,
      currentReviewId: null,
      status: null,
      hasGroup: null
    },
    displayWallDetail: {
      contentData: null,
      postData: null,
      placeholder: '评论',
      replyName: null,
      parentId: null,
    },
    oneSentence: {
      status: null,
      isGrouped: null,
      results: null,
      content: null,
      isAnswer: null,
      score: null,
      resultId: null,
    },
    raceAnswer: {
      status: null,
      time: null,
      results: null,
      currentReviewId: null,
      isRaced: null,
      isDialogShow: false
    },
    questionnaire: {
      status: null,
      taskStatus: null,
      resultStatus: null,
      questions: false,
      resultId: null,
      questionResults: null,
      result: null,
      memberNum: null,
      actualNum: null
    },
    brainStorm: {
      status: null,
      hasGroup: null,
      submitWay: null,
      groups: null,
      content: null,
      currentReviewId: null,
      isDialogShow: false,
      isAnswer: false
    },
    testpaper: {
      status: null,
      items: null,
      analysis: false,
      result: [],
      statis: [],
      accuracy: [],
      questions: [],
      menuData: {}
    },
    randomTestpaper: {
      questionIds: [],
      accuracy: [],
      result: null
    },
    practiceWork: {
      result: [],
      data: {},
      file: [],
      pictureUrl: '',
      status: ''
    },
    practice: {
      taskStatus: '',
      results: [],
    },
    practiceDetail: {
      contentData: null,
      postData: null,
      placeholder: '评论',
      replyName: null,
      parentId: null,
    },
  },
  mutations: Object.assign({},
    {
      [types.ACTIVITY_CLEAR](state) {
        state.activityData = {};
      },
      [types.ACTIVITY_INIT](state, activityData) {
        state.activityData = activityData
      },
      [types.ACTIVITY_STATUS_CHANGE](state, res) {
        state[res.activityType].status = res.status;
        state[res.activityType].taskStatus = res.status;
      },
      [types.CURRENT_ACTIVITY](state, currentActivity) {
        state.currentActivity = currentActivity;
      }
    },
    rollcall.mutations,
    displayWall.mutations,
    displayWallDetail.mutations,
    oneSentence.mutations,
    raceAnswer.mutations,
    questionnaire.mutations,
    brainStorm.mutations,
    randomTestpaper.mutations,
    testpaper.mutations,
    practiceWork.mutations,
    practice.mutations,
    practiceDetail.mutations,
  ),
  actions: Object.assign({},
    {
      [types.ACTIVITY_INIT]({ commit }, { courseId, lessonId, taskId, activityId }) {
        if(!courseId) {
          return;
        }
        commit(types.UPDATE_LOADING_STATUS, { isLoading: true });
        return Vue.http.get(api.activity.detail(courseId, lessonId, activityId), {
          params: {
            taskId
          }
        }).then((res) => {
          commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
          if (!res.data.message) {
            commit(types.ACTIVITY_INIT, res.data);
          }

          return res;
        })
      },
      [types.ACTIVITY_CLEAR]({ commit }) {
        commit(types.ACTIVITY_CLEAR);
      },
      [types.CURRENT_ACTIVITY]({ commit }, { courseId }) {
        // 限制当前活动数据，至多1s获取一次
        // if ((Date.now() - lastUpdateTime) >= 1000) {
          return Vue.http.get(api.studentTask.current(courseId)).then((res) => {
            res = Object.assign({}, res.data, { courseId: courseId, lastUpdateTime: Date.now() });
            commit(types.CURRENT_ACTIVITY, res);
            return res;
          });
        // }
      }
    },
    rollcall.actions,
    displayWall.actions,
    displayWallDetail.actions,
    oneSentence.actions,
    raceAnswer.actions,
    questionnaire.actions,
    brainStorm.actions,
    randomTestpaper.actions,
    testpaper.actions,
    practiceWork.actions,
    practice.actions,
    practiceDetail.actions
  )
}
