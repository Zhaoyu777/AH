<!-- 好像可删？ -->
<template>
  <div class="group-panel">
    <div class="weui-panel weui-panel_access">
      <div class="weui-panel__hd">
        <i class="cz-icon cz-icon-textsms mrs"></i>
        最近话题
      </div>
      <thread :threadData="threadData" :groupId="groupId"></thread>
    </div>
  </div>
</template>

<script>
import Thread from './components/Thread';

export default {
  components: {
    Thread
  },
  props: ['threadData', 'groupId'],
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