<template>
  <div class="activity-body">
    <div class="activity-body__title">
      问题
    </div>
    <div class="activity-body__content">
      {{ activityData.activityContent }}
    </div>
    <!-- 正在学习中 -->
    <div>
      <div class="activity-body__primary" v-if="!rollcall.isRollcall">老师点名中...</div>
      <div class="activity-body__warning" v-else-if="rollcall.isRollcall">你已被随机抽中，请回答老师问题</div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      rollcall: state => state.activity.rollcall,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      host: this.$getCookie('host'),
      taskId: this.$route.params.taskId,
      courseId: this.$route.params.courseId,
      isDisabled: false,
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave(to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.ROLLCALL_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.ROLLCALL_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.ROLLCALL_CLEAR,
      types.ROLLCALL_STUDENT,
      types.ROLLCALL_STATUS,
      types.ROLLCALL_STATUS_OFF,
      types.ROLLCALL_RAND]),
    teacherFetchData() {
      this[types.ROLLCALL_STUDENT](this.taskId);
    },
    fetchData() {
      this.$isLoading();
      this[types.ROLLCALL_STATUS](this.taskId)
        .then((res) => {
          this.$endLoading();
        })
        .catch((response) => {
          this.$endLoading();
          this.$ajaxMessage(response.data.message);
        });
    },
    rand() {
      this.isDisabled = true;
      this[types.ROLLCALL_RAND]({
        taskId: this.taskId,
        courseId: this.courseId
      })
//        .then((res) => {
//        console.log(res);
//        if (res.data.message) {
//          this.$ajaxMessage(res.data.message);
//          return;
//        }
//        setTimeout(() => {
//          this.$vux.toast.show({
//            text: '点名成功',
//          });
//          this.isDisabled = false;
//        }, 3000);
//      }).catch((response) => {
//        this.isDisabled = false;
//        this.$ajaxMessage(response.response.data.message);
//      });
    },
    updateData(data) {
      this.courseId = data.courseId;
      this.taskId = data.taskId;
      this.isDisabled = false;
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/activity-body.less';

.rollcall-student-list {
  margin: 0.9375rem 0.625rem;
  background: #fff;
  border-radius: 0.625rem;
}

.rollcall-student-item {
  padding: 1.25rem 1.125rem;
  line-height: 1.875rem;
  + .rollcall-student-item {
    border-top: 0.0625rem solid #f0f0f0;
  }
  .rollcall-student__avatar {
    width: 1.875rem;
    height: 1.875rem;
    border-radius: 50%;
    margin-right: 0.625rem;
  }
  .rollcall-student__name {
    font-size: 1.0625rem;
    color: #313131;
  }
  .rollcall-student__assist {
    text-align: right;
  }
  .rollcall-student__review {
    margin-top: 0.25rem;
  }
  .rollcall-student__scores {
    color: #4993e9;
    font-size: 0.875rem;
  }
}

</style>
