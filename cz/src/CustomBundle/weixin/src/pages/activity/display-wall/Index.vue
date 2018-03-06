<template>
  <div>
    <photo-upload v-if="displayWall.hasGroup && role === 'student'" @download="handleDownload"></photo-upload>
    <random-action v-show="!displayWall.hasGroup && role === 'student' && displayWall.status == 'start'" :groups="groupList" @randomJoin='randomJoin'></random-action>
    <self-photo @photoEvent="handlePhoto" v-if="role === 'student'"></self-photo>
    <photo-action v-if="role === 'teacher' && isAjaxEnd" :courseId="courseId" :lessonId="lessonId" :taskId="taskId"></photo-action>

    <none-group @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog" v-if="displayWall.groupWay === 'none'"></none-group>

    <fixed-person @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog" v-else-if="displayWall.groupWay === 'fixed' && displayWall.submitWay === 'person'"></fixed-person>

    <fixed-group @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog" v-else-if="displayWall.groupWay === 'fixed' && displayWall.submitWay === 'group'"></fixed-group>

    <fixed-person @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog" v-else-if="displayWall.groupWay === 'random' && displayWall.submitWay === 'person'"></fixed-person>

    <fixed-group @photoEvent="handlePhoto" @photoLike="handleLike" @photoCancelLike="handleCancelLike" @reviewDialog="showReviewDialog" v-else-if="displayWall.groupWay === 'random' && displayWall.submitWay === 'group'"></fixed-group>

    <review-dialog :isDialogShow="displayWall.isDialogShow" @submitReview="handleSubmitReview" @hideDialog="handleHideDialog"></review-dialog>
  </div>
</template>

<script>
import ReviewDialog  from '@/components/ReviewDialog';
import PhotoUpload from './PhotoUpload';
import SelfPhoto from './SelfPhoto';
import PhotoAction from './PhotoAction';
import FixedGroup from './FixedGroup';
import FixedPerson from './FixedPerson';
import NoneGroup from './NoneGroup';
import RandomAction from '@/components/RandomAction';

import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    ReviewDialog,
    PhotoUpload,
    SelfPhoto,
    PhotoAction,
    FixedGroup,
    FixedPerson,
    NoneGroup,
    RandomAction
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      displayWall: state => state.activity.displayWall,
      role: state => state.activity.activityData.role,
      groups: state=> state.activity.displayWall.groups
    }),
    groupList() {
      const menus = {};
      if (this.groups) {
        this.groups.map((group) => {
          menus[group.groupId] = group.title;
        });
      }
      menus['title.noop'] = `<div style="color:#313131">请选择一组加入</div><div style="color:#616161;font-size:.75rem;">加入后无法更改哦</div>`
      return menus;
    },
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      taskId: this.$route.params.taskId,
      activityId: this.$route.params.activityId,
      isAjaxEnd: false
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.DISPLAY_WALL_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.DISPLAY_WALL_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.DISPLAY_WALL_INIT,
      types.DISPLAY_WALL_CLEAR,
      types.DISPLAY_WALL_LIKE,
      types.DISPLAY_WALL_CANCEL_LIKE,
      types.SET_DISPLAY_WALL_REVIEW_CURRENT_ID,
      types.SET_DISPLAY_WALL_REVIEW_DIALOG,
      types.DISPLAY_WALL_REVIEW,
      types.DISPLAY_WALL_RANDOM_JOIN]),
    fetchData() {
      this[types.DISPLAY_WALL_INIT](this.taskId).then(() => {
        this.isAjaxEnd = true;
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleDownload() {
      this.fetchData();
    },
    handlePhoto(contentId) {
      this.$router.push({
        name: 'displayWallContent',
        params: {
          courseId: this.courseId,
          lessonId: this.lessonId,
          taskId: this.taskId,
          activityId: this.activityId,
          contentId
        }
      })
    },
    handleLike(item) {
      this[types.DISPLAY_WALL_LIKE](item).then(() => {
        this.$vux.toast.show({
          text: '点赞成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleCancelLike(item) {
      this[types.DISPLAY_WALL_CANCEL_LIKE](item).then(() => {
        this.$vux.toast.show({
          text: '取消点赞成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    showReviewDialog(resultId) {
      this[types.SET_DISPLAY_WALL_REVIEW_CURRENT_ID](resultId);
      this[types.SET_DISPLAY_WALL_REVIEW_DIALOG](true);
    },
    handleSubmitReview({ score, remark }) {
      this[types.DISPLAY_WALL_REVIEW]({
        resultId: this.displayWall.currentReviewId,
        groupWay: this.displayWall.groupWay,
        submitWay: this.displayWall.submitWay,
        score,
        remark
      }).then((res) => {
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
      this[types.SET_DISPLAY_WALL_REVIEW_DIALOG](false);
    },
    randomJoin(item) {
      this[types.DISPLAY_WALL_RANDOM_JOIN](item).then(() => {
        this.$vux.toast.show({
          text: '分组成功'
        });
      });
    },
    updateData(data) {
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.taskId = data.taskId;
      this.activityId = data.activityId;
      this.isAjaxEnd = false;
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
  border-bottom-left-radius:  0.625rem;
  border-bottom-right-radius:  0.625rem;
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
