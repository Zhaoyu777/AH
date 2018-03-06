<template>
  <div>
    <div v-show="tabIndex == 0" style="position:relative">
      <div v-if="courseData.before.length">
        <div v-for="link in courseData.before">
          <lesson-before :link="link" :role="role" :preview="preview"></lesson-before>
        </div>
      </div>
      <div class="no-data" v-else>
        <span>暂无课前内容</span>
      </div>
    </div>
    <div v-show="tabIndex == 1" style="position:relative">
      <div v-if="courseData.in.length">
        <div v-for="link in courseData.in" v-if="link.isVisible || role === 'teacher'">
          <div class="course-lesson-phase__link" v-if="link.taskType=='chapter'">{{link.title}}</div>
          <lesson-in :link="link" :lessonState="courseData.lessonStatus" :role="role" v-else-if="link.taskType=='lesson'" :preview="preview"></lesson-in>
        </div>
      </div>
      <div class="no-data" v-else>
        <span>暂无课中内容</span>
      </div>
    </div>
    <div v-show="tabIndex == 2" style="position:relative">
      <div v-if="courseData.after.length && (courseData.lessonStatus === 'teached' || role === 'teacher')">
        <div v-for="link in courseData.after">
          <lesson-after :link="link" :lessonState="courseData.lessonStatus" :role="role" :preview="preview"></lesson-after>
        </div>
      </div>
      <div class="no-data" v-else>
        <span>暂无课后内容</span>
      </div>
    </div>
  </div>
</template>

<script>
import LessonBefore from '@/pages/course/LessonBefore';
import LessonIn from '@/pages/course/LessonIn';
import LessonAfter from '@/pages/course/LessonAfter';
import { mapState } from 'vuex';

export default {
  components: {
    LessonBefore,
    LessonIn,
    LessonAfter
  },
  data() {
    return {
      preview: this.$route.query.preview,
    }
  },
  computed: {
    ...mapState({
      tabIndex: state => state.study.tabIndex,
      courseData: state => state.study.courseData,
      role: state => state.study.role
    })
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/course-lesson-phase.less';
</style>
