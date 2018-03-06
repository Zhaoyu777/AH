<template>
  <div>
    <div class="activity-action">
      <button class="weui-btn weui-btn_disabled" v-if="activityData.status == 'created' && activityData.stage == 'in'">
        暂未上课
      </button>
      <button class="weui-btn weui-btn_disabled" v-else-if="questionNaire.questions == null">
        问卷已删除
      </button>

      <div v-else-if="role == 'student'">
        <question-naire v-if="showQuestion || questionNaire.questionResults"></question-naire>
        <button class="weui-btn weui-btn_disabled" v-else-if="!questionNaire.status && activityData.stage == 'in'">
          调查未开始
        </button>
        <button class="weui-btn weui-btn_primary" @click="show" v-else-if="questionNaire.status == 'start' || activityData.stage != 'in'">
          开始调查
        </button>

        <button class="weui-btn weui-btn_disabled" v-else-if="questionNaire.status != 'start'">调查结束</button>
      </div>
      <div v-else-if="role == 'teacher'">
        <button class="weui-btn weui-btn_primary" @click="startTask" v-if="!questionNaire.status && activityData.stage == 'in'">
          开始调查
        </button>
        <button class="weui-btn weui-btn_primary" @click="endTask" v-else-if="questionNaire.status == 'start' && activityData.stage == 'in'">
          结束调查
        </button>

        <button class="weui-btn weui-btn_disabled" v-else-if="questionNaire.status != 'start' && activityData.stage == 'in'">
          调查已结束
        </button>
        <question-naire v-if="showQuestion"></question-naire>
        <button class="weui-btn weui-btn_primary" @click="show" v-else="showQuestion">
          查看结果
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import QuestionNaire from './QuestionNaire';


export default {
  components: {
    QuestionNaire
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      questionNaire: state => state.activity.questionnaire,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      activityId: this.$route.params.activityId,
      taskId: this.$route.params.taskId,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      showQuestion: false,
      answerResult: {},
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    clearInterval(this.interval);
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.QUESTION_NAIRE_CLEAR]();
    clearInterval(this.interval);
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    clearInterval(this.interval);
    this[types.QUESTION_NAIRE_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.QUESTION_NAIRE_RESULT,
      types.TASK_START,
      types.QUESTION_NAIRE_CLEAR,
      types.TASK_END
    ]),
    fetchData() {
      this[types.QUESTION_NAIRE_RESULT]({
        taskId: this.taskId,
        activityId: this.activityId
      }).then((res) => {
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    startTask() {
      this[types.TASK_START]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '开始调查',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    endTask() {
      const _this = this;
      this.$vux.confirm.show({
        title: '停止调查',
        content: '确定要结束调查吗？',
        onConfirm () {
          _this.activityEnd();
        }
      })
    },
    updateData(data) {
      this.activityId = data.activityId;
      this.taskId = data.taskId;
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.showQuestion = false;
      this.answerResult = {};
    },
    activityEnd() {
      this[types.TASK_END]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '结束调查',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    show() {
      this.showQuestion = true;
    }
  }
}
</script>

<style lang="less" scroped>
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';

.question-naire__title {
  font-size: 1.1875rem;
  color: #333;
}
.question-naire__num {
  color: @brand-primary;
}
.question-naire__type {
  font-size: .75rem;
  color: #c1c1c1;
}
.question-naire__option {
  display: block;
  padding: .625rem .9375rem;
  line-height: 1.25rem;
  color: #414141;
  font-size: .875rem;
  margin-bottom: .625rem;
  border-radius: .625rem;
  background-color: #f9f9f9;
}
.question-naire__selected {
  background-color: @brand-primary;
  color: #fff;
  .question-naire__num {
    color: #fff;
  }
}
.question-naire__data {
  padding: .3125rem .625rem .9375rem .625rem;
  .question-naire__num {
    padding-left: .625rem;
    color: #919191;
  }
}
.question-naire__btns {
  display: inline-block;
  width: 1.875rem;
  height: 1.875rem;
  line-height: 1.875rem;
  text-align: center;
  color: @brand-primary;
  background-color: #f9f9f9;
  border-radius: .625rem;
  margin: .1875rem;
  &.active {
    background-color: @brand-primary;
    color: #fff;
  }
}

.activity-info {
  padding: .1875rem 1.875rem;
  color: #919191;
}

.vux-checker-box {
  margin-top: .875rem;
}
.vux-checker-item {
  display: block !important;
}
.weui-cells:after {
  border-bottom: 0px !important;
}
.weui-cells:before {
  border-top: 0px !important;
}
.weui-cell {
  font-size: .875rem !important;
  background-color: #f9f9f9 !important;
  border-radius: .625rem !important;
}
</style>
