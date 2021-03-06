<template>
  <div>
    <photo-upload v-if="role === 'student'" @download="handleDownload"></photo-upload>
    <photo-action v-if="role === 'teacher'" :courseId="courseId" :lessonId="lessonId" :taskId="taskId"></photo-action>
    <photo-upload @download="handleDownload" :taskId="taskId"></photo-upload>
    <none-group @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog"></none-group>

    <review-dialog :isDialogShow="isDialogShow" @submitReview="handleSubmitReview" @hideDialog="handleHideDialog"></review-dialog>
  </div>
</template>

<script>
import ReviewDialog  from '@/components/ReviewDialog';
import PhotoUpload from './PhotoUpload';
import SelfPhoto from './SelfPhoto';
import PhotoAction from './PhotoAction';
import NoneGroup from './NoneGroup';

import { mapState, mapActions, mapMutations } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    ReviewDialog,
    PhotoUpload,
    SelfPhoto,
    PhotoAction,
    NoneGroup,
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      practice: state => state.activity.practice,
      role: state => state.activity.activityData.role,
    }),
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      taskId: this.$route.params.taskId,
      activityId: this.$route.params.activityId,
      isDialogShow: false,
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.PRACTICE_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.PRACTICE_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.PRACTICE_INIT,
      types.PRACTICE_LIKE,
      types.PRACTICE_CANCEL_LIKE,
      types.SET_PRACTICE_REVIEW_CURRENT_ID,
      types.PRACTICE_REVIEW,
    ]),
    ...mapMutations([
      types.PRACTICE_CLEAR,
      types.SET_PRACTICE_REVIEW_CURRENT_ID
    ]),
    fetchData() {
      const taskId = this.taskId;
      this[types.PRACTICE_INIT](taskId)
        .catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
    },
    handleDownload() {
      this.fetchData();
    },
    handlePhoto(contentId) {
      this.$router.push({
        name: 'practiceContent',
        params: {
          courseId: this.courseId,
          lessonId: this.lessonId,
          taskId: this.taskId,
          activityId: this.activityId,
          contentId
        }
      })
    },
    handleLike(contentId) {
      this[types.PRACTICE_LIKE]({ contentId }).then(() => {
        this.$vux.toast.show({
          text: '点赞成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleCancelLike(contentId) {
      this[types.PRACTICE_CANCEL_LIKE]({ contentId }).then(() => {
        this.$vux.toast.show({
          text: '取消点赞成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    showReviewDialog(resultId) {
      this[types.SET_PRACTICE_REVIEW_CURRENT_ID](resultId);
      this.isDialogShow = true;
    },
    handleSubmitReview({ score, remark }) {
      const practice = this.practice;
      this[types.PRACTICE_REVIEW]({
        courseId: this.courseId,
        contentId: practice.currentContentId,
        resultId: practice.currentResultId,
        score,
        remark
      }).then(() => {
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
      this.isDialogShow = false;
    },
    handleDownload() {
      this.fetchData();
    },
    updateData(data) {
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.taskId = data.taskId;
      this.activityId = data.activityId;
    }
  }
}
</script>

<style lang="less">
.wall-group {
  background: #fff;
  border-radius: 0.625rem;
  margin: 0.9375rem 0.625rem;
  padding: 1.25rem;
  line-height: 1.875rem;
  .wall-group__title {
    color: #4993e9;
    font-size: 0.875rem;
  }
  .wall-group__info {
    color: #919191;
  }
}

.wall-photo {
  background: #fff;
  border-radius: 0.625rem;
  margin: 0.9375rem 0.625rem;
}

.wall-photo__number {
  border-radius: 50%;
  text-align: center;
  color: #fff;
  background: #ccc;
  width: 1.875rem;
  height: 1.875rem;
  margin-right: 0.625rem;
  &.wall-photo__number--primary {
    background: #4993e9;
  }
}

.wall-photo__header {
  padding: 0.625rem 1.25rem;
  line-height: 1.875rem;

  .wall-photo__avatar {
    margin-right: 0.625rem;
    display: inherit;
  }
  .wall-photo__title {
    font-size: 1.0625rem;
    color: #313131;
  }
}

.wall-photo__loading {
  text-align: center;
  line-height: 6.25rem;
  color: #919191;
  font-size: 0.875rem;
}

.wall-photo__body {
  position: relative;
  height: 12.5rem;
  display: block;
  background-size: 100% auto;
  background-position: center;
  background-repeat: no-repeat;
  .wall-photo__thumb {
    width: 100%;
    height: auto;
  }
  .wall-photo__action {
    position: absolute;
    bottom: 0;
    right: 0;
    left: 0;
    padding: 1.25rem;
    line-height: 1.375rem;
    text-align: right;
    &:after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      left: 0;
      top: 0;
      z-index: 0;
      border-bottom-left-radius:  0.625rem;
      border-bottom-right-radius:  0.625rem;
      opacity: 0.35;
      background-image: linear-gradient(-1deg, #000000 0%, rgba(255,255,255,0.00) 100%);
    }
  }
}

.wall-photo__review {
  position: relative;
  z-index: 1;
  float: left;
}

.wall-photo__star,
.wall-photo__comment {
  position: relative;
  z-index: 1;
  color: #fff;
  font-size: 0.9375rem;
  i {
    font-size: 1.25rem;
  }
}

.wall-photo__star {
  padding: 0.625rem;
}
</style>
