<template>
  <main class="app-main bg-white" v-if="studentData">
    <button-tab class="course-signin-tab">
      <button-tab-item :selected="currentKey === 'attend'" @on-item-click="itemSwitch('attend')">出勤</button-tab-item>
      <button-tab-item :selected="currentKey === 'absent'" @on-item-click="itemSwitch('absent')">缺勤</button-tab-item>
      <button-tab-item :selected="currentKey === 'leave'" @on-item-click="itemSwitch('leave')">请假</button-tab-item>
      <button-tab-item :selected="currentKey === 'late'" @on-item-click="itemSwitch('late')">迟到</button-tab-item>
      <button-tab-item :selected="currentKey === 'early'" @on-item-click="itemSwitch('early')">早退</button-tab-item>
    </button-tab>

    <div class="course-signin-student">
      <div class="course-signin-student__info">
        应到{{ studentData.memberCount }}人，已签到<span>{{ studentData.attendCount}}</span>人
      </div>

      <div class="course-signin-student-item weui-flex" v-for="(item, index) in studentData.members[currentKey]" @click="showSheet(item.id)">
        <div class="course-signin-student-item__number">{{ index + 1 }}</div>
        <img class="course-signin-student-item__avatar" :src="host + item.avatar">
        <div class="weui-flex__item course-signin-student-item__title">
          {{ item.nickname }}
        </div>
        <div class="course-signin-student__date">
          {{ $dateFormatFn(item.updatedTime) }}
        </div>
      </div>
    </div>

    <status-sheet></status-sheet>
  </main>
</template>

<script>
import { ButtonTab, ButtonTabItem } from 'vux';
import StatusSheet from './StatusSheet';
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    ButtonTab,
    ButtonTabItem,
    StatusSheet
  },
  data() {
    return {
      host: this.$getCookie('host'),
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      timeId: this.$route.params.timeId,
    }
  },
  created() {
    this.fetchData();
    this.fetchInterval = setInterval(() => {
      this.fetchData();
    }, 3000);
  },
  computed: {
    ...mapState({
      studentData: state => state.signIn.studentData,
      currentKey: state => state.signIn.currentKey,
      isShowSheet: state => state.signIn.isShowSheet,
    }),
  },
  beforeRouteLeave (to, from, next) {
    clearInterval(this.fetchInterval);
    next();
  },
  beforeRouteUpdate (to, from, next) {
    clearInterval(this.fetchInterval);
    next();
  },
  beforeDestroy() {
    clearInterval(this.fetchInterval);
  },
  methods: {
    ...mapActions([
      types.STUDENTS_INIT,
      types.SET_CURRENT_KEY,
      types.SET_IS_SHOW_SHEET,
      types.SET_CHECKED_ID]),
    itemSwitch(key) {
      this[types.SET_CURRENT_KEY](key);
    },
    showSheet(id) {
      this[types.SET_IS_SHOW_SHEET](true);
      this[types.SET_CHECKED_ID](id);
    },
    fetchData() {
      this[types.STUDENTS_INIT]({
        lessonId: this.lessonId,
        timeId: this.timeId })
      .catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/mixins';

.course-signin-tab {
  margin: 1.875rem 0.625rem;
}

.course-signin-student__info {
  margin: 0 1rem;
  span {
    color: #4993e9;
    margin: 0 0.3125rem;
  }
}

.course-signin-student-item {
  line-height: 1.875rem;
  padding: 1.25rem 0.9375rem 0.9375rem 0.9375rem;
  border-bottom: 0.0625rem solid #e5e5e5;
  .course-signin-student-item__avatar {
    width: 1.875rem;
    height: 1.875rem;
    margin-right: 0.625rem;
    border-radius: 50%;
  }
  .course-signin-student-item__title {
    font-size: 1.0625rem;
    color: #313131;
    margin-right: 1.25rem;
    line-height: 1.875rem;
    .text-overflow;
  }
  .course-signin-student-item__number {
    width: 1.25rem;
    color: #4c4c4c;
    margin-right: 0.625rem;
  }
}
</style>
