<template>
  <div class="activity-content" v-if="question.type == 'determine'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index }}. </span>
      <span class="question-naire__stem" v-html="$ignoreTag(question.stem)"></span>
      <span class="question-naire__type">(判断题)</span>
    </div>
    <checker v-model="value" default-item-class="question-naire__option" selected-item-class="question-naire__selected" @on-change="valuechangeListener">
      <checker-item :value="1" :disabled="analysis" v-if="analysis && this.question.testResult && this.question.testResult.answer[0] == 1" class="testpaper-right__answer">
        <span>A</span>
        正确
      </checker-item>
      <checker-item :value="1" :disabled="analysis" v-else>
        <span class="question-naire__num">A</span>
        正确
      </checker-item>
      <div class="question-naire__data" v-if="analysis && role == 'teacher'">
        <span class="text-warning">{{ statis[question.id][1].percent }}%</span>
        <span class="text-primary">{{ statis[question.id][1].num }}</span>
        <span class="question-naire__num">{{ statis[question.id].realNum }}</span>
      </div>
      <checker-item :value="0" :disabled="analysis" v-if="analysis && this.question.testResult && this.question.testResult.answer[0] == 0" class="testpaper-right__answer">
        <span>B</span>
        错误
      </checker-item>
      <checker-item :value="0" :disabled="analysis" v-else>
        <span class="question-naire__num">B</span>
        错误
      </checker-item>
      <div class="question-naire__data" v-if="analysis && role == 'teacher'">
        <span class="text-warning">{{ statis[question.id][0].percent }}%</span>
        <span class="text-primary">{{ statis[question.id][0].num }}</span>
        <span class="question-naire__num">{{ statis[question.id].realNum }}</span>
      </div>
    </checker>

    <div v-if="analysis && role == 'student'">
      <div class="testpaper-analysis__container" v-if="!this.question.testResult || (this.question.testResult && this.question.testResult.status != 'right')">
        <div class="testpaper-analysis__answer">
          正确答案：
          <span class="text-primary mrl" v-if="this.question.answer[0] == 1">
            A
          </span>
          <span class="text-primary mrl" v-else>
            B
          </span>
          你的答案：
          <span class="text-warning">
            <span v-if="this.question.testResult.answer == ''">
              未回答
            </span>
            <span v-else>
              <span class="text-primary mrl" v-if="this.question.testResult.answer[0] == 1">
                A
              </span>
              <span class="text-primary mrl" v-else>
                B
              </span>
            </span>
          </span>
        </div>

        <div class="testpaper-analysis__analysis" v-if="question.analysis">
          解析：{{ $ignoreTag(question.analysis) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import { Checker, CheckerItem } from 'vux';

export default {
  components: {
    Checker,
    CheckerItem
  },
  props: ['question', 'index', 'id'],
  data() {
    return {
      value: ''
    }
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      role: state => state.activity.activityData.role,
      statis: state => state.activity.testpaper.statis,
      analysis: state => state.activity.testpaper.analysis
    })
  },
  created() {
    this.$bus.on('clearAnswer', () => {
      this.value = '';
    })
    this.$bus.on('setAnswer', () => {
      this.setAnswer();
    })
    if (this.analysis) {
      this.setAnswer();
    }
  },
  methods: {
    valuechangeListener() {
      this.$emit('valuechanged', {
        id: this.id,
        value: [this.value]
      })
    },
    setAnswer() {
      for (var i = this.question.answer.length - 1; i >= 0; i--) {
        this.value = parseInt(this.question.answer[i]);
      }
      this.valuechangeListener();
    }
  },
}
</script>

<style lang="less">
  .question-naire__stem {
    line-height: 1.5;
  }
</style>