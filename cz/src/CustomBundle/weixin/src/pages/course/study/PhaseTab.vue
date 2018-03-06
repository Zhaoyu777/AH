<template>
  <tab :line-width="3" custom-bar-width="2rem">
    <tab-item :selected="tabIndex === 0"  @on-item-click="tabsSwitch(0)">课前</tab-item>
    <tab-item :selected="tabIndex === 1"  @on-item-click="tabsSwitch(1)">课堂</tab-item>
    <tab-item :selected="tabIndex === 2" @on-item-click="tabsSwitch(2)">课后</tab-item>
  </tab>
</template>

<script>
import { Tab, TabItem } from 'vux';
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    Tab,
    TabItem
  },
  computed: {
    ...mapState({
      tabIndex: state => state.study.tabIndex
    })
  },
  methods: {
    ...mapActions([types.STUDY_TAB_SWITCH]),
    tabsSwitch(tabIndex) {
      this[types.STUDY_TAB_SWITCH](tabIndex);
    }
  }
}
</script>

<style scoped>
.vux-tab {
  margin-bottom: 1.25rem;
}
</style>
