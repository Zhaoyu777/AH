<template>
  <div class="weui-flex lesson-action">
    <div class="weui-flex__item">
      <div v-if="preview == 1">
        <div class="weui-btn weui-btn_primary" v-if="signInStatus == 'start'">
          第{{ signInTime }}次签到中
        </div>
      </div>
      <div v-else>
        <router-link class="weui-btn weui-btn_primary" :to="{ name: 'signInTime', params: { courseId, lessonId, timeId: signInTime  } }" v-if="signInStatus == 'start'">
        第{{ signInTime }}次签到中
        </router-link>
        <button class="weui-btn weui-btn_default" v-else-if="signInStatus == 'end' && signInTime < 2">
          第{{ parseInt(signInTime) + 1 }}次签到暂未开始
        </button>
        <div class="weui-btn weui-btn_default" v-else-if="signInStatus == 'end' && signInTime == 2">
          签到结束
        </div>
        <div class="weui-btn weui-btn_default" v-else>
          签到暂未开始
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  data() {
    return {
      courseId: this.$route.params.courseId,
      preview: this.$route.query.preview,
    }
  },
  computed: {
    ...mapState({
      lessonId: state => state.study.lessonId,
      signInTime: state => state.study.signInTime,
      signInStatus: state => state.study.signInStatus,
    })
  }
}
</script>
