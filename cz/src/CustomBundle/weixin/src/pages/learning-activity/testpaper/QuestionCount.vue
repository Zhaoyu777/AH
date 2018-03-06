<template>
  <div class="activity-content">
    <span v-for="(question, type) in questions">
      <span v-for="(item, index) in question">
        <span class="question-naire__btns" :class="{'active': (answerResult[item.id] !== undefined && answerResult[item.id] != '')}" v-if="item.type != 'material'">
          {{ item.seq }}
        </span>
        <span v-else v-for="sub in item.subs" class="question-naire__btns" :class="{'active': answerResult[sub.id] !== undefined}">
          {{ sub.seq }}
        </span>
      </span>
    </span>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';

export default {
  props: ['answerResult'],
  computed: {
    ...mapState({
      questions: state => state.activity.testpaper.questions
    })
  }
}
</script>