<template>

  <div v-if="role == 'teacher'">
    <div class="activity-body">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content">
        {{ activityData.activityContent }}
      </div>
    </div>
    <div class="activity-action">
      <button class="weui-btn weui-btn_disabled" v-if="activityData.status != 'teaching'">暂未上课</button>
      <div v-else>
        <button class="weui-btn weui-btn_default" v-if="!rollcall.canRand">
          所有人已被点过
        </button>
        <button class="weui-btn weui-btn_default" v-if="rollcall.canRand && rollcall.status">
          点名中...
        </button>
        <button class="weui-btn weui-btn_primary" @click="rand" v-if="rollcall.canRand && !rollcall.status">
          随机点名
        </button>
      </div>
    </div>
    <student-list v-if="rollcall.stuData"></student-list>
  </div>

  <div v-else-if="role == 'student'">
    <div class="activity-body">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content">
        {{ activityData.activityContent }}
      </div>
      <div class="activity-body__warning" v-if="rollcall.isRollcall">你已被随机抽中，请回答老师问题</div>
    </div>
  </div>
</template>

<script>
import { XDialog } from 'vux';
import StudentList from './List';

import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    StudentList
  },
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
     //if (this.role === 'teacher') {
      this.teacherFetchData();
     //} else {
      this.fetchData();
     //}
  },
  beforeRouteLeave(to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.ROLLCALL_CLEAR]();
    this.updateData(to.params);
     if (this.role === 'teacher') {
      this.teacherFetchData();
     } else {
      this.fetchData();
     }
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
      this[types.ROLLCALL_STATUS](this.taskId)
        .catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    rand() {
      this.rollcall.status = true;
      this[types.ROLLCALL_RAND]({
        taskId: this.taskId,
        courseId: this.courseId
      }).catch((error) => {
        this.rollcall.status = false;
      });
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
