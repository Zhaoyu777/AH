<template>
  <main class="app-main bg-white" v-if="courseData">
    <course-header :courseData="courseData"></course-header>
    <course-quick :courseData="courseData"></course-quick>
    <lesson-list></lesson-list>
  </main>
</template>

<script>
import CourseHeader from './Header';
import CourseQuick from '@/pages/course/Quick';
import LessonList from './List';

import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    CourseHeader,
    CourseQuick,
    LessonList
  },
  data() {
    return {
      host: this.$getCookie('host'),
      courseId: this.$route.params.courseId,
    }
  },
  created() {
    this.fetchData();
  },
  computed: {
    ...mapState({
      courseData: state => state.lesson.courseData,
    })
  },
  methods: {
    ...mapActions([types.LESSON_INIT, types.LESSON_CLEAR]),
    fetchData() {
      this[types.LESSON_CLEAR]();
      this[types.LESSON_INIT](this.courseId).catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/course-item.less';
@import '~@/assets/less/module/course-lesson-phase.less';
@import '~@/assets/less/mixins';

.course-lesson-item {
  .course-lesson-item__header {
    background-color: #f9f9f9;
    padding: 1.125rem 0.9375rem;
    line-height: 1.25rem;
    margin-bottom: 0.625rem;
  }
  .course-lesson-item__title {
    color: #2b333b;
    font-size: 0.875rem;
    font-weight: bold;
    line-height: 1.25rem;
    .text-overflow;
  }
  .course-lesson-item__reviews {
    text-align: right;
    width: 3.75rem;
  }
  .course-lesson-item__toggle {
    text-align: right;
    width: 3.125rem;
  }
  .course-lesson-item__body {
    padding: 0.9375rem;
    background: #fff;
  }
}
</style>

