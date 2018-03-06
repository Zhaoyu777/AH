<template>
  <div class="toolbar" @click.self="isShowMenu = false">
    <div class="toolbar__back" @click="barBack"><i class="cz-icon cz-icon-chevronleft"></i></div>
    <div class="toolbar__menu">
      <div class="toolbar__menu-title" @click.stop="toggleMenu"><i class="cz-icon cz-icon-menu"></i></div>
      <ul class="toolbar__menu-list" :class="{'show': isShowMenu}">
        <li><router-link :to="{name: 'courseList'}"><i class="cz-icon cz-icon-lesson"></i>课程</router-link></li>
        <li><router-link :to="{name: 'group'}"><i class="cz-icon cz-icon-quanzi"></i>学校圈</router-link></li>
        <li><router-link :to="{name: 'my'}"><i class="cz-icon cz-icon-person"></i>我的</router-link></li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isShowMenu: false
    }
  },
  watch: {
    '$route': function () {
      this.isShowMenu = false;
    }
  },
  methods: {
    barBack() {
      this.$emit('barBack');
    },
    toggleMenu() {
      this.isShowMenu = !this.isShowMenu;
    }
  }
}
</script>

<style lang="less">
.toolbar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 10px 10px;
  box-shadow: 0 0.125rem 0.375rem 0 #efefef;
  z-index: 1;
  font-size: 12px;
  color: #919191;
  .toolbar__back {
    float: left;
    i {
      font-size: 24px;
    }
  }
  .toolbar__menu {
    position: relative;
    float: right;
  }
  .toolbar__menu-title {
    i {
      font-size: 24px;
    }
  }
  .toolbar__menu-list {
    display: none;
    position: absolute;
    top: 40px;
    right: -5px;
    line-height: 24px;
    &.show {
      display: block;
    }
    li {
      list-style: none;
      background: #fff;
      box-shadow: 0px 2px 4px 0px rgba(0,0,0,0.05);
      border-radius: 4px;
      margin-bottom: 5px;
      width: 80px;
      padding: 8px 10px;
    }
    a {
      color: #919191;
    }
    i {
      font-size: 16px;
      margin-right: 5px;
    }
  }
}
</style>
