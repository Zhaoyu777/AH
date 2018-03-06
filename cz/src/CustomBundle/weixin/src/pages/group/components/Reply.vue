<template>
  <div v-transfer-dom>
    <popup v-model="isShow"
        :hide-on-blur="false">
      <div class="answer-question">
        <div class="answer-question__canecl" @click="popHide">取消</div>
        回复话题
        <div class="answer-question__submit" @click="submitReply">提交</div>
      </div>
      <group>
        <x-textarea
          :max="50"
          :placeholder="'输入回复内容'"
          v-model="value"
        ></x-textarea>
      </group>
    </popup>
  </div>
</template>

<script>
import { TransferDom, Group, Popup, XTextarea } from 'vux';
import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  directives: {
    TransferDom
  },
  components: {
    Popup,
    Group,
    XTextarea,
  },
  data() {
    return {
      value: null,
      groupId: this.$route.params.groupId,
      threadId: this.$route.params.threadId,
    }
  },
  computed: {
    ...mapState({
      isShow: state => state.group.isShow,
      postId: state => state.group.postId,
      fromUserId: state => state.group.fromUserId,
    })
  },
  methods: {
    ...mapMutations([
      types.GROUP_REPLY_ISSHOW
    ]),
    ...mapActions([
      types.GROUP_REPLY_CONTENT
    ]),
    popHide() {
      this[types.GROUP_REPLY_ISSHOW](false);
    },
    submitReply() {
      this[types.GROUP_REPLY_ISSHOW](false);
      this[types.GROUP_REPLY_CONTENT]({
        groupId: this.groupId,
        threadId: this.threadId,
        content: this.value,
        postId: this.postId,
        fromUserId: this.fromUserId,
      }).then((res) => {
        if (res.message) {
          this.$ajaxMessage(res.message);
          return;
        }
        this.$vux.toast.show({
          text: '回复成功',
        })
        this.value = null;
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
</style>