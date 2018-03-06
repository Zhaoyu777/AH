<template>
  <div class="activity-content" v-if="question.type == 'essay'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index + 1 }}. </span>
      <span v-html="$ignoreTag(question.stem)"></span>
      <span class="question-naire__type">(问答题)</span>
    </div>
    <div v-if="questionNaire.resultStatus == 'start'">
      <group>
        <x-input
          placeholder="请在这输入答案"
          novalidate
          placeholder-align="left"
          v-model="value"
          :show-clear="false"
          @on-change="changeListener"
          >
          </x-input>
      </group>
    </div>
    <div v-else-if="questionNaire.resultStatus == 'finished'" v-for="answer in question.answers">
      <div class="question-naire__option word-break">
        {{ answer.content }}
        <div class="option-essay">-- {{ answer.user }}</div>
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
      value: ''
    }
  },
  computed: {
    ...mapState({
      questionNaire: state => state.activity.questionnaire
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
</style>