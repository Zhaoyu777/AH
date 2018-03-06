<template>
  <main class="app-main">
    <div class="course-list" v-if="courseList && courseList.length">
      <div class="course-item__teaching">
        <single-course :course="course" v-for="course,index in teachingCourseList" :key="index"
          @handleItem="handleTeachingItem" :role="role"></single-course>
      </div>
      <single-course :course="course" v-for="course,index in teachedCourseList" :key="index"
        @handleItem="handleItem" :role="role"></single-course>
    </div>
    <div class="no-data" v-else-if="courseList && !courseList.length">
      <span>暂无旁听课程</span>
    </div>
  </main>
</template>

<script>
import singleCourse from '@/components/singleCourse';
import api from "@/assets/js/api";
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    singleCourse
  },
  data() {
    return {
      courseList: null,
      role: 'student',
      host: this.$getCookie('host')
    }
  },
  computed: {
    ...mapState({
      currentActivity: state => state.activity.currentActivity,
    }),
    teachingCourseList: function() {
      const courseList = this.courseList.filter(value => {
        return value.lessonStatus;
      })
      return courseList;
    },
    teachedCourseList: function() {
      const courseList = this.courseList.filter(value => {
        return !value.lessonStatus;
      })
      return courseList;
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    ...mapActions([
      types.CURRENT_ACTIVITY
    ]),
    fetchData() {
      this.$isLoading();

      this.$http.get(api.course.learning()).then((response) => {
        this.$endLoading();
        this.courseList = response.data.courses;
      }, (response) => {
        this.$endLoading();
        this.$ajaxError();
      });
    },
    handleItem({ id, isLesson }) {
      this.$router.push({ name: 'student-lesson', params: { courseId: id } });
    },
    handleTeachingItem({ id, isLesson }) {
      this[types.CURRENT_ACTIVITY]({
        courseId: id
      }).then((res) => {
        if(!res.taskId) {
          this.$ajaxMessage("课程未开始");
          this.fetchData();
          return;
        }

        const courseId   = res.courseId,
              lessonId   = res.lessonId,
              taskId     = res.taskId,
              activityId = res.activityId,
              activityType = res.activityType;
        const params = { courseId, lessonId, taskId, activityId };

        if(taskId === '0') {
          this.$router.push({name: 'learning-activity', params })
        } else {
          this.$router.push({ name: `learning-${res.activityType}`, params });
        }
      })
    }
  }
}
</script>

<style lang="less">
.course-list {
  padding: 0.625rem;
}

.teaching__tips {
  font-size: 1.0625rem;
}

.course-item__teaching {
  margin-bottom: 1rem;
}
</style>
