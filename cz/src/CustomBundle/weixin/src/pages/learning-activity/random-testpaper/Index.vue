<template>
  <div>
    <result v-if="analysis">
    </result>
    <div v-if="questions.length != 0">
      <div v-for="(question, type) in questions">
        <div v-if="type == 'single_choice'">
          <div v-for="item in question">
            <single-choice
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </single-choice>
          </div>
        </div>

        <div v-if="type == 'choice'">
          <div v-for="item in question">
            <choice
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </choice>
          </div>
        </div>

        <div v-if="type == 'uncertain_choice'">
          <div v-for="item in question">
            <uncertain-choice
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </uncertain-choice>
          </div>
        </div>

        <div v-if="type == 'determine'">
          <div v-for="item in question">
            <determine
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </determine>
          </div>
        </div>

        <div v-if="type == 'fill'">
          <div v-for="item in question">
            <fill
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </fill>
          </div>
        </div>

      </div>
      <div>
        <question-count :answerResult="answerResult" v-if="!analysis"></question-count>
        <div class="activity-action">
          <button class="weui-btn weui-btn_primary" @click="submit" v-if="!analysis && role == 'student'">提交结果</button>
          <button class="weui-btn weui-btn_primary" @click="redo" v-else-if="analysis && role == 'student'">再考一次</button>
        </div>
      </div>

    </div>
    <div class="no-data" v-else>
      试卷无题目
    </div>
  </div>
</template>

<script>
import { XDialog } from 'vux';

import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import SingleChoice from '../testpaper/items/SingleChoice';
import Choice from '../testpaper/items/Choice';
import UncertainChoice from '../testpaper/items/UncertainChoice';
import Determine from '../testpaper/items/Determine';
import Fill from '../testpaper/items/Fill';
import QuestionCount from '../testpaper/QuestionCount';
import Result from './Result';

export default {
  components: {
    SingleChoice,
    Choice,
    UncertainChoice,
    Determine,
    Fill,
    Result,
    QuestionCount
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      questions: state => state.activity.testpaper.questions,
      questionIds: state => state.activity.randomTestpaper.questionIds,
      analysis: state => state.activity.testpaper.analysis,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      host: this.$getCookie('host'),
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      activityId: this.$route.params.activityId,
      taskId: this.$route.params.taskId,
      answerResult: {},
    }
  },
  created() {
    this.getResult();
  },
  beforeRouteLeave (to, from, next) {
    this[types.RANDOM_TESTPAPER_CLEAR]();
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.RANDOM_TESTPAPER_CLEAR]();
    this.updateData(to.params);
    this.getResult();
    next();
  },
  beforeDestroy() {
    this[types.RANDOM_TESTPAPER_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.RANDOM_TESTPAPER_RESULT,
      types.RANDOM_TESTPAPER_SUBMIT,
      types.RANDOM_TESTPAPER_REDO,
      types.RANDOM_TESTPAPER_CLEAR,
    ]),
    getResult() {
      this.$isLoading();
      this[types.RANDOM_TESTPAPER_RESULT]({taskId:this.taskId}).then((res) => {
        this.$endLoading();
      }).catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.data.message);
      });
    },
    updateData(data) {
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.activityId = data.activityId;
      this.taskId = data.taskId;
      this.answerResult = {};
    },
    updateData(params) {
      this.taskId = params.taskId;
    },
    valuechangedListener(data) {
      this.$set(this.answerResult, data.id, data.value);
    },
    submit() {
      this[types.RANDOM_TESTPAPER_SUBMIT]({
        taskId: this.taskId,
        questionIds: this.questionIds,
        data: this.answerResult
      }).then((res) => {
        this.setAnswer();
        this.$vux.toast.show({
          text: '交卷成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });

    },
    redo() {
      this[types.RANDOM_TESTPAPER_REDO]({
        taskId: this.taskId,
      }).then((res) => {
        this.clearAnswer();
        this.$vux.toast.show({
          text: '重新考试',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    setAnswer() {
      this.$bus.emit('setAnswer')
    },
    clearAnswer() {
      this.$bus.emit('clearAnswer')
      this.answerResult = {};
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/activity-body.less';
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
  padding: .9375rem;
  color: #414141;
  font-size: .875rem;
  margin-bottom: .625rem;
  border-radius: .625rem;
  background-color: #f9f9f9;
}
.activity-content .question-naire__selected {
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
.testpaper-right__answer {
  background-color: #FD4852;
  color: #fff;
}
.testpaper-analysis__container {
  margin-top: .625rem;
  margin-bottom: .625rem;
  padding: .46875rem .875rem .59375rem .90625rem;
  border-radius: .3125rem;
  background: #f9f9f9;
  .testpaper-analysis__answer {
    font-family: PingFangSC-Regular;
    font-size: .875rem;
    color: #616161;
  }
  .testpaper-analysis__analysis {
    margin-top: .9375rem;
    font-family: PingFangSC-Regular;
    font-size: .875rem;
    color: #919191;
  }
}
.mrl {
  margin-right: .625rem
}
.mtl {
  margin-top: .625rem
}
</style>
