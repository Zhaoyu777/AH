<template>
  <div class="activity-content" v-if="question.type == 'single_choice'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index }}. </span>
      <span class="question-naire__stem" v-html="$ignoreTag(question.stem)"></span>
      <span class="question-naire__type">(单选)</span>
    </div>
    <checker
      v-model="value"
      default-item-class="question-naire__option"
      selected-item-class="question-naire__selected"
      :key="question.id"
      @on-change="valuechangeListener">
      <div v-for="(item, index) in question.metas.choices" style="color:red;">
        <checker-item
          :key="index"
          :value="index"
          :disabled="analysis"
          v-if="isRight(index)"
          class="testpaper-right__answer">
          <span>{{ $numTransStr(index) }}</span>
          <span v-html="$ignoreTag(item)"></span>
        </checker-item>
        <checker-item
          :key="index"
          :value="index"
          :disabled="analysis"
          v-else>
          <span class="question-naire__num">{{ $numTransStr(index) }}</span>
          <span v-html="$ignoreTag(item)"></span>
        </checker-item>
        <div class="question-naire__data" v-if="analysis && role == 'teacher'">
          <span class="text-warning">{{ statis[question.id][index].percent }}%</span>
          <span class="text-primary">{{ statis[question.id][index].num }}</span>
          <span class="question-naire__num">{{ statis[question.id].realNum }}</span>
        </div>
      </div>
    </checker>

    <div v-if="analysis && role == 'student'">
      <div class="testpaper-analysis__container" v-if="!this.question.testResult || (this.question.testResult && this.question.testResult.status != 'right')">
        <div class="testpaper-analysis__answer">
          正确答案：
          <span class="text-primary mrl">
            <span v-for="rightAnswer in this.question.answer">
              {{ $numTransStr(parseInt(rightAnswer)) }}
            </span>
          </span>
          你的答案：
          <span class="text-warning">
            <span v-if="!this.question.testResult || (this.question.testResult.answer.length == 1 && this.question.testResult.answer[0] == '')">
              未回答
            </span>
            <span v-for="yourAnswer in this.question.testResult.answer" v-else>
              {{ $numTransStr(parseInt(yourAnswer)) }}
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
      this.value = "";
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
    isRight(index) {
      if (!this.analysis) {
        return false;
      }

      if (this.question.testResult) {
        for (var i = this.question.testResult.answer.length - 1; i >= 0; i--) {
          if (index == parseInt(this.question.testResult.answer[i])) {
            return true;
          }
        }
      }

      return false;
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
  .question-naire__title {
    word-break: break-all;
  }
  .question-naire__option {
    word-break: break-all;
  }
  .question-naire__stem {
    line-height: 1.5;
  }
</style>