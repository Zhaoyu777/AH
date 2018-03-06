<template>
  <div>
    <div class="activity-body" v-if="activity.activityContent">
      <div class="activity-body__title">说明</div>
      <div class="instruction activity-body__content" v-html="activity.activityContent"></div>
    </div>
    
    <div v-if="role == 'student' && practiceWorkData.fileType">
      <div v-if="practiceWork.status == 'start' || activity.stage != 'in'">
        <div v-if="practiceWorkData.fileType == 'jpg/png'">
          <photo-upload v-if="result.status != 'reviewing' && result.status != 'finished'"></photo-upload>
        </div>
        <div v-else class="activity-header">
          <div class="content content-word">请通过电脑上传{{practiceWorkData.fileType}}文件作业</div>
        </div>
      </div>
    </div>
    <div class="activity-header" v-else-if="role == 'teacher'">
      <div class="content content-word">请通过电脑批阅</div>
    </div>

    <div class="activity-header" v-if="url">
      <div>
        <div class="my-picture-word">
          我的上传
        </div>
        <div class="text-center">
          <img :src="url" style="max-width:100%" />
        </div>
      </div>
      <div class="text-size" v-if="result.status == 'reviewing'">作业批阅中，请稍后查看批阅结果</div>
      <div v-else-if="result.status == 'finished'">
        <div class="finished text-size">批阅完成</div>
        <div class="text-size">评价：{{ result.appraisal == 1 ? '优秀' : result.appraisal == 2 ? '良好' : result.appraisal == 3 ? '中等' : result.appraisal == 4 ? '合格' : '不合格' }}</div>
        <div class="text-size">评语：{{ result.comment }}</div>
      </div>
    </div>
  </div>
</template>
<script>
  import { mapState, mapActions } from 'vuex';
  import PhotoUpload from './PhotoUpload';
  import * as types from '@/vuex/mutation-types';
  export default {
    data() {
      return {
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
      next();
      this[types.PRACTICE_WORK_CLEAR]();
      this.fetchData();
    },
    components: {
      PhotoUpload
    },
    beforeDestroy() {
      this[types.PRACTICE_WORK_CLEAR]();
    },
    computed: {
      ...mapState({
        url: state => state.activity.practiceWork.pictureUrl,
        activity: state => state.activity.activityData,
        role: state => state.activity.activityData.role,
        practiceWork: state => state.activity.practiceWork,
        practiceWorkData: state => state.activity.practiceWork.data,
        result: state => state.activity.practiceWork.result,
      })
    },
    methods: {
      ...mapActions([types.PRACTICE_WORK_INIT, types.PRACTICE_WORK_CLEAR]),
      fetchData() {
        this[types.PRACTICE_WORK_INIT](this.$route.params.taskId).then(() => {
          this.isAjaxEnd = true;
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
      },
      updateData(to) {
        this.taskId = to.params.taskId;
      }
    }
  }
</script>
<style lang="less">
.content {
  padding-top: 15px;
}
.instruction img {
  max-width: 100%;
}
.content-word {
  font-size: 1.1875rem;
  text-align: center;
  line-height: 2;
  margin-bottom: 0.9375rem;
  word-break: break-all;
}
.my-picture-word{
  font-size: 1.1875rem;
  text-align: center;
  color: #4993e9;
  margin-bottom: 10px;
}
</style>