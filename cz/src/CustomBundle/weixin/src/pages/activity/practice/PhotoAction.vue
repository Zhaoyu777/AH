<template>
  <div class="photo-action">
    <button class="weui-btn weui-btn_disabled"
      v-if="activityData.status === 'created'">
      暂未上课
    </button>
    <button class="weui-btn weui-btn_primary" @click="start"
            v-if="activityData.status !== 'created' && practice.taskStatus != 'start'">
      开始回答
    </button>
    <button class="weui-btn weui-btn_warn" @click="end"
            v-else-if="practice.taskStatus == 'start'">
      停止回答
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
      practice: state => state.activity.practice,
    })
  },
  methods: {
    ...mapActions([types.TASK_START, types.TASK_END]),
    start() {
      this[types.TASK_START]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
      }).then(() => {
        this.$vux.toast.show({
          text: '开始回答成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    end() {
      const _this = this;
      this.$vux.confirm.show({
        title: '停止回答',
        content: '确定要停止回答吗？',
        onConfirm () {
          _this.activityEnd();
        }
      })
    },
    activityEnd() {
      this[types.TASK_END]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
      }).then(() => {
        this.$vux.toast.show({
          text: '停止回答成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
.photo-action {
  margin: 0.9375rem;
}
</style>
