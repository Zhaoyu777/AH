<template>
  <div>
    <div class="activity-body"  v-if="raceAnswer.status || role === 'teacher'">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content" v-html="activityData.activityContent">
      </div>
    </div>

    <div class="not-teaching-tips activity-body" v-else-if="!raceAnswer.status && role === 'student'">
      <img :src="screenCover">
      <span class="tips-name">做好准备，马上开抢</span>
    </div>

    <div class="activity-action">
      <button class="weui-btn weui-btn_disabled" v-if="activityData.status === 'created'">暂未上课</button>
      <div v-else>
        <student-race></student-race>
      </div>
    </div>
    <race-result></race-result>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import api from '@/assets/js/api';
import * as types from '@/vuex/mutation-types';
import RaceResult from './RaceResult';
import StudentRace from './StudentRace';
import {screenCover} from '@/assets/js/data';

export default {
  components: {
    RaceResult,
    StudentRace
  },
  computed: {
    ...mapState({
      raceAnswer: state => state.activity.raceAnswer,
      activityData: state => state.activity.activityData,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      screenCover,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      taskId: this.$route.params.taskId,
      isAjaxEnd: false
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.RACE_ANSWER_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.RACE_ANSWER_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.RACE_ANSWER_INIT,
      types.RACE_ANSWER_CLEAR
    ]),
    fetchData() {
      this[types.RACE_ANSWER_INIT](this.taskId).then(() => {
        this.isAjaxEnd = true;
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    updateData(data) {
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.taskId = data.taskId;
      this.isAjaxEnd = false;
    }
  }
}

</script>

<style lang='less'>
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';
@import '~@/assets/less/module/activity-body.less';

.not-teaching-tips {
  font-size: 1.0625rem;
  text-align: center;
  background-color: #fff;
  height: calc(~"100% - 7rem");
  img {
    width: 100%;
    padding-bottom: 1.5625rem;
  }
  .tips-name {
    font-size: 24px;
    color: rgba(49, 49, 49, 0.87);
    font-weight: 500;
  }
}

</style>
