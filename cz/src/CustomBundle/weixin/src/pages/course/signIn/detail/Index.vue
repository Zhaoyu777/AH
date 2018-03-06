<template>
  <main class="app-main" v-if="lessonTitle">
    <sign-in-header ref="signInHeader"></sign-in-header>
    <div class="course-signin__record-list">
      <router-link class="course-signin__record-item" :to="{ name: 'signInResult', params: { courseId, lessonId, timeId: 1 } }" v-if="signIn.time > 0">
        第一次签到 <i class="cz-icon cz-icon-chevronright"></i>
      </router-link>
      <router-link class="course-signin__record-item" :to="{ name: 'signInResult', params: { courseId, lessonId, timeId: 2 } }" v-if="signIn.time > 1">
        第二次签到 <i class="cz-icon cz-icon-chevronright"></i>
      </router-link>
    </div>
  </main>
</template>

<script>
import SignInHeader from './Header';
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    SignInHeader
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      // role: this.$getCookie('role'),
    }
  },
  created() {
    this.fetchData();
  },
  computed: {
    ...mapState({
      lessonTitle: state => state.signIn.lessonTitle,
      signIn: state => state.signIn.signIn,
    })
  },
  methods: {
    ...mapActions([types.SIGNIN_INIT]),
    fetchData() {
      this[types.SIGNIN_INIT](this.lessonId).catch((res) => {
        this.$endLoading();
        this.$ajaxError();

      }).then((res) => {
        if (this.signIn.status === 'start') {
          this.$refs.signInHeader.countdown();
        }

      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>

<style lang="less">
.course-signin__record-item {
  font-size: 1.0625rem;
  padding: 0.9375rem 1.25rem;
  border-bottom: 0.0625rem solid #e5e5e5;
  display: block;
  color: #616161;
  i {
    float: right;
    color: #c7c7c7;
    font-size: 1.25rem;
  }
}
</style>
