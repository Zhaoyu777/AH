<template>
  <div class="course-header">
    <div class="weui-flex">
      <img class="course-cover" :src="courseData.cover ? host + courseData.cover : host + courseCover" alt="">
      <p class="course-title">{{courseData.courseSetTitle}}</p>
      <!-- <p class="course-class">{{courseData.courseTitle}}</p> -->
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

<style lang="less" scoped>
.course-header {
  position: relative;
  margin-bottom: .625rem;
  .course-cover {
    position: relative;
    width: 100%;
  }
  .course-title {
    position: absolute;
    bottom: .9375rem;
    width: 100%;
    padding-left: .9375rem;
    font-size: 1.1875rem;
    color: rgba(255,255,255,0.87);
  }
}
</style>
