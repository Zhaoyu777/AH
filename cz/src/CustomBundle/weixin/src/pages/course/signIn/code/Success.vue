<template>
  <div class="signin-success__header" v-if="signInStatus == 'attend'">
    <i class="cz-icon cz-icon-iccheckcircleblack24px signin-success__icon"></i>
    <div class="signin-success__title">已签到成功</div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  computed: {
    ...mapState({
      signInStatus: state => state.signIn.signInStatus
    })
  }
}
</script>