<template>
  <div class="activity-content" v-if="question.type == 'choice'">
    <div class="question-naire__title">
      <span class="question-naire__num">{{ index + 1 }}. </span>
      <span v-html="$ignoreTag(question.stem)"></span>
      <span class="question-naire__type">(多选)</span>
    </div>
    <checker
      v-model="value"
      type="checkbox"
      default-item-class="question-naire__option"
      selected-item-class="question-naire__selected"
      v-for="(item, index) in question.items"
      :key="item.id"
      @on-change="changeListener"
      >
      <checker-item
        :value="index"
        :key="index"
        :disabled="questionNaire.resultStatus == 'finished'">
        <span class="question-naire__num">{{ $numTransStr(index) }}</span>
        <span v-html="$ignoreTag(item.text)"></span>
      </checker-item>
      <div class="question-naire__data" v-if="questionNaire.resultStatus == 'finished'">
        <span class="text-warning">{{item.part}}%</span>
        <span class="text-primary">{{item.num}}</span>
        <span class="question-naire__num">{{item.num}}</span>
      </div>
    </checker>
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
    };
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      questionNaire: state => state.activity.questionnaire
    })
  },
  methods: {
    changeListener() {
      this.$emit('valuechanged', {
        id: this.id,
        value: this.value.length ? this.value : undefined,
      });
    },
  },
}
</script>