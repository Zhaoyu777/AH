<template>
  <div>
    <div class="activity-body">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content" v-html="activityData.activityContent">
      </div>
    </div>
    <sentence-action v-if="isAjaxEnd" :courseId="courseId" :lessonId="lessonId" :taskId="taskId"></sentence-action>
    <student-show v-if="oneSentence.resultId"></student-show>
  </div>
</template>

<script>
import { XDialog } from 'vux';
import { mapState, mapActions } from 'vuex';
import api from '@/assets/js/api';
import * as types from '@/vuex/mutation-types';
import StudentShow from './StudentShow';
import SentenceAction from './SentenceAction';

import { getToken } from '@/assets/js/socket';

export default {
  components: {
    StudentShow,
    SentenceAction
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      oneSentence: state => state.activity.oneSentence,
      role: state => state.activity.activityData.role,
    })
  },
  data() {
    return {
      isAjaxEnd: false,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId,
      taskId: this.$route.params.taskId
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.ONE_SENTENCE_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.ONE_SENTENCE_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.ONE_SENTENCE_INIT,
      types.ONE_SENTENCE_CLEAR
    ]),
    fetchData() {
      this[types.ONE_SENTENCE_INIT](this.taskId).then((res) => {
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
@import '~@/assets/less/module/activity-body.less';
@import '~@/assets/less/module/wall-group.less';
</style>
