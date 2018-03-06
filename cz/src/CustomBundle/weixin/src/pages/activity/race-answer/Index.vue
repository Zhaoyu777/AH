<template>
  <div>
    <div class="activity-body"  v-if="raceAnswer.status || role == 'teacher'">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content" v-html="activityData.activityContent">
      </div>
    </div>
    <div class="activity-action">
      <button class="weui-btn weui-btn_disabled" v-if="activityData.status == 'created'">暂未上课</button>
      <div v-else>
        <race-action v-if="isAjaxEnd && role == 'teacher'" :courseId="courseId"
                     :lessonId="lessonId" :taskId="taskId"></race-action>
        <student-race v-else-if="role == 'student'"></student-race>
      </div>
    </div>
    <race-result></race-result>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import api from '@/assets/js/api';
import * as types from '@/vuex/mutation-types';
import RaceAction from './RaceAction';
import RaceResult from './RaceResult';
import StudentRace from './StudentRace';

export default {
  components: {
    RaceAction,
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

</style>