<template>
  <div class="activity-content">
    <span v-for="(question, index) in questionNaire.questions">
      <span class="question-naire__btns" :class="{'active': answerResult[question.id] !== undefined}">
        {{ index + 1 }}
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
      questionNaire: state => state.activity.questionnaire
    })
  }
}
</script>