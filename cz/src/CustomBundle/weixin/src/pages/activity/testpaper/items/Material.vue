<template>
  <div>
    <div class="activity-content" v-if="question.type == 'material'">
      <div class="question-naire__title">
        <span class="question-naire__stem" v-html="$ignoreTag(question.stem)"></span>
        <span class="question-naire__type">(材料题)</span>
      </div>
    </div>
    <div>
      <div v-if="question.subs">
        <div v-for="(question, type) in question.subs">
          <div v-if="question.type == 'single_choice'">
            <single-choice
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </single-choice>
          </div>

          <div v-if="question.type == 'choice'">
            <choice
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </choice>
          </div>

          <div v-if="question.type == 'essay'">
            <essay
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </essay>
          </div>

          <div v-if="question.type == 'uncertain_choice'">
            <uncertain-choice
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </uncertain-choice>
          </div>

          <div v-if="question.type == 'determine'">
            <determine
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </determine>
          </div>

          <div v-if="question.type == 'fill'">
            <fill
              :question="question"
              :index="question.seq"
              :id="question.id"
              @valuechanged="valuechangedListener">
            </fill>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapState, mapActions } from 'vuex';
  import * as types from '@/vuex/mutation-types';
  import SingleChoice from './SingleChoice';
  import Choice from './Choice';
  import Essay from './Essay';
  import UncertainChoice from './UncertainChoice';
  import Determine from './Determine';
  import Fill from './Fill';

  export default {
  components: {
    SingleChoice,
    Choice,
    Essay,
    UncertainChoice,
    Determine,
    Fill
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      role: state => state.activity.activityData.role
    })
  },
  props: ['question', 'index', 'id', 'answerResult'],
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      activityId: this.$route.params.activityId,
      taskId: this.$route.params.taskId
    }
  },
  created() {

  },
  methods: {
    ...mapActions([
      types.TESTPAPER_RESULT,
      types.TESTPAPER_SUBMIT
    ]),
    valuechangedListener(data) {
      this.$set(this.answerResult, data.id, data.value);
    },
  }
}

</script>

<style lang="less">
  .question-naire__stem {
    line-height: 1.5;
  }
</style>