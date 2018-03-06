<template>
  <div>
    <button class="weui-btn weui-btn_primary" @click="start" v-if="!raceAnswer.status">
      开始抢答
    </button>
    <button class="weui-btn weui-btn_warn" @click="end" v-else-if="raceAnswer.status == 'start'">
      停止抢答
    </button>
    <button class="weui-btn weui-btn_default" v-else-if="raceAnswer.status == 'end'">
      已停止抢答
    </button>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  props: ['courseId','lessonId','taskId'],
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      raceAnswer: state => state.activity.raceAnswer,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    ...mapActions([
      types.RACE_ANSWER_START,
      types.RACE_ANSWER_END
    ]),
    start() {
      this[types.RACE_ANSWER_START]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
      }).then(() => {
        this.$vux.toast.show({
          text: '开始抢答成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    end() {
      const _this = this;
      this.$vux.confirm.show({
        title: '停止抢答',
        content: '确定要停止抢答吗？',
        onConfirm () {
          _this.activityEnd();
        }
      })
    },
    activityEnd() {
      this[types.RACE_ANSWER_END]({
        taskId: this.taskId,
        activityId: this.activityData.activityId
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>