<template>
  <div class="course-signin__header">
    <div class="course-signin__title">{{ lessonTitle }}</div>
    <div class="course-signin__code" v-if="signIn.status === 'start'">签到码：<span>{{ signIn.code }}</span></div>
    <button class="weui-btn weui-btn_warn course-signin__btn" @click="endSignIn" v-if="signIn.status === 'start'">
      <i class="cz-icon cz-icon-iccheckcircleblack24px"></i>结束签到<small>还剩{{ countdownNumber }}</small>
    </button>
    <div class="course-signin__info">本课次还可以进行<span>{{ signIn.surplusTime }}</span>次签到</div>
  </div>
</template>

<script>
import api from '@/assets/js/api';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      countdownNumber: '45‘00“',
      interval: null,
      MAX_DIFF: 45,
    }
  },
  computed: {
    ...mapState({
      lessonTitle: state => state.signIn.lessonTitle,
      signIn: state => state.signIn.signIn,
    })
  },
  methods: {
    ...mapActions([types.SIGNIN_END]),
    timeToString(time) {
      let seconds = time % 60;
      let minute = Math.floor(time / 60);

      return `${minute}’${seconds}”`;
    },
    // 倒计时
    countdown() {
      const newDate = this.signIn.currentTime;
      const maxDiff = this.MAX_DIFF * 60;
      let diff = parseInt(newDate) - parseInt(this.signIn.createdTime);

      console.log(newDate, this.signIn.createdTime, diff);

      this.interval = setInterval(() => {
        diff = diff + 1;
        this.countdownNumber = this.timeToString(maxDiff - diff);

        if (diff >= maxDiff) {
          // 自动结束签到
          this.endSignIn();
        }
      }, 1000);
    },
    endSignIn() {
      this[types.SIGNIN_END](this.signIn.id).then(() => {
        clearInterval(this.interval);
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/variables.less';

.course-signin__header {
  padding: 1.5625rem 0.9375rem 0.9375rem 0.9375rem;
  text-align: center;
  border-bottom: 0.0625rem solid #e5e5e5;
  .course-signin__title {
    font-size: 1.0625rem;
    margin-bottom: 0.9375rem;
  }
  .course-signin__info {
    margin-bottom: 0.9375rem;
    span {
      margin: 0 0.1875rem;
      color: #fd4852;
    }
  }
  .course-signin__code {
    font-size: 1rem;
    span {
      color: @brand-primary;
    }
  }
  .course-signin__btn {
    font-size: 1.1875rem;
    margin: 0.9375rem 0;
    i {
      font-size: 1.1875rem;
      margin-right: 0.3125rem;
    }
    small {
      font-size: 0.75rem;
      margin-left: 0.3125rem;
    }
  }
}
</style>
