<template>
  <div class="lesson-action">
    <button class="weui-btn weui-btn_primary" @click="signIn" v-if="signInStatus !== 'attend' && studentSignInStatus === 'start'">立即签到</button>
    <button class="weui-btn weui-btn_default" v-else-if="signInStatus !== 'attend' && studentSignInStatus === 'doing'">签到中</button>
    <router-link class="weui-btn weui-btn_default" :to="{ name: 'signInRecord', params: { courseId, lessonId } }">我的签到记录</router-link>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import * as utils from '@/assets/js/utils';

export default {
  props: ['code', 'latitude', 'longitude'],
  computed: {
    ...mapState({
      signInStatus: state => state.signIn.signInStatus,
      currentActivity: state => state.activity.currentActivity,
    })
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      timeId: this.$route.params.timeId,
      studentSignInStatus: 'start',
      checkSignInTimeout: null,
    }
  },
  beforeRouteLeave (to, from, next) {
    clearTimeout(this.checkSignInTimeout);
    next();
  },
  beforeRouteUpdate (to, from, next) {
    clearTimeout(this.checkSignInTimeout);
    next();
  },
  beforeDestroy() {
    clearTimeout(this.checkSignInTimeout);
  },
  methods: {
    ...mapActions([
      types.STUDENT_SIGNIN,
      types.STUDENT_SIGNIN_STATUS
    ]),
    signIn() {
      let code = this.code.join("");

      this[types.STUDENT_SIGNIN]({
        lessonId: this.lessonId,
        timeId: this.timeId,
        code,
        latitude: this.latitude,
        longitude: this.longitude})
      .then((res) => {
        if (res.data == true) {
          this.studentSignInStatus = 'doing';
          this.successGo();       
        } else {
          this.$vux.toast.show({
            text: res.data,
            type: 'warn'
          })
        }
      })
      .catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    successGo(res) {
      this.checkSignInTimeout = setTimeout(() => {
        this.studentSignInStatus = 'start';
        if (this.signInStatus == 'attend') {
          const res = {
            courseId: this.courseId,
            lessonId: this.lessonId,
            taskId: this.currentActivity.taskId,
            activityId: this.currentActivity.activityId,
            activityType: this.currentActivity.activityType
          }
          utils.goToTask.call(this, res);
          return;
        }

        this[types.STUDENT_SIGNIN_STATUS]({
          lessonId: this.lessonId,
          timeId: this.timeId,
        }).then(() => {
          this.studentSignInStatus = 'start';
          if (this.signInStatus == 'attend') {
            return ;
          }

          this.$vux.toast.show({
            text: '签到超时',
            type: 'warn'
          });
        })
      }, 3000);
    }
  }
}
</script>
