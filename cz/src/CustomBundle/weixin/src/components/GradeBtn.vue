<template>
  <div class="activity-student__assist" v-if="role == 'teacher'">
    <div class="code-tag code-tag--danger code-tag--md activity-student__review" v-if="item.score == 0" @click="showReviewDialog(item.resultId)">评分</div>
    <div class="activity-student__scores" v-else> +{{ item.score }}分</div>
  </div>
</template>

<script>
import * as types from '@/vuex/mutation-types';
import { mapState, mapMutations, mapActions } from 'vuex';

export default {
  props: [
    'item'
  ],
  computed: {
    ...mapState({
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {

    }
  },
  methods: {
    showReviewDialog(resultId) {
      this.$emit('showReviewDialog', resultId)
    }
  }
}
</script>

<style>
@import '~@/assets/less/module/student-list.less'
</style>