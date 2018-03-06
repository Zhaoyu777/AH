<template>
  <div class="app has-bar">
    <tool-bar @barBack="handleBack"></tool-bar>
    <router-view></router-view>
    <code-menu :type="menuType"></code-menu>
  </div>
</template>

<script>
import ToolBar from '@/components/Toolbar';
import CodeMenu from '@/components/CodeMenu';
import { mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    ToolBar,
    CodeMenu,
  },
  created() {
    this[types.DEFAULT_MENU_INIT]();
  },
  data() {
    return {
      menuType: 'default'
    }
  },
  methods: {
    ...mapActions([
      types.DEFAULT_MENU_INIT
    ]),
    handleBack() {
      this.$router.push({name: 'my'});
    }
  }
}
</script>
