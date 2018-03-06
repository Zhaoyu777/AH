<template>
  <div>
    <div v-if="role == 'student' && activityData.status != 'created' && testpaper.result">
      <testpaper v-if="testpaper.result.status == 'doing' || analysis">
      </testpaper>

      <result v-if="testpaper.result.status != 'doing' && !analysis">
      </result>
    </div>
    <div class="activity-action" v-else-if="!testpaper.result">
      <button class="weui-btn weui-btn_disabled" v-if="activityData.status == 'created'">
        暂未上课
      </button>
      <div v-else-if="role == 'student'">
        <button class="weui-btn weui-btn_disabled" v-if="!testpaper.status">
          测验未开始
        </button>
        <button class="weui-btn weui-btn_primary" @click="start" v-else-if="testpaper.status == 'start'">
          开始测验
        </button>
        <button class="weui-btn weui-btn_disabled" v-else-if="testpaper.status != 'start'">测验结束</button>
      </div>
      <div v-else-if="role == 'teacher'">
        <button class="weui-btn weui-btn_primary" @click="startTask" v-if="!testpaper.status">
          开始测验
        </button>
        <button class="weui-btn weui-btn_primary" @click="endTask" v-else-if="testpaper.status == 'start'">
          结束测验
        </button>
        <button class="weui-btn weui-btn_disabled" v-else-if="testpaper.status != 'start'">
          测验已结束
        </button>
        <button class="weui-btn weui-btn_primary" @click="showResult()" v-if="!analysis">
          查看结果
        </button>

        <testpaper v-else>
        </testpaper>
      </div>
    </div>
  </div>
</template>

<script>
import { XDialog } from 'vux';

import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import Testpaper from './Testpaper';
import Result from './Result';

export default {
  components: {
    Testpaper,
    Result
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      testpaper: state => state.activity.testpaper,
      analysis: state => state.activity.testpaper.analysis,
      role: state => state.activity.activityData.role
    })
  },
  data() {
    return {
      host: this.$getCookie('host'),
      taskId: this.$route.params.taskId,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId
    }
  },
  created() {
    this.getResult();
  },
  beforeRouteLeave (to, from, next) {
    this[types.TESTPAPER_CLEAR]();
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.TESTPAPER_CLEAR]();
    this.updateData(to.params);
    this.getResult();
    next();
  },
  beforeDestroy() {
    this[types.TESTPAPER_CLEAR]();
  },
  methods: {
    ...mapActions([
      types.TESTPAPER_START,
      types.TESTPAPER_RESULT,
      types.TESTPAPER_START_TASK,
      types.TESTPAPER_STATIS,
      types.TESTPAPER_CLEAR,
      types.TESTPAPER_END_TASK
    ]),
    getResult() {
      this[types.TESTPAPER_RESULT]({taskId:this.taskId}).then((res) => {

      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    start() {
      this[types.TESTPAPER_START]({
        taskId:this.taskId
      }).then((res) => {
        if (res.result) {
          this.$vux.toast.show({
            type: 'text',
            text: `开始测验`,
            width: '80%'
          })
        }
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    updateData(data) {
      this.taskId = data.taskId;
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
    },
    showResult() {
      this[types.TESTPAPER_STATIS]({taskId:this.taskId}).then((res) => {

      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    updateData(params) {
      this.taskId = params.taskId;
    },
    startTask() {
      this[types.TESTPAPER_START_TASK]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '开始测验',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    endTask() {
      const _this = this;
      this.$vux.confirm.show({
        title: '停止测验',
        content: '确定要结束测验吗？',
        onConfirm () {
          _this.activityEnd();
        }
      })
    },
    activityEnd() {
      this[types.TESTPAPER_END_TASK]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '结束测验',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/activity-body.less';
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';

.question-naire__title {
  font-size: 1.1875rem;
  color: #333;
}
.question-naire__num {
  color: @brand-primary;
}
.question-naire__type {
  font-size: .75rem;
  color: #c1c1c1;
}
.question-naire__option {
  display: block;
  padding: .9375rem;
  color: #414141;
  font-size: .875rem;
  margin-bottom: .625rem;
  border-radius: .625rem;
  background-color: #f9f9f9;
}
.activity-content .question-naire__selected {
  background-color: @brand-primary;
  color: #fff;
  .question-naire__num {
    color: #fff;
  }
}
.question-naire__data {
  padding: .3125rem .625rem .9375rem .625rem;
  .question-naire__num {
    padding-left: .625rem;
    color: #919191;
  }
}
.question-naire__btns {
  display: inline-block;
  width: 1.875rem;
  height: 1.875rem;
  line-height: 1.875rem;
  text-align: center;
  color: @brand-primary;
  background-color: #f9f9f9;
  border-radius: .625rem;
  margin: .1875rem;
  &.active {
    background-color: @brand-primary;
    color: #fff;
  }
}

.activity-info {
  padding: .1875rem 1.875rem;
  color: #919191;
}

.vux-checker-box {
  margin-top: .875rem;
}
.vux-checker-item {
  display: block !important;
}
.weui-cells:after {
  border-bottom: 0px !important;
}
.weui-cells:before {
  border-top: 0px !important;
}
.weui-cell {
  font-size: .875rem !important;
  background-color: #f9f9f9 !important;
  border-radius: .625rem !important;
}
.testpaper-right__answer {
  background-color: #FD4852;
  color: #fff;
}
.testpaper-analysis__container {
  margin-top: .625rem;
  margin-bottom: .625rem;
  padding: .46875rem .875rem .59375rem .90625rem;
  border-radius: .3125rem;
  background: #f9f9f9;
  .testpaper-analysis__answer {
    font-family: PingFangSC-Regular;
    font-size: .875rem;
    color: #616161;
  }
  .testpaper-analysis__analysis {
    margin-top: .9375rem;
    font-family: PingFangSC-Regular;
    font-size: .875rem;
    color: #919191;
  }
}
.mrl {
  margin-right: .625rem
}
.mtl {
  margin-top: .625rem
}
</style>
