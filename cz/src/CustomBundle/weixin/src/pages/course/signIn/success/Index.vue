<template>
  <main class="app-main bg-white" v-if="signInData">
    <div class="signin-success__header">
      <i class="cz-icon cz-icon-iccheckcircleblack24px signin-success__icon"></i>
      <div class="signin-success__title">签到成功</div>
    </div>
    <div class="signin-success__info">
      <div class="weui-flex signin-success__info-item">
        <div class="signin-success__info-label">课程</div>
        <div class="weui-flex__item signin-success__info-vaule">{{ signInData.courseTitle }}</div>
      </div>
      <div class="weui-flex signin-success__info-item">
        <div class="signin-success__info-label">课次</div>
        <div class="weui-flex__item signin-success__info-vaule">{{ signInData.lessonTitle }}</div>
      </div>
      <div class="weui-flex signin-success__info-item">
        <div class="signin-success__info-label">签到时间</div>
        <div class="weui-flex__item signin-success__info-vaule">{{ $dateFormatFn(signInData.updatedTime) }}</div>
      </div>
    </div>
    <div class="signin-success__action">
      <router-link class="weui-btn weui-btn_primary" :to="{ name: 'study', params: { courseId } }">完成</router-link>
    </div>
  </main>
</template>

<script>
import api from '@/assets/js/api';

export default {
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      timeId: this.$route.params.timeId,
      signInData: null,
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$isLoading();
      this.$http.get(api.signIn.success(this.lessonId, this.timeId)).then((response) => {
        this.$endLoading();
        this.signInData = response.data;
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
@import '~@/assets/less/module/signin-success.less';
.signin-success__info {
  border-top: 0.0625rem solid #e5e5e5;
  border-bottom: 0.0625rem solid #e5e5e5;
  .signin-success__info-item {
    margin: 0.9375rem;
    font-size: 0.875rem;
  }
  .signin-success__info-label {
    width: 6.25rem;
    color: #888;
    line-height:1.5;
  }
  .signin-success__info-vaule {
    text-align: right;
    color: #414141;
    line-height:1.5;
  }
}

.signin-success__action {
  margin: 0.9375rem 0.9375rem 3.125rem;
}
</style>
