<template>
  <div class="course-lesson-list">
    <div class="course-lesson-item" v-for="(lesson, index) in courseData.lessons" :key="index">
      <div class="weui-flex course-lesson-item__header">
        <div class="weui-flex__item course-lesson-item__title" @click="goLessonDetail(lesson.id)">
          {{lesson.title}}
        </div>
        <div class="course-lesson-item__reviews">
          <span class="code-tag code-tag--primary" v-if="lesson.status === 'teached' && lesson.isEvaluation">已授课</span>
          <span class="code-tag code-tag--danger" @click="showReviewsDialog(lesson.id, index)"
            v-if="lesson.status == 'teached' && !lesson.isEvaluation">未评价</span>
          <span class="code-tag code-tag--default" v-if="lesson.status === 'created'">未授课</span>
        </div>
      </div>
      <course-review :reviewCurrentId="reviewCurrentId" :dialogShow="dialogShow" @getDialogShow="getDialogShowListener"></course-review>
    </div>
  </div>
</template>

<script>
import CourseReview from './CourseReview';

import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      reviewCurrentId: null,
      dialogShow: false,
      courseId: this.$route.params.courseId
    }
  },
  computed: {
    ...mapState({
      courseData: state => state.lesson.courseData,
      role: state => state.lesson.courseData.role
    })
  },
  components: {
    CourseReview
  },
  methods: {
    ...mapMutations([
      types.LESSON_REVIEW_CURRENT_ID
    ]),
    showReviewsDialog(id, index) {
      this.reviewCurrentId = id;
      this[types.LESSON_REVIEW_CURRENT_ID](index);
      this.dialogShow = !this.dialogShow;
    },
    getDialogShowListener() {
      this.dialogShow = !this.dialogShow;
    },
    goLessonDetail(lessonId) {
      this.$router.push({name: 'study', params: {courseId: this.courseId}, query: {lessonId: lessonId}})
    }
  }
}
</script>
