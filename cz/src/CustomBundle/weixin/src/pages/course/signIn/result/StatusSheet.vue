<template>
  <actionsheet v-model="isShowSheet" :menus="sheetMenus" :close-on-clicking-mask="false" show-cancel @on-click-menu-cancel="cancel" @on-click-menu="setSignInState"></actionsheet>
</template>

<script>
import { Actionsheet } from 'vux';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    Actionsheet
  },
  computed: {
    ...mapState({
      currentKey: state => state.signIn.currentKey,
      checkedId: state => state.signIn.checkedId,
      isShowSheet: state => state.signIn.isShowSheet,
    }),
    sheetMenus: function() {
      let sheetMenus = [{
        label: '设为出勤',
        value: 'attend'
      }, {
        label: '设为请假',
        value: 'leave'
      }, {
        label: '设为迟到',
        value: 'late'
      }, {
        label: '设为早退',
        value: 'early'
      }, {
        label: '设为缺勤',
        value: 'absent',
        type: 'warn'
      }]
      sheetMenus = sheetMenus.filter((item) => {
        return item.value !== this.currentKey
      })
      return sheetMenus;
    }
  },
  methods: {
    ...mapActions([types.SET_STUDENT_SIGNIN_STATUS, types.SET_IS_SHOW_SHEET]),
    setSignInState(key) {
      if (key === 'cancel') {
        return;
      }

      this[types.SET_STUDENT_SIGNIN_STATUS]({
        checkedId: this.checkedId,
        status: key,
      }).catch((res) => {
        this.$ajaxError();
      }).then((res) => {
        this[types.SET_IS_SHOW_SHEET](false);
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });    },
    cancel() {
      this[types.SET_IS_SHOW_SHEET](false);
    }
  }
}
</script>