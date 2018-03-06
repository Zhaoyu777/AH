<template>
  <div class="group-panel">
    <div class="weui-panel weui-panel_access">
      <div class="weui-panel__hd">
        <i class="cz-icon cz-icon-remen"></i>
        热门小组
        <i class="cz-icon cz-icon-othermore pull-right gray" @click="allGroupClick"></i>
      </div>
      <group :type="type"></group>
    </div>
  </div>
</template>

<script>
import Group from './components/Group';

export default {
  components: {
    Group
  },
  data() {
    return {
      type: 'hot',
    }
  },
  methods: {
    allGroupClick() {
      this.$router.push({ name: 'allGroup' });
    }
  }
}
</script>

<style lang="less">
  .group-panel {
  margin: 10px;
  .weui-panel {
    border-radius: .3125rem;
  }
  .weui-panel__hd {
    font-size: .875rem;
    padding: .9375rem;
    color: #414141;
  }
}
</style>