<template>
  <div class="activity-action">
    <button class="weui-btn weui-btn_disabled"
      v-if="activityData.status === 'created'">
      暂未上课
    </button>
    <div v-if="role == 'teacher'">
      <button class="weui-btn weui-btn_primary"
        v-if="activityData.status !== 'created' && !oneSentence.status"
        @click="start">
        开始回答
      </button>
      <button class="weui-btn weui-btn_warning"
        v-else-if="oneSentence.status === 'start'"
        @click="end">
        停止回答
      </button>
      <button class="weui-btn weui-btn_default"
        v-else-if="oneSentence.status === 'end'">
        已停止回答
      </button>
    </div>
    <div v-else-if="role =='student'">
      <button class="weui-btn weui-btn_primary"
        title="一句话问答"
        v-model="isShow"
        v-if="activityData.status !== 'created' && oneSentence.status == 'start' && !oneSentence.isAnswer"
        @click="popShow">
        开始回答
      </button>
      <button class="weui-btn weui-btn_default"
        v-else-if="oneSentence.isAnswer">
        已回答
      </button>
      <div v-transfer-dom>
        <popup v-model="isShow">
          <div class="answer-question">
            <div class="answer-question__canecl" @click="popHide">取消</div>
            回答问题
            <div class="answer-question__submit" @click="submit">提交</div>
          </div>
          <group>
            <x-textarea
              :max="50"
              :placeholder="'输入回复内容'"
              v-model="value">
            </x-textarea>
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
  props: ['courseId','lessonId','taskId'],
  directives: {
    TransferDom
  },
  components: {
    Group,
    Popup,
    XTextarea,
  },
  data() {
    return {
      isShow: false,
      value: null,
    }
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      oneSentence: state => state.activity.oneSentence,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    ...mapActions([
      types.ONE_SENTENCE_START,
      types.ONE_SENTENCE_END,
      types.ONE_SENTENCE_SUBMIT
    ]),
    start() {
      this[types.ONE_SENTENCE_START]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
        activityId: this.activityData.activityId
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
      this[types.ONE_SENTENCE_END]({
        taskId: this.taskId,
        activityId: this.activityData.activityId
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
      this[types.ONE_SENTENCE_SUBMIT]({
        taskId: this.taskId,
        content: this.value
      }).then((res) => {
        this.value = null;
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }
        if(res.data.score == 0) {
          this.$vux.toast.show({
            text: '恭喜，回答完毕',
          })
        } else {
          this.$vux.toast.show({
            text: `恭喜，回答完毕获得${res.data.score}分`,
          })
        }
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
