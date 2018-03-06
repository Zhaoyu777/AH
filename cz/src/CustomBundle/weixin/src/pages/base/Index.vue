<template>
  <div class="app">
    <router-view></router-view>
    <code-menu :type="menuType"></code-menu>
  </div>
</template>

<script>
import CodeMenu from '@/components/CodeMenu';
import { mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    CodeMenu,
  },
  created() {
    this[types.DEFAULT_MENU_INIT]();
  },
  data() {
    return {
      menuType: 'default',
    }
  },
  methods: {
    ...mapActions([
      types.DEFAULT_MENU_INIT
    ]),
  }
}
</script>
