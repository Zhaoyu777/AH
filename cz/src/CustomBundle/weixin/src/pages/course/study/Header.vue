<template>
  <div class="lesson-header" :style="{ backgroundImage: 'url(' + cover + ')' }">
    <div class="lesson-header__mask"></div>
    <span class="lesson-header__class">{{ courseData.courseTitle }}</span>
    <div class="lesson-header__title">课次{{courseData.lessonNumber}} {{ courseData.lessonTitle }}</div>
  </div>
</template>

<script>
import { courseCover } from '@/assets/js/data';
import { mapState } from 'vuex';

export default {
  computed: {
    ...mapState({
      courseData: state => state.study.courseData
    }),
    cover() {
      return this.$getCookie('host') + (this.courseData.cover || courseCover)
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/variables.less';

.lesson-header {
  position: relative;
  height: 10.625rem;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  .lesson-header__mask {
    position: absolute;
    height: 4rem;
    bottom: 0;
    left: 0;
    right: 0;
    opacity: 0.2;
    background-image: linear-gradient(-180deg, rgba(0,0,0,0.00) 0%, #000000 100%);
  }
  .lesson-header__class {
    position: absolute;
    top: 0.9375rem;
    left: 0.9375rem;
    background: rgba(red(@brand-primary),green(@brand-primary),blue(@brand-primary), .54);
    padding: 0.1875rem 0.625rem;
    color: #fff;
    border-radius: 0.125rem;
  }
  .lesson-header__title {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 0.875rem 1rem;
    color: #fff;
    font-size: 1.0625rem;
  }
}
</style>
