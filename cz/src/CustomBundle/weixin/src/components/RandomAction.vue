<template>
  <div class="activity-action">
    <button class="weui-btn weui-btn_primary" title="选择分组" v-model="isShow" @click="popShow">选择分组</button>
    <div v-transfer-dom>
      <actionsheet
        v-model="isShow"
        :menus="groups"
        show-cancel
        @on-click-menu="click">
      </actionsheet>
    </div>
  </div>
</template>

<script>
import { TransferDom, Actionsheet } from 'vux';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  directives: {
    TransferDom
  },
  components: {
    Actionsheet
  },
  props: ['groups'],
  data() {
    return {
      isShow: false,
    }
  },
  methods: {
    popShow() {
      this.isShow = true;
    },
    click (key) {
      if (key == 'cancel') {
        return;
      }
      this.$emit('randomJoin',{
        taskId: this.$route.params.taskId,
        groupId: key
      });
    }
  }
}
</script>

<style>
  .vux-actionsheet-menu-default {
    line-height: 1.6;
  }
</style>
