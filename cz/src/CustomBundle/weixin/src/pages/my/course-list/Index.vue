<template>
  <main class="app-main">
    <div class="my-course-list" v-if="courseData && courseData.length">
      <div class="col-6" v-for="item in courseData">
        <router-link class="my-course" :to="{ name: 'myCourse', params: { courseId: item.id } }">
          <div class="my-course__img">
            <img class="img-responsive" :src="item.cover ? host + item.cover : host + courseCover" alt="">
          </div>
          <div class="my-course__title">
            {{ item.title }}
          </div>
        </router-link>
      </div>
    </div>
    <div class="no-data" v-else-if="courseData && !courseData.length">
      <span v-if="role == 'teacher'">暂无在教课程</span>
      <span v-else>暂无在学课程</span>
    </div>
  </main>
</template>

<script>
import { courseCover } from '@/assets/js/data';
import api from '@/assets/js/api';

export default {
  data() {
    return {
      courseCover,
      courseData: null,
      host: this.$getCookie('host'),
      role: this.$getCookie('role')
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      let url;
      if (this.role === 'teacher') {
        url = api.my.teachingCourses();
      } else {
        url = api.my.learningCourses();
      }

      this.$isLoading();
      this.$http.get(url).then((res) => {
        this.$endLoading();
        this.courseData = res.data;
      }, () => {
        this.$endLoading();
        this.$ajaxError();
      })
    }
  }
}
</script>

<style scoped lang="less">
@import '~@/assets/less/mixins.less';
.my-course-list {
  margin: 1.25rem 0.625rem;
  &:before,
  &:after {
    content: " ";
    display: table;
  }
  &:after {
    clear: both;
  }
  .my-course {
    border: 0.0625rem solid #e5e5e5;
    padding: 0.625rem;
    margin: 0 0.3125rem 0.625rem;
    border-radius: 0.25rem;
    background: #fff;
    display: block;
  }
  .my-course__img {
    margin-bottom: 0.625rem;
  }
  .my-course__title {
    font-size: 0.875rem;
    color: #616161;
    line-height: 1.25rem;
    .text-overflow;
  }
}
</style>

<style lang="less">
  .col-6 {
    width: 50%;
    float: left;
  }
</style>
