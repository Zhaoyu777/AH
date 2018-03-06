<template>
  <main class="app-main">
    <div class="course-recourse" v-for="(file, index) in files" v-if="files && files.length > 0">
      <div class="course-recourse-header" v-if="file.type == 'ppt'">
        PPT
      </div>
      <div class="course-recourse-header" v-else-if="file.type == 'video'">
        视频
      </div>
      <div class="course-recourse-header" v-else-if="file.type == 'audio'">
        音频
      </div>
      <div class="course-recourse-header" v-else-if="file.type == 'doc'">
        文档
      </div>
      <ul class="course-recourse-list" v-for="(file, index) in file.files">
        <li class="weui-flex course-recourse-item">
          <div class="course-recourse-item__thumb">
            <img :src="resourceCover" alt="">
          </div>
          <div class="weui-flex__item">
            <div class="course-recourse-item__title">{{ file.title }}</div>
            <div class="course-recourse-item__time">{{ file.date }}</div>
          </div>
        </li>
      </ul>
    </div>
    <div class="no-data" v-if="files && files.length == 0">暂无资源</div>
  </main>
</template>

<script>
import { resourceCover } from '@/assets/js/data';
import api from '@/assets/js/api';

export default {
  data() {
    return {
      // TODO: 需要换成文件的封面
      resourceCover,
      files: null,
      host: this.$getCookie('host'),
      courseId: this.$route.params.courseId,
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$isLoading();
      this.$http.get(api.course.resources(this.courseId)).then((response) => {
        document.title = response.data.courseTitle;
        this.$endLoading();
        this.files = response.data.files;
        console.log(response.data);

      }, (response) => {
        this.$endLoading();
        this.$ajaxError();
      });
    }
  }
}
</script>

<style lang="less">
.course-recourse-header {
  background: #f3f3f7;
  padding: 0.625rem 1.25rem;
  color: #919191;
}

.course-recourse-empty {
  color: #919191;
  padding: 1.25rem;
  background: #fff;
}

.course-recourse-list {
  background: #fff;
}

.course-recourse-item {
  padding: 0.625rem 1.25rem;
  border-bottom: 0.0625rem solid #f0f0f0;
  .course-recourse-item__thumb {
    margin-right: 0.9375rem;
    img {
      width: 3.125rem;
      height: 3.125rem;
      border-radius: 0.3125rem;
    }
  }
  .course-recourse-item__title {
    font-size: 0.875rem;
    color: #313131;
    margin-top: 0.3125rem;
    margin-bottom: 0.625rem;
  }
  .course-recourse-item__time {
    font-size: 0.875rem;
    color: #919191;
  }
}
</style>
