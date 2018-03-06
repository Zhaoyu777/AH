<template>
  <main class="app-main bg-white" v-if="signInStatus">
    <div class="course-signin-student__title" v-if="signInStatus !== 'attend'">
      请输入课程签到码
    </div>
    <sign-in-success></sign-in-success>
    <input-code v-if="signInStatus !== 'attend'" :code="code"></input-code>
    <sign-in-action :code="code" :latitude="latitude" :longitude="longitude"></sign-in-action>
  </main>
</template>

<script>
import { Group, XInput } from 'vux';
import InputCode  from '@/components/InputCode';
import api from '@/assets/js/api';
import SignInSuccess from './Success';
import SignInAction from './Action';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    InputCode,
    SignInSuccess,
    SignInAction
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      timeId: this.$route.params.timeId,
      code: [],
      address: null,
      latitude: null,
      longitude: null,
    }
  },
  created() {
    this.$jssdk();
    // 获得经纬度
    this.getLocation();
    this.fetchData();
  },
  beforeDestroy() {
    // this.$closeSocket();
  },
  computed: {
    ...mapState({
      signInStatus: state => state.signIn.signInStatus
    })
  },
  methods: {
    ...mapActions([types.STUDENT_SIGNIN_INIT]),
    getLocation() {
      this.$wechat.ready(() => {
        let _this = this;
        this.$wechat.getLocation({
          type: 'wgs84',
          success(res) {
            _this.latitude = res.latitude;
            _this.longitude = res.longitude;
          }
        });
      })
    },
    fetchData() {
      //此处有问题，签到时不应该再去连接一次socket,具体原因待查
      this[types.STUDENT_SIGNIN_INIT]({lessonId: this.lessonId, timeId:this.timeId})
      .then(() => {
        this.$getSocket(this.courseId, this.lessonId);
      })
      .catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
// 复写
#vue_input_code  {
 width: auto !important;
 padding: 0 3.75rem !important;
}

#vue_input_code .input > span.first {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}

#vue_input_code .input > span:last-child {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

#vue_input_code .input > span {
  border-color: #ccc !important;
}
</style>

<style lang="less">
@import '~@/assets/less/module/signin-success.less';
.lesson-action {
  margin: 1.875rem 0;
  padding: 0 0.9375rem;
}
.course-signin-student__title {
  margin: 4.375rem 0.9375rem 1.75rem;
  text-align: center;
  font-size: 1.0625rem;
}
</style>
