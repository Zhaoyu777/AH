<template>
  <div id="app" class="app-wrap">
    <loading :value.sync="isLoading"></loading>
    <transition name="slide-fade">
      <keep-alive>
        <router-view v-if="$route.meta.keepAlive">
        </router-view>
      </keep-alive>
    </transition>

    <transition name="slide-fade">
      <router-view v-if="!$route.meta.keepAlive">
      </router-view>
    </transition>
  </div>
</template>

<script>
import { Loading } from 'vux';
import { mapState } from 'vuex';

export default {
  name: 'app',
  components: {
    Loading,
  },
  computed: {
    ...mapState({
      isLoading: state => state.isLoading,
    })
  },
};
</script>

<style lang="less">
@import '~vux/src/styles/reset.less';
@import '~vux/src/styles/weui/weui.less';
@import '~@/assets/less/common.less';

html,
body {
  height: 100%;
  width: 100%;
  overflow: hidden;
}

html {
  font-size: 16px;
}

body {
  font-family: "PingFang SC","Hiragino Sans GB","Microsoft YaHei","微软雅黑",Arial,sans-serif;
  background-color: #f3f3f7;
  font-size: 62.5%;
  color: #616161;
  line-height: 1;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  -ms-text-size-adjust: 100%;
  -webkit-text-size-adjust: 100%;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

.app-wrap {
  height: 100%;
  overflow: hidden;
}

.app-show {
  opacity: 1;
}

.app-hidden {
  opacity: 0;
}

.app {
  position: relative;
  height: calc(~"100% - 3.25rem");
  padding-bottom: 3.25rem;
  overflow: hidden;
  &.has-bar {
    height: calc(~"100% - 6rem");
    padding-top: 2.75rem;
  }
}

.app-main {
  position: relative;
  overflow-y: auto;
  overflow-x: hidden;
  height: 100%;
  -webkit-overflow-scrolling:touch;
}

.slide-fade-enter-active {
  transition: all .3s ease;
}
.slide-fade-leave-active {
  transition: all .3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}
.slide-fade-enter, .slide-fade-leave-active {
  transform: translateX(-300px);
  opacity: 0;
}
</style>
