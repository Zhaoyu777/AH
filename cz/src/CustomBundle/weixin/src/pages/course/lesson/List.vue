<template>
  <div class="course-lesson-list">
    <div class="course-lesson-item" v-for="(lesson, index) in courseData.lessons" :key="index">
      <div class="weui-flex course-lesson-item__header" @click="toggleShowPhase(index)">
        <div class="weui-flex__item course-lesson-item__title">
          {{lesson.title}}
        </div>
        <div class="course-lesson-item__reviews" v-if="role == 'student' && lesson.status == 'teached'">
          <span class="code-tag code-tag--primary" v-if="lesson.isEvaluation">已评价</span>
          <span class="code-tag code-tag--danger" @click="showReviewsDialog(lesson.id, index)" v-else>未评价</span>
        </div>
        <div class="course-lesson-item__toggle">
          <i class="cz-icon cz-icon-remove" v-if="lesson.isShowPhase"></i>
          <i class="cz-icon cz-icon-anonymous-iconfont" v-else></i>
        </div>
      </div>

      <div class="course-lesson-item__body" v-show="lesson.isShowPhase">
        <div class="course-lesson-phase">
          <div class="course-lesson-phase__header">课前</div>
          <div v-for="link in lesson.before">
            <lesson-before :link="link" :role="role"></lesson-before>
          </div>
        </div>

        <div class="course-lesson-phase">
          <div class="course-lesson-phase__header">课堂</div>
          <div v-for="link in lesson.in" v-if="link.isVisible || role === 'teacher'">
            <div class="course-lesson-phase__link" v-if="link.taskType=='chapter'">{{link.title}}</div>
            <lesson-in :link="link" :lessonState="lesson.status" :role="role" v-else-if="link.taskType=='lesson'"></lesson-in>
          </div>
        </div>

        <div class="course-lesson-phase">
          <div class="course-lesson-phase__header">课后</div>
          <div v-for="link in lesson.after" v-if="link.isVisible || role === 'teacher'">
            <lesson-after :link="link" :lessonState="lesson.status" :role="role"></lesson-after>
          </div>
        </div>
      </div>

      <course-review :reviewCurrentId="reviewCurrentId" :dialogShow="dialogShow" @getDialogShow="getDialogShowListener"></course-review>
    </div>
  </div>
</template>

<script>
import LessonBefore from '@/pages/course/LessonBefore';
import LessonIn from '@/pages/course/LessonIn';
import LessonAfter from '@/pages/course/LessonAfter';
import CourseReview from './CourseReview';

import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      reviewCurrentId: null,
      dialogShow: false,
    }
  },
  computed: {
    ...mapState({
      courseData: state => state.lesson.courseData,
      role: state => state.lesson.courseData.role
    })
  },
  components: {
    CourseReview,
    LessonBefore,
    LessonIn,
    LessonAfter
  },
  methods: {
    ...mapMutations([
      types.LESSON_REVIEW_CURRENT_ID
    ]),
    ...mapActions([
      types.UPDATE_LESSON_IS_SHOW_PHASE
    ]),
    toggleShowPhase(index) {
      this[types.UPDATE_LESSON_IS_SHOW_PHASE](index);
    },
    showReviewsDialog(id, index) {
      this.reviewCurrentId = id;
      this[types.LESSON_REVIEW_CURRENT_ID](index);
      this.dialogShow = !this.dialogShow;
    },
    getDialogShowListener() {
      this.dialogShow = !this.dialogShow;
    }
  }
}
</script>
