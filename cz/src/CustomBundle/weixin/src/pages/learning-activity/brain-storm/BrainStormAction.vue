<template>
  <div class="activity-action">
    <button class="weui-btn weui-btn_disabled" v-if="activityData.status === 'created'">暂未上课</button>
    <div v-else>
      <button class="weui-btn weui-btn_primary" title="Default popup" v-model="isShow" @click="popShow" v-if="brainStorm.hasGroup && brainStorm.status == 'start' && !brainStorm.isAnswer">开始回答</button>
      <button class="weui-btn weui-btn_default" v-else-if="brainStorm.isAnswer">已回答</button>
      <!-- 正在上课中 -->
      <!--<div v-else>-->
        <!--准备开始回答-->
      <!--</div>-->
      <div v-transfer-dom>
        <popup v-model="isShow" :hide-on-blur="false">
          <div class="answer-question">
            <div class="answer-question__canecl" @click="popHide">取消</div>
            回答问题
            <div class="answer-question__submit" @click="submit">提交</div>
          </div>
          <group>
            <x-textarea :max="50" :placeholder="'输入回复内容'" v-model="value"></x-textarea>
          </group>
        </popup>
      </div>
    </div>
  </div>
</template>

<script>
import { TransferDom, Group, Popup, XTextarea } from 'vux';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  props: ['taskId','courseId','lessonId'],
  directives: {
    TransferDom
  },
  components: {
    Group,
    Popup,
    XTextarea
  },
  data() {
    return {
      isShow: false,
      value: null
    }
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      brainStorm: state => state.activity.brainStorm,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    ...mapActions([
      types.BRAIN_STORM_START,
      types.BRAIN_STORM_END,
      types.BRAIN_STORM_SUBMIT
    ]),
    start() {
      this[types.BRAIN_STORM_START]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '开始回答成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    end() {
      const _this = this;
      this.$vux.confirm.show({
        title: '停止回答',
        content: '确定要停止回答吗？',
        onConfirm () {
          _this.activityEnd();
        }
      })
    },
    activityEnd() {
      this[types.BRAIN_STORM_END]({
        taskId: this.taskId,
        courseId: this.courseId,
        lessonId: this.lessonId
      }).then(() => {
        this.$vux.toast.show({
          text: '停止回答成功',
        })
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    popShow() {
      this.isShow = true;
    },
    popHide() {
      this.isShow = false;
    },
    submit() {
      this.isShow = false;
      this[types.BRAIN_STORM_SUBMIT]({
        taskId: this.taskId,
        content: this.value
      }).then((res) => {
        this.$vux.toast.show({
          text: '恭喜，回答完毕',
        });
        this.$set(this.brainStorm,'isAnswer',true);
        this.value = null;
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';

.answer-question {
  position: relative;
  padding-top: 18px;
  font-size: 1.125rem/* 18px */;
  color: #2B333B;
  text-align: center;
  .answer-question__canecl {
    position: absolute;
    left: .625rem;
    top: 1.125rem;
    font-size: 1rem;
    color: #BDC2C6;
  }
  .answer-question__submit {
    position: absolute;
    right: .625rem;
    top: 1.125rem/* 18px */;
    font-size: 1rem;
    color: @brand-primary;
  }
}
.photo-action {
  margin: 0.9375rem;
}
</style>
