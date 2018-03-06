<template>
  <div>
    <!-- 正在上课中 -->
    <!--<button class="weui-btn weui-btn_default"-->
            <!--v-if="raceAnswer.status !== 'start' && raceAnswer.status !== 'end'" disabled>-->
      <!--准备抢答...-->
    <!--</button>-->
    <div class="student-race-btn" v-if="raceAnswer.status == 'start' && isRaced == false">
      <div v-if="raceAnswer.time > 0">
        <div class="student-race_count">{{ raceAnswer.time }}</div>
      </div>
      <div v-else-if="raceAnswer.time != true">
        <div class="student-race_info" @click="race">开始<br/>抢答</div>
      </div>
    </div>
    <x-dialog v-model="showHideOnBlur" hide-on-blur :dialog-style="{'background-color': 'transparent'}">
      <div class="student-race-result">
        <img class="student-race-result_success" :src="raceCover" alt="" v-if="raceAnswer.isRaced">
        <div class="student-race-result_fail" v-else>
          <span>很遗憾<br/>没有抢到</span>
        </div>
      </div>
    </x-dialog>
  </div>
</template>

<script>
import { XDialog } from 'vux';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import { raceCover } from '@/assets/js/data';

export default {
  components: {
    XDialog
  },
  data() {
    return {
      showHideOnBlur: false,
      courseId: this.$route.params.courseId,
      taskId: this.$route.params.taskId,
      activityId: this.$route.params.activityId,
      raceCover,
      isRaced : false
    }
  },
  created() {
    this.fetchData();
  },
  computed: {
    ...mapState({
      raceAnswer: state => state.activity.raceAnswer,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    ...mapActions([
      types.RACE_ANSWER_RESULT
    ]),
    race() {
      this[types.RACE_ANSWER_RESULT]({
        courseId: this.courseId,
        taskId: this.taskId,
        activityId: this.activityId
      }).then(() => {
        this.showHideOnBlur = true;
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
      this.isRaced = true;
    },
    fetchData() {
      const countDown = setInterval(() => {
        this.raceAnswer.time = (this.raceAnswer.time - 0.1).toFixed(1);
        this.raceAnswer.time <= 0 && clearInterval(countDown);
      }, 100);
    },
  }
}
</script>

<style lang="less" scoped>
.student-race-btn {
  position: fixed;
  z-index: 5000;
  right: .625rem;
  bottom: 6rem;
  width: 80px;
  height: 80px;
  text-align: center;
  border-radius: 50%;
  background-color: #fd4852;
  box-shadow: 0px 10px 20px 0px #e0535b;
  display: flex;
  vertical-align: middle;
  align-items: center;
  justify-content: center;
  .student-race_info {
    font-size: 1rem;
    color: #fff;
    padding: 20px 0;
    line-height: 20px;
  }
  .student-race_count {
    font-size: 1.875rem;
    color: #fff;
    padding: 20px 0;
    line-height: 20px;
  }
}
.student-race-result_fail {
  width: 100px;
  height: 100px;
  text-align: center;
  border-radius: 50%;
  background: #ccc;
  box-shadow: 0px 10px 20px 0px #646161;
  margin: 0 auto;
  span {
    display: inline-block;
    padding: 30px 0;
    line-height: 20px;
    font-size: 1rem;
    color: #fff;
  }
}
.student-race-result_success {
  width: 12.5rem;
}
</style>
