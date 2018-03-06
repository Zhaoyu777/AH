<template>
  <div class="activity-student-list">
    <div class="weui-flex activity-student-item" v-for="(item, index) in raceAnswer.results">
      <span class="crown__icon" :class="{
          'crown__icon-1': index == 0,
          'crown__icon-2': index == 1,
          'crown__icon-3': index == 2 }">
        <i class="cz-icon cz-icon-crown"></i>
        <span class="crown__rank">{{ index + 1 }}</span>
      </span>
      <img :src="item.avatar ? host + item.avatar : host + avatarCover" alt="" class="activity-student__avatar">
      <div class="weui-flex__item activity-student__name">{{ item.truename }}</div>
      <div v-if="role == 'student'" class="weui-flex__item activity-student__time">{{ item.createdTime }}</div>
      <grade-btn :item="item" @showReviewDialog="showReviewDialog(item.resultId)"></grade-btn>
    </div>
    <review-dialog :isDialogShow="raceAnswer.isDialogShow" @submitReview="handleSubmitReview" @hideDialog="handleHideDialog"></review-dialog>
  </div>
</template>

<script>
import { avatarCover } from '@/assets/js/data';
import ReviewDialog  from '@/components/ReviewDialog';
import GradeBtn  from '@/components/GradeBtn';
import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      host: this.$getCookie('host'),
      avatarCover,
      courseId: this.$route.params.courseId
    }
  },
  components: {
    ReviewDialog,
    GradeBtn
  },
  computed: {
    ...mapState({
      raceAnswer: state => state.activity.raceAnswer,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    ...mapMutations([
      types.SET_RACE_ANSWER_REVIEW_DIALOG
    ]),
    ...mapActions([
      types.SET_RACE_ANSWER_REMARK_CURRENT_ID,
      types.RACE_ANSWER_REMARK
    ]),
    handleSubmitReview({ score, remark }) {
      this[types.RACE_ANSWER_REMARK]({
        courseId: this.courseId,
        resultId: this.raceAnswer.currentReviewId,
        score,
        remark
      }).then((res) => {
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }
        this.handleHideDialog();
        this.$vux.toast.show({
          text: '评分成功'
        })
      }).catch((response) => {
        this.handleHideDialog();
        // this.$vux.toast.show({
        //   text: '评分失败',
        //   type: 'warn'
        // })
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleHideDialog() {
      this[types.SET_RACE_ANSWER_REVIEW_DIALOG](false);
    },
    showReviewDialog(resultId) {
      this[types.SET_RACE_ANSWER_REMARK_CURRENT_ID](resultId);
      this[types.SET_RACE_ANSWER_REVIEW_DIALOG](true);
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/student-list.less';
@import '~@/assets/less/module/crown-icon.less';
</style>