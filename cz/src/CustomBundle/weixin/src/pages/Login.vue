<template>
  <div class="login-from">
    <group class="login-from__input">
      <x-input title='用户ID' v-model="userId" required show-clear :debounce="500"></x-input>
    </group>
    <x-button type="primary" @click.native="login()">登录</x-button>
  </div>
</template>
<script>
import { Group, XInput, XButton } from 'vux';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  name: 'Login',
  components: {
    Group,
    XInput,
    XButton,
  },
  data() {
    return {
      userId: null
    }
  },
  methods: {
    ...mapActions([types.USER_LOGIN]),
    login() {
      const userId = this.userId;
      if (userId) {
        this[types.USER_LOGIN](this.userId).then(res => {
          this.$setCookie('role', res.data.role);
          this.$setCookie('host', res.data.host);
          this.$setCookie('userId', userId);
          this.$setCookie('XSRF-TOKEN', res.data.csrf_token);
          this.$router.replace({name: 'courseList'});

        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });

      } else {
        this.$vux.toast.show({
          text: '请输入用户ID',
          time: '800',
          type: 'warn'
        })
      }
    }
  }
}
</script>

<style lang="less">
.login-from {
  margin: 3.125rem 0.9375rem;
}
.login-from__input {
  margin-bottom: 1.25rem;
}
</style>
