<template>
  <div>
    <div v-if="questionNaire.resultStatus == 'finished'">
      <div class="activity-info">本班共有 {{ questionNaire.memberNum }} 人，实际完成<span class="text-primary"> {{ questionNaire.actualNum }} </span>人</div>
      <div v-if="questionNaire.questionResults">
        <div v-for="(question, index) in questionNaire.questionResults">
          <single-choice
            :question="question"
            :index="index"
            :id="question.id"
            @valuechanged="valuechangedListener">
          </single-choice>
          <choice
            :question="question"
            :index="index"
            :id="question.id"
            @valuechanged="valuechangedListener">
          </choice>
          <essay
            :question="question"
            :index="index"
            :id="question.id"
            @valuechanged="valuechangedListener">
          </essay>
        </div>
      </div>
      <div class="no-data" v-else-if="!questionNaire.questionResults">
        <span>暂无提交数据</span>
      </div>
    </div>
    <div v-else-if="questionNaire.resultStatus === 'start'">
      <div v-for="(question, index) in questionNaire.questions">
        <single-choice
          :question="question"
          :index="index"
          :id="question.id"
          @valuechanged="valuechangedListener">
        </single-choice>
        <choice
          :question="question"
          :index="index"
          :id="question.id"
          @valuechanged="valuechangedListener">
        </choice>
        <essay
          :question="question"
          :index="index"
          :id="question.id"
          @valuechanged="valuechangedListener">
        </essay>
      </div>
      <question-count :answerResult="answerResult"></question-count>
    </div>
    <div class="activity-action">
      <button class="weui-btn weui-btn_primary" @click="submit" v-if="activityData.status == 'created' && questionNaire.resultStatus == 'start' && activityData.stage == 'before'">提交结果</button>
      <button class="weui-btn weui-btn_disabled" v-else-if="activityData.status === 'created'">已提交</button>
      <button class="weui-btn weui-btn_primary" @click="submit" v-else-if="(activityData.status == 'teaching' && questionNaire.status == 'start' || activityData.stage != 'in') && questionNaire.resultStatus == 'start'">提交结果</button>
      <button class="weui-btn weui-btn_default" v-else-if="activityData.status == 'teached' || questionNaire.status == 'end' && role != 'teacher'">
        调查结束
      </button>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import SingleChoice from './SingleChoice';
import Choice from './Choice';
import Essay from './Essay';
import QuestionCount from './QuestionCount';


export default {
  components: {
    SingleChoice,
    Choice,
    Essay,
    QuestionCount
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
      answerResult: {},
    }
  },
  created() {
    this.fetchData();
    this.interval = setInterval(() => this.fetchData(), 5000);
  },
  beforeRouteLeave (to, from, next) {
    clearInterval(this.interval);
    next();
  },
  beforeDestroy: function() {
    this[types.QUESTION_NAIRE_CLEAR]();
    clearInterval(this.interval)
  },
  beforeRouteUpdate (to, from, next) {
    clearInterval(this.interval);
    this[types.QUESTION_NAIRE_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  methods: {
    ...mapActions([
      types.QUESTION_NAIRE_RESULT,
      types.QUESTION_NAIRE_CLEAR,
      types.QUESTION_NAIRE_SUBMIT
    ]),
    fetchData() {
      if (this.questionNaire.resultStatus !== 'start') {
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
      }
    },
    updateData(params) {
      this.courseId = params.courseId;
      this.lessonId = params.lessonId;
      this.activityId = params.activityId;
      this.taskId = params.taskId;
    },
    submit() {
      this[types.QUESTION_NAIRE_SUBMIT]({
        resultId: this.questionNaire.resultId,
        content: this.answerResult
      }).then((res) => {
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }
        this.$vux.toast.show({
          text: '恭喜，回答完毕',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    valuechangedListener(data) {
      this.$set(this.answerResult, data.id, data.value);
    },
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
