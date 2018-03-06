import * as types from '@/vuex/mutation-types';
import * as allActivityTypes from '@/assets/js/config/activityTypes';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';
import displayWall from './socket-handler/displayWall';
import practice from './socket-handler/practice';
import signIn from './socket-handler/signIn';
import activityStatus from './socket-handler/activityStatus';
import socket from './socket-handler/socket';
import lessonStatus from './socket-handler/lessonStatus'

const typed_socket_handler = {
  ...displayWall,
  ...practice,
  ...signIn,
  ...activityStatus,
  ...socket,
  ...lessonStatus,
  // 一句话问答结果
  [allSocketTypes.ONE_SENTENCE_RESULT](res) {
    const activity = this.$store.state.activity,
      taskId = this.$route.params.taskId;
    let oneSentenceData = activity.oneSentence,
      results = oneSentenceData.results.slice(0),
      userId = this.$getCookie('userId'),
      index = -1;

    if (taskId === res.taskId && userId !== res.result.userId) {
      if (oneSentenceData.isGrouped) {
        const groupId = res.groupId;
        res.result.createTime = (new Date()).getTime(); // Date.now();
        index = results.findIndex(elem => {
          const elemGroupId = elem.groupId || elem.replys[elem.replys.length - 1].groupId;
          return groupId === elemGroupId;
        });
        if (index > -1 && results[index].replys && Array.isArray(results[index].replys)) {
          results[index].replys.unshift(res.result);
          results[index].currentReplyCount = results[index].replys.length;
        } else {
          results[results.length] = {
            groupId: groupId,
            replyCount: res.replyCount,
            currentReplyCount: 1,
            replys: [res.result]
          };
        }
      } else {
        if (Array.isArray(results[0])) {
          results[0].unshift(res.result);
        } else {
          results = [
            [res.result]
          ]
        }
      }
      this.$store.state.activity.oneSentence.results = results;
    }
  },
  // 抢答到的学生数据
  [allSocketTypes.RACE_ANSWER_RESULT](res) {
    const activityId = this.$route.params.activityId;
    if (activityId === res.raceAnswer.activityId) {
      let raceAnswer = this.$store.state.activity.raceAnswer;
      let results = raceAnswer.results.slice(0);
      results.push(res.result);
      results = results.sort(function(item1, item2) {
        return item1.resultId - item2.resultId;
      });
      raceAnswer.results = results;
    }
  },
  // 随机点名(点名答题)
  [allSocketTypes.RAND_ROLLCALL_START](res) {
    const role = this.$store.state.activity.activityData.role;
    const taskId = this.$route.params.taskId;
    if (taskId === res.taskId) {
      let userId = this.$getCookie('userId');
      if (res.result.userId === userId || role === 'teacher') {
        store.commit(types.ROLLCALL_TASK_STATUS, true);
        let setTime = setTimeout(() => {
          store.commit(types.ROLLCALL_STATUS, true);
          if (role === 'teacher') {
            let data = {
              student: res.result,
              canRand: res.canRand
            };
            store.commit(types.ROLLCALL_RAND, data);
            store.commit(types.ROLLCALL_TASK_STATUS, false);
            this.$vux.toast.show({
              text: '点名成功',
            });
          }
          clearTimeout(setTime);
        }, 3000);
      }
    }
  },
  // 头脑风暴结果推送
  [allSocketTypes.BRAIN_STORM_CHANGE](res) {
    const activityId = this.$store.state.activity.activityData.id;
    if (activityId === res.brainStorm.activityId) {
      res.brainStorm.truename = res.result.truename;
      res.brainStorm.avatar = res.result.avatar;
      let userId = this.$getCookie('userId');
      store.commit(types.BRAIN_STORM_RESULTS, res);
    }
  },
  // （头脑风暴和展示墙）随机分组 - 选择分组
  [allSocketTypes.JOIN_TASK_GROUP](res) {
    const activityData = this.$store.state.activity.activityData,
      activityId = activityData.id;
    let groups = [];
    if (activityId !== res.activityId) {
      return;
    }

    let userId = this.$getCookie('userId');

    const activityType = activityData.activityType;
    if (activityType === allActivityTypes.BRAIN_STORM.key) {
      groups = this.$store.state.activity.brainStorm.groups;
      if (userId === res.userId) {
        this.$store.state.activity.brainStorm.hasGroup = true;
      }
    } else if (activityType === allActivityTypes.DISPLAY_WALL.key) {
      groups = this.$store.state.activity.displayWall.groups;
      if (userId === res.userId) {
        this.$store.state.activity.displayWall.hasGroup = true;
      }
    }
    groups.map(group => {
      if (group.groupId === res.groupId) {
        group.memberCount = res.memberCount;
      }
    })
  },
  // 评分
  [allSocketTypes.TASK_RESULT_REMARK](res) {
    const activityData = this.$store.state.activity.activityData;
    if (!activityData) return;

    const activityId = activityData.id,
      role = activityData.role;
    if (activityId === res.activityId) {
      let taskData = this.$store.state.activity[res.type];
      const score = res.score,
        userId = this.$getCookie('userId');

      if (res.type === allActivityTypes.DISPLAY_WALL.key) {
        store.commit(types.DISPLAY_WALL_REVIEW, res);
      } else {
        taskData.socre = score;
      }

      if (role == 'student') {
        if (res.userIds.indexOf(userId) !== -1) {
          this.$vux.toast.show({
            type: 'text',
            text: res.message,
            width: '80%'
          });
        }
      }
    }
  },
};

export default typed_socket_handler;