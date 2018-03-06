<template>
  <div class="weui-flex lesson-action">
  <flexbox>
    <flexbox-item class="sign-flexbox-item" v-if="signInStatus !=='start' && signInTime < 2">
      <button class="weui-btn weui-btn_warning"
        @click="signInStart">
        开始签到
      </button>
    </flexbox-item>
    <flexbox-item class="sign-flexbox-item" v-if="signInTime > 0">
      <div class="weui-btn weui-btn_default" v-if="preview == 1">签到详情</div>
      <router-link class="weui-btn weui-btn_default"
        v-else-if="preview != 1"
        :to="{ name: 'signInDetail', params: { courseId, lessonId }  }">
        签到详情
      </router-link>
    </flexbox-item>
    <flexbox-item>
       <button class="weui-btn weui-btn_primary"
        v-if="lessonStatus == 'created'"
        @click="courseStart">
        开始上课
      </button>
      <button class="weui-btn weui-btn_warn"
        v-else-if="lessonStatus == 'teaching'"
        @click="courseCancel">
        取消上课
      </button>
      <button class="weui-btn weui-btn_warn"
        v-else-if="lessonStatus == 'teached'">
        已完成课程
      </button>
    </flexbox-item>
  </flexbox>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import { Flexbox, XButton, FlexboxItem } from 'vux';

export default {
  components: {
    Flexbox,
    FlexboxItem,
    XButton,
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      preview: this.$route.query.preview,
    }
  },
  computed: {
    ...mapState({
      lessonId: state => state.study.lessonId,
      signInStatus: state => state.study.signInStatus,
      lessonStatus: state => state.study.courseData.lessonStatus,
      signInTime: state => state.study.signInTime,
      next: state => state.study.next,
    }),
  },
  methods: {
    ...mapActions([types.SIGNIN_START, types.COURSE_CANCEL, types.COURSE_START]),
    signInStart() {
      if (this.preview != 1) {
        this[types.SIGNIN_START](this.lessonId).then((res) => {
          if (res.data.message) {
            this.$ajaxMessage(res.data.message)
          }
          this.$vux.toast.show({
            text: '开始签到成功',
          })
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
      }
    },
    courseStart() {
      if (this.preview != 1) {
        this[types.COURSE_START](this.lessonId).then((res) => {
          if (res.data.message) {
            this.$ajaxMessage(res.data.message);
            return;
          }
          this.$vux.toast.show({
            text: '您可以开始上课了',
            time: 1000,
          });
          setTimeout(() => {
            this.$router.push(`/course/${this.courseId}/lesson/${this.lessonId}/task/${this.next.taskId}/activity/${this.next.activityId}/type/${this.next.activityType}`);
          }, 1000)
        }).catch((res) => {
          this.$ajaxError();
        });
      }
    },
    courseCancel() {
      if (this.preview != 1) {
        const _this = this;
        this.$vux.confirm.show({
          title: '取消上课',
          content: '确定要取消上课吗？',
          onConfirm() {
            _this.confirmCancel();
          }
        })
      }
    },
    confirmCancel() {
      this[types.COURSE_CANCEL](this.lessonId).then((res) => {
        if (res.data === true) {
          this.$vux.toast.show({
            text: '您取消了上课',
            time: 3000,
          })
        } else {
          this.$ajaxMessage(res.data);
        }
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
  .vux-flexbox-item {
    margin-left: .5rem;
    &.sign-flexbox-item {
      max-width: 6rem;
    }
  }
</style>
