<template>
  <main class="app-main">
    <div class="course-list" v-if="courseList && courseList.length">
      <div v-if="role === 'teacher'" class="teahcer-area" >
        <single-course :course="course" v-for="course,index in courseList" :key="index"
          @handleItem="handleItem" :role="role"></single-course>
      </div>
      <div v-else class="student-area">
        <div class="course-item__teaching" >
          <single-course :course="course" v-for="course,index in teachingCourseList" :key="index"
            @handleItem="handleTeachingItem" :role="role"></single-course>
        </div>
        <div>
          <single-course :course="course" v-for="course,index in teachedCourseList" :key="index"
            @handleItem="handleItem" :role="role"></single-course>
        </div>
      </div>
    </div>
    <div class="no-data" v-else-if="courseList && !courseList.length">
      <span v-if="role === 'teacher'">暂无课堂教学课程，点击慕课查看在线课程</span>
      <span v-else>暂无课堂学习课程，点击慕课查看在线课程</span>
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
      role: this.$getCookie("role")
    };
  },
  computed: {
    ...mapState({
      currentActivity: state => state.activity.currentActivity,
    }),
    teachingCourseList: function() {
      const courseList = this.courseList.filter(value => {
        return value.lessonStatus;
      });
      return courseList;
    },
    teachedCourseList: function() {
      const courseList = this.courseList.filter(value => {
        return !value.lessonStatus;
      });
      return courseList;
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    ...mapActions([types.CURRENT_ACTIVITY]),
    fetchData() {
      this.$isLoading();
      if (this.role === "teacher") {
        this.$http.get(api.course.teaching()).then(
          response => {
            this.$endLoading();
            this.courseList = response.data.courses;
          },
          response => {
            this.$endLoading();
            this.$ajaxError();
          }
        );
      } else {
        this.$http.get(api.course.learning()).then(
          response => {
            this.$endLoading();
            this.courseList = response.data.courses;
          },
          response => {
            this.$endLoading();
            this.$ajaxError();
          }
        );
      }
    },
    handleItem({ id, isLesson }) {
      if (this.role === "teacher" && !isLesson) {
        this.$vux.toast.show({
          type: "warn",
          text: "暂未备课"
        });
        return;
      }

      let name = this.role === 'teacher' ? 'study' : 'student-lesson';

      this.$router.push({ name: name, params: { courseId: id } });
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
};
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

