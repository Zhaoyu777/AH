<template>
  <main class="app-main bg-white" v-if="courseData">
    <course-header></course-header>
    <phase-tab></phase-tab>
    <!-- <teach-action v-if="role === 'teacher'"></teach-action> -->
    <study-action v-if="role === 'student'"></study-action>
    <course-quick :courseData="courseData"></course-quick>
    <course-phase></course-phase>
  </main>
</template>

<script>
import CourseHeader from './Header';
import PhaseTab from './PhaseTab';
import CourseQuick from '@/pages/course/Quick';
import CoursePhase from './Phase';
import TeachAction from './TeachAction';
// import StudyAction from './StudyAction';
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';
import { getToken } from '@/assets/js/socket';
import io from 'socket.io-client';

export default {
  components: {
    CourseHeader,
    PhaseTab,
    CourseQuick,
    CoursePhase,
    TeachAction,
    // StudyAction
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.query.lessonId,
    }
  },
  created() {
    this[types.STUDY_CLEAR]();
    this.fetchData();
  },
  beforeDestroy() {
    this.$closeSocket();
  },
  beforeRouteLeave(to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    next();
  },
  computed: {
    ...mapState({
      courseData: state => state.study.courseData,
      role: state => state.study.role
    })
  },
  methods: {
    ...mapActions([types.STUDY_INIT, types.STUDY_CLEAR]),
    fetchData() {
      this[types.STUDY_INIT]({
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$getSocket(this.courseId, this.courseData.lessonId);
        document.title = this.courseData.courseSetTitle;
      }).catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>

<style lang="less">
.lesson-action {
  margin: 1.875rem 0;
  padding: 0 0.9375rem;
}
.lesson-action__signin {
  margin-right: 0.625rem;
}
</style>
