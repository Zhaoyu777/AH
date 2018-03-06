<template>
  <div>
    <div class="wall-photo-warp wall-group"
      v-for="(item, index) in brainStorm.groups"
      v-if="item.results.length">
      <div class="weui-flex">
        <div class="wall-photo__number wall-photo__number--primary">
          {{ index + 1 }}组
        </div>
        <div class="weui-flex__item wall-group__title">
          <span v-for="(member, index) in item.results" v-if="index < 4">
            <img class="code-avatar wall-photo__avatar"
              :src="member.avatar ? host + member.avatar : host + avatarCover" />
          </span>
        </div>
        <div class="wall-group__info"><span class="text-primary">已提交</span></div>
      </div>
      <div v-for="member in item.results">
        <div class="wall-content__body">
          {{ member.content }}
        </div>
        <grade-btn
          :item="member"
          @showReviewDialog="showReviewDialog(member.id)">
        </grade-btn>
        <review-dialog
          :isDialogShow="brainStorm.isDialogShow"
          @submitReview="handleSubmitReview"
          @hideDialog="handleHideDialog">
        </review-dialog>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex';
import { avatarCover } from '@/assets/js/data';
import GradeBtn  from '@/components/GradeBtn';
import ReviewDialog  from '@/components/ReviewDialog';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    GradeBtn,
    ReviewDialog
  },
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
    }
  },
  computed: {
    ...mapState({
      brainStorm: state => state.activity.brainStorm
    })
  },
  methods: {
    ...mapMutations([
      types.SET_BRAIN_STORM_REVIEW_DIALOG
    ]),
    ...mapActions([
      types.SET_BRAIN_STORM_REMARK_CURRENT_ID,
      types.BRAIN_STORM_REMARK
    ]),
    handleSubmitReview({ score, remark, resultId }) {
      this[types.BRAIN_STORM_REMARK]({
        resultId: this.brainStorm.currentReviewId,
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
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleHideDialog() {
      this[types.SET_BRAIN_STORM_REVIEW_DIALOG](false);
    },
    showReviewDialog(resultId) {
      this[types.SET_BRAIN_STORM_REMARK_CURRENT_ID](resultId);
      this[types.SET_BRAIN_STORM_REVIEW_DIALOG](true);
    }
  }
}
</script>