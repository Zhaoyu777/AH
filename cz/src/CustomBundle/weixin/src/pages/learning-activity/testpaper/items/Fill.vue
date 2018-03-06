<template>
  <div class="activity-content" v-if="question.type == 'fill'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index }}. </span>
        <span v-for="(stem, index) in question.stem">
          <span class="question-naire__stem" v-html="$ignoreTag(stem)"></span>
          <span class='question-stem-fill-blank' v-if="question.stem[index+1] != null">({{ index+1 }})</span>
        </span>
      <span class="question-naire__type">(填空题)</span>
    </div>
    <div v-for="(stem, seq) in question.stem"
      v-if="question.stem[seq+1] != null">
      <group>
        <x-input
          :placeholder="`填空(${seq+1})答案，请填这里`"
          v-model="value[seq]"
          :key="index"
          novalidate
          placeholder-align="left"
          :disabled="analysis"
          :show-clear="false"
          @on-change="changeListener">
          </x-input>
      </group>
    </div>

    <div v-if="analysis && role == 'student'">
      <div class="testpaper-analysis__container">
        <div class="testpaper-analysis__answer"
          v-for="(stem, seq) in question.stem"
          v-if="question.stem[seq+1] != null">
          <div class="fill-answer__container">
            填空({{ seq+1 }}):
            正确答案：
            <span v-for="(rightAnswer, num) in question.answer[seq]">
              <span class="text-primary word-break">
                {{ rightAnswer }}
              </span>
              <span v-if="question.answer[seq][num+1]">或</span>
            </span>

            <div class="text-answer">
              你的答案：
              <span v-if="question.testResult && question.testResult.answer[seq] != null" class="text-warning word-break">
                {{ question.testResult.answer[seq] }}
              </span>
              <span v-else class="text-warning">
                未回答
              </span>
            </div>
          </div>
        </div>

        <div class="testpaper-analysis__analysis" v-if="question.analysis">
          解析：{{ $ignoreTag(question.analysis) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { XInput, Group } from 'vux';
import { mapState } from 'vuex';

export default {
  components: {
    XInput,
    Group
  },
  props: ['question', 'index', 'id'],
  data() {
    return {
      value: []
    }
  },
  computed: {
    ...mapState({
      analysis: state => state.activity.testpaper.analysis,
      role: state => state.activity.activityData.role
    })
  },
  created() {
    this.$bus.on('clearAnswer', () => {
      this.value = [];
    })
  },
  methods: {
    changeListener() {
      this.$emit('valuechanged', {
        id: this.id,
        value: this.value ? this.value : undefined,
      });
    },
  },
}
</script>

<style lang="less">
  .question-naire__option {
    margin-top: 15px;
    .option-essay {
      float: right;
      color: #919191;
    }
  }

  .question-stem-fill-blank {
    padding-left: 15px;
    padding-right: 15px;
    border-bottom: 1px solid #999;
    color: #aaa;
  }

  .fill-answer__container {
    padding-top: 1.25rem;
  }

  .text-answer{
    margin-top: .9375rem;
  }
  .question-naire__stem {
    line-height: 1.5;
  }
</style>