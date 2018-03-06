<template>
  <div class="activity-content" v-if="question.type == 'essay'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index }}. </span>
      <span class="question-naire__stem" v-html="$ignoreTag(question.stem)"></span>
      <span class="question-naire__type">(问答题)</span>
    </div>
    <div>
      <group>
        <x-input
          placeholder="请在这输入答案"
          novalidate
          placeholder-align="left"
          v-model="value"
          :show-clear="false"
          :disabled="analysis"
          @on-change="changeListener"
          >
          </x-input>
      </group>
    </div>

    <div v-if="analysis && role == 'student'">
      <div class="testpaper-analysis__container" v-if="!this.question.testResult || (this.question.testResult)">
        <div class="testpaper-analysis__answer">
          <div>
            <div>
              参考答案：
            </div>
            <span class="answer-content text-primary word-break">
              {{ $ignoreTag(question.answer[0]) }}
            </span>
          </div>

          <div>
            <div>
              你的答案：
            </div>
            <span class="answer-content text-warning word-break">
              <span v-if="!this.question.testResult">
                未回答
              </span>
              <span v-else>
                <span class="text-warning mrl">
                  {{this.question.testResult.answer[0]}}
                </span>
              </span>
            </span>
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
      value: ""
    }
  },
  computed: {
    ...mapState({
      analysis: state => state.activity.testpaper.analysis,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    changeListener() {
      this.$emit('valuechanged', {
        id: this.id,
        value: this.value ? [this.value] : undefined,
      });
    },
  },
}
</script>

<style lang="less">
  @import '~@/assets/less/mixins.less';

  .question-naire__option {
    margin-top: 15px;
    .option-essay {
      float: right;
      color: #919191;
    }
  }
  .answer-content {
    display: inline-block;
    padding: .3125rem 0 .3125rem 0;
  }
  .question-naire__stem {
    line-height: 1.5;
  }
</style>
