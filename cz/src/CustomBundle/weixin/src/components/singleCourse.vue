<template>
  <div>
    <div v-if="course.lessonStatus && role === 'student'">
      <div class="course-item teaching-course-item" @click="handleItem">
        <div class="teaching-course-item__thumb">
          <img :src="course.cover ? host + course.cover : host + courseCover" alt="">
          <span class="code-tag code-tag--danger course-item__state" v-if="role === 'teacher' && !course.isLesson">未备课</span>
        </div>
        <div class="">
          <div class="teaching-course-item__title">{{course.courseSetTitle}}</div>
          <div class="course-item__class">{{course.courseTitle}}</div>
          <div class="teaching-course-item__task-number clearfix" >
            <span v-if="role === 'student' && course.taskNum > 0">{{course.taskNum}}个未完成任务</span>
            <span class="teaching-course-item__status">授课中...</span>
          </div>
        </div>
      </div>
    </div>
    <div v-else>
      <div class="weui-flex course-item" @click="handleItem">
        <div class="course-item__thumb">
          <img :src="course.cover ? host + course.cover : host + courseCover" alt="">
          <span class="code-tag code-tag--danger course-item__state" v-if="role == 'teacher' && !course.isLesson">未备课</span>
          <badge :text="course.taskNum"  class="weui-badge course-item__task-number" v-if="role == 'student' && course.taskNum > 0"></badge>
        </div>
        <div class="weui-flex__item">
          <div class="course-item__title">{{course.courseSetTitle}}</div>
          <div class="course-item__class">{{course.courseTitle}}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { courseCover } from "@/assets/js/data";
import { Badge } from "vux";

export default {
  props: {
    course: {
      type: Object,
    },
    role: {
      type: String,
      default: 'teacher',
    }
  },
  components: {
    Badge,
  },
  data() {
    return {
      courseCover,
      host: this.$getCookie("host"),
    }
  },
  methods: {
    handleItem: function() {
      const course = this.course;
      this.$emit('handleItem', {
        id: course.id,
        isLesson: course.isLesson
      })
    }
  }
}
</script>

<style lang="less">
  @import "~@/assets/less/module/course-item.less";

  .teaching-course-item__task-number {
    width: 100%;
  }
  .course-item__title {
    line-height: 1;
    color: rgba(49,49,49,0.87);
  }
  .teaching-course-item {
    padding: .9375rem;
    &__thumb {
      position: relative;
      img {
        width: 100%;
      }
    }
    &__title {
      padding: 1rem 0 .625rem 0;
      font-size: 1.5rem;
      color: rgba(49,49,49,0.87);
    }
    &__task-number {
      color: #FD4852;
      font-size: .875rem;
      padding-top: 1rem;
    }
    &__status {
      float: right;
      color: #4993E9;
    }
  }
</style>

