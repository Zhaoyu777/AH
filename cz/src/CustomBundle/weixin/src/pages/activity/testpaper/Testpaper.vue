<template>
  <div>
    <div v-if="questions">
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

        <div v-if="type == 'essay'">
          <div v-for="item in question">
            <essay
              :question="item"
              :index="item.seq"
              :id="item.id"
              @valuechanged="valuechangedListener">
            </essay>
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

        <div v-if="type == 'material'">
          <div v-for="item in question">
            <material
              :question="item"
              :index="item.seq"
              :id="item.id"
              :answerResult="answerResult"
              @valuechanged="valuechangedListener">
            </material>
          </div>
        </div>

      </div>
      <div v-if="!testpaper.analysis">
        <question-count :answerResult="answerResult"></question-count>
        <div class="activity-action">
          <button class="weui-btn weui-btn_primary" @click="submit" v-if="testpaper.status == 'start'">提交结果</button>
          <button class="weui-btn weui-btn_disabled"v-else-if="testpaper.status != 'start'">测验结束</button>
        </div>
      </div>
    </div>
    <div class="no-data" v-else>
      试卷无题目
    </div>
  </div>
</template>

<script>
  import { mapState, mapActions } from 'vuex';
  import * as types from '@/vuex/mutation-types';
  import SingleChoice from './items/SingleChoice';
  import Choice from './items/Choice';
  import Essay from './items/Essay';
  import QuestionCount from './QuestionCount';
  import UncertainChoice from './items/UncertainChoice';
  import Determine from './items/Determine';
  import Fill from './items/Fill';
  import Material from './items/Material';

  export default {
  components: {
    SingleChoice,
    Choice,
    Essay,
    UncertainChoice,
    Determine,
    Fill,
    Material,
    QuestionCount
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      questions: state => state.activity.testpaper.questions,
      testpaper: state => state.activity.testpaper,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      activityId: this.$route.params.activityId,
      taskId: this.$route.params.taskId,
      answerResult: {},
    }
  },
  created() {

  },
  beforeRouteLeave (to, from, next) {
    clearInterval(this.interval);
    next();
  },
  beforeRouteUpdate (to, from, next) {
    clearInterval(this.interval);
    next();
  },
  methods: {
    ...mapActions([
      types.TESTPAPER_RESULT,
      types.TESTPAPER_SUBMIT
    ]),
    submit() {
      this[types.TESTPAPER_SUBMIT]({
        resultId: this.testpaper.result.id,
        data: this.answerResult
      }).then((res) => {
        this.$vux.toast.show({
          text: '交卷成功',
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
