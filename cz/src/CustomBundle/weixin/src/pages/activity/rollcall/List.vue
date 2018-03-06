<template>
  <div class="rollcall-student-list">
    <div class="weui-flex rollcall-student-item" v-for="item in rollcall.stuData">
      <img class="rollcall-student__avatar"
        :src="item.avatar ? host + item.avatar : host + avatarCover">
      <div class="weui-flex__item rollcall-student__title">
        {{ item.truename }}
      </div>
      <div class="rollcall-student__assist">
        <div class="rollcall-student__scores" v-if="item.socre && item.score !== '0'"> +{{ item.score }}分</div>
        <div class="code-tag code-tag--danger code-tag--md rollcall-student__review"
             v-else
             @click="showReviewDialog(item.resultId)">
          评分
        </div>
      </div>
    </div>
    <review-dialog
      :isDialogShow="rollcall.isDialogShow"
      @submitReview="handleSubmitReview"
      @hideDialog="handleHideDialog">
    </review-dialog>
  </div>
</template>

<script>
import { avatarCover } from '@/assets/js/data';
import ReviewDialog  from '@/components/ReviewDialog';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
    components: {
    ReviewDialog
  },
  data() {
    return {
      host: this.$getCookie('host'),
      avatarCover,
      courseId: this.$route.params.courseId,
    }
  },
  computed: {
    // ...mapState({
    //   rollcall: state => state.activity.rollcall
    // })
    rollcall() {
      return this.$store.state.activity.rollcall
    }
  },
  methods: {
    ...mapActions([
      types.SET_ROLLCALL_REVIEW_DIALOG,
      types.SET_ROLLCALL_REVIEW_CURRENT_ID,
      types.ROLLCALL_REVIEW]),
    showReviewDialog(resultId) {
      this[types.SET_ROLLCALL_REVIEW_CURRENT_ID](resultId);
      this[types.SET_ROLLCALL_REVIEW_DIALOG](true);
    },
    handleSubmitReview({ score, remark }) {
      this[types.ROLLCALL_REVIEW]({
        courseId: this.courseId,
        resultId: this.rollcall.currentReviewId,
        score,
        remark
      }).then((res) => {
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
      this[types.SET_ROLLCALL_REVIEW_DIALOG](false);
    }
  }
}
</script>
