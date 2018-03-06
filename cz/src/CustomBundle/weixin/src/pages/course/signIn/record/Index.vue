<template>
  <main class="app-main">
    <div class="signin-record" v-for="item in signInData">
      <div class="signin-record__title">{{ item.lesson }}</div>
      <div class="signin-record__time">{{ $dateFormatFn(item.updatedTime) }}</div>
      <div class="signin-record__address">{{ item.address }}</div>
    </div>
    <div class="no-data" v-if="signInData && signInData.length === 0">暂无签到记录</div>
  </main>
</template>

<script>
import api from '@/assets/js/api';

export default {
  data() {
    return {
      signInData: null,
      courseId: this.$route.params.courseId
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$isLoading();
      this.$http.get(api.signIn.record(this.courseId)).then((response) => {
        this.$endLoading();
        this.signInData = response.data;
        console.log(this.signInData);

      }, (response) => {
        this.$endLoading();
        this.$ajaxError();
      });
    }
  }
}
</script>

<style lang="less" scoped>
.signin-record {
  position: relative;
  border-bottom: 0.0625rem solid #e5e5e5;
  padding: 0.625rem 0.9375rem;
  .signin-record__title {
    color: #313131;
    font-size: 0.9375rem;
    margin-bottom: 0.625rem;
  }
  .signin-record__time {
    font-size: 0.75rem;
    color: #919191;
  }
  .signin-record__address {
    position: absolute;
    top: 1.25rem;
    right: 0.9375rem;
    text-align: right;
  }
}
</style>
