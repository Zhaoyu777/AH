<template>
  <main class="app-main">
    <div class="teacher-sign-reminder clearfix" v-if="signInTime > 0 && signInStatus == 'start' && userSignStatus !== 'attend'">
      <img :src="signIconCover" class="teacher-sign-reminder__icon">
      老师已发起签到，快来签到吧～
      <router-link class="sign-btn teacher-sign-reminder__btn" :to="{ name: 'signInTime', params: { lessonId: lessonId, timeId: signInTime } }">
        <!-- <img :src="signBtnCover" class="teacher-sign-reminder__btn"> -->
        第{{signInTime}}次签到
      </router-link>
    </div>
    <div class="refresh-btn" @click="refresh" v-if="isRefresh">
      <i class="cz-icon cz-icon-Refresh"></i>
      <div>刷新</div>
    </div>
    <div v-if="currentActivity.taskId === '0' || oldTask ">
      <div class="activity-type" v-if="currentActivity">
        <img class="type-icon" :src="oldActivityIcon" :srcset="oldActivityIcon2" />
        <p class="type-name text-overflow">{{ activityTitle }}</p>
      </div>
      <div class="not-teaching-tips activity-body">
        <img :src="screenCover">
        <span class="tips-name">{{ desc }}</span>
      </div>
    </div>
    <div v-else>
      <div class="activity-type" v-if="currentActivity">
        <img class="type-icon" :src="activityIcon" :srcset="activityIcon2" />
        <span class="type-name text-overflow">{{ activityTitle }}</span>
      </div>
      <router-view></router-view>
    </div>
  </main>
</template>

<script>
  import {mapState, mapActions, mapMutations} from 'vuex';
  import * as types from '@/vuex/mutation-types';
  import {screenCover, signBtnCover, signIconCover, activityIcon, activityIcon2, oldActivityIcon, oldActivityIcon2} from '@/assets/js/data';
  import api from '@/assets/js/api';
  import * as allActivityTypes from '@/assets/js/config/activityTypes';

  export default {
    created() {
      this.fetchData();
    },
    data() {
      return {
        screenCover,
        signBtnCover,
        signIconCover,
        activityIcon,
        activityIcon2,
        oldActivityIcon,
        oldActivityIcon2,
        oldTask: false,
        desc: '请注意观看大屏幕'
      }
    },
    beforeDestroy() {
      this.$closeSocket();
    },
    watch: {
      '$route': 'fetchData'
    },
    computed: {
      ...mapState({
        signInTime: state => state.study.signInTime,
        signInStatus: state => state.study.signInStatus,
        userSignStatus: state => state.signIn.signInStatus,
        currentActivity: state => state.activity.currentActivity,
        lessonId: state => state.activity.currentActivity.lessonId,
        activityName: state => state.activity.activityName,
        oldActivityName: state => state.activity.oldActivityName,
        isRefresh: state => state.isRefresh,
        activityTitle: state => state.activity.currentActivity.activityTitle
      })
    },
    methods: {
      ...mapActions([
        types.STUDY_INIT,
        types.CURRENT_ACTIVITY,
        types.ACTIVITY_INIT,
        types.ACTIVITY_CLEAR,
        types.STUDENT_SIGNIN_INIT
      ]),
      fetchData() {
        const courseId = this.$route.params.courseId;

        this[types.CURRENT_ACTIVITY]({
          courseId: courseId
        })
        .then((res) => {
          this.$getSocket(res.courseId, res.lessonId);
          this[types.STUDY_INIT]({
            courseId: res.courseId,
            lessonId: res.lessonId
          }).then(() => {
            this[types.STUDENT_SIGNIN_INIT]({ lessonId: this.lessonId, timeId: this.signInTime })
          });

          if (!res.taskId || res.taskId === '0') {
            return;
          }

          this.oldTask = !!(this.oldActivityName[res.activityType]);
          this.initActivityData(res);

          this.$router.replace({
            name: `learning-${res.activityType}`,
            params: {courseId, lessonId: res.lessonId, taskId: res.taskId, activityId: res.activityId}
          });
        }).catch((res) => {
          this.$endLoading();
          this.$ajaxMessage(res.data.message);
        });
      },
      initActivityData(res) {
        this[types.ACTIVITY_CLEAR]();
        this[types.ACTIVITY_INIT]({
          courseId: res.courseId,
          lessonId: res.lessonId,
          taskId: res.taskId,
          activityId: res.activityId,
        }).catch((res) => {
          this.$endLoading();
          this.$ajaxMessage(res.data.message);
        });
      },
      refresh() {
        this.$router.go(this.$router.path);
      },
    }
  }
</script>

<style lang="less">
@import '~@/assets/less/mixins.less';

  .activity-header {
    border-radius: 0.625rem;
    background: #fff;
    padding: 1.25rem;
    margin: 0.9375rem 0.625rem;
    .activity-header__lesson-title {
      font-size: 0.9375rem;
      margin-bottom: 0.9375rem;
      color: #313131;
    }
    .activity-header__title {
      font-size: 0.875rem;
      color: #4993e9;
    }
  }

  .activity-type {
    position: relative;
    height: 5rem;
    margin: 0.9375rem 0.625rem;
    padding-left: 3.1875rem;
    padding-right: 1.5rem;
    line-height: 5rem;
    border-radius: 0.625rem;
    background-color: #fff;
    .type-icon {
      position: absolute;
      top: 1.25rem;
      left: 1.25rem;
      width: 2.375rem;
      height: 2.375rem;
    }
    .type-name {
      padding-left: 1rem;
      color: @brand-primary;
      font-size: 1.5rem;
    }
  }

  .activity-body {
    position: relative;
    border-radius: 0.625rem;
    background: #fff;
    margin: 0.9375rem 0.625rem;
    padding: 2.5rem;
    &.activity-body--sm {
      padding: 0.3125rem;
      // height: calc(~"100% - 10rem");
    }
  }

  .activity-content {
    border-radius: 0.625rem;
    background: #fff;
    padding: 1.25rem;
    margin: 0.9375rem 0.625rem;
  }

  //放按钮部分
  .activity-action {
    margin: 0.9375rem 0.625rem;
  }

  .not-teaching-tips {
    font-size: 1.0625rem;
    text-align: center;
    background-color: #fff;
    height: calc(~"100% - 7rem");
    img {
      width: 100%;
      padding-bottom: 1.5625rem;
    }
    .tips-name {
      font-size: 24px;
      color: rgba(49, 49, 49, 0.87);
      font-weight: 500;
    }
  }

  .teacher-sign-reminder {
    background: #FFFAEB;
    padding: .625rem;
    width: calc(~"100% - 1.25rem");
    font-size: .875rem;
    color: #414141;
    vertical-align: middle;
    line-height: 1.875rem;
    &__icon {
      height: 1.5625rem;
      margin-right: .5rem;
      vertical-align: middle;
    }
    &__btn {
      float: right;
      height: 1.875rem;
      vertical-align: middle;
    }
  }
  .sign-btn {
    display: inline-block;
    padding: 0 13px;
    background-image: linear-gradient(90deg, #FFE190 0%, #FFCE4E 100%);
    border-radius: 100px;
    font-size: 14px;
    color: #414141;
    border: 0;
  }


  .refresh-btn {
    position: fixed;
    bottom: 20.625rem;
    right: 0;
    background: rgba(0,0,0,.3);
    padding: .625rem .6875rem;
    color: #fff;
    z-index: 1000;
    text-align: center;
    border-top-left-radius: 5px;
    border-bottom-left-radius: 5px;
    i {
      display: inline-block;
      padding-bottom: .25rem;
    }
  }

</style>
