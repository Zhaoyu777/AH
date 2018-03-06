<template>
  <div class="course-header">
    <div class="weui-flex course-item">
      <div class="course-item__thumb">
        <img :src="courseData.cover ? host + courseData.cover : host + courseCover" alt="">
      </div>
      <div class="weui-flex__item">
        <div class="course-item__title">{{courseData.courseSetTitle}}</div>
        <div class="course-item__class">{{courseData.courseTitle}}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { courseCover } from '@/assets/js/data';

export default {
  props: ['courseData'],
  data() {
    return {
      courseCover,
      host: this.$getCookie('host')
    }
  }
}
</script>

<style lang="less">
.course-header {
  box-shadow: 0 0.25rem 0.75rem 0 #efefef;
  margin-bottom: 0.9375rem;
  .course-item {
    border-radius: 0;
    padding: 0.9375rem 1.25rem;
  }
}
</style>
