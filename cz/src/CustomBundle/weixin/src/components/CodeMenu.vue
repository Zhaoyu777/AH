<template>
  <tabbar>
    <tabbar-item :selected="item.url == $route.path" @on-item-click="action(item.type, item.url)"
      v-for="item,index in activityMenu" :key="index" v-if="type === 'activity'">
      <i slot="icon" :class="item.iconClass"></i>
      <span slot="label">{{item.labelTitle}}</span>
    </tabbar-item>
    <tabbar-item :selected="item.url == $route.path" @on-item-click="action(item.type, item.url)"
      v-for="item,index in courseMenu" :key="index" v-if="type === 'course'">
      <i slot="icon" :class="item.iconClass"></i>
      <span slot="label">{{item.labelTitle}}</span>
    </tabbar-item>
    <tabbar-item :selected="item.url == $route.path" @on-item-click="action(item.type, item.url)"
      v-for="item,index in defaultMenu" :key="index" v-if="type === 'default'">
      <i slot="icon" :class="item.iconClass"></i>
      <span slot="label">{{item.labelTitle}}</span>
    </tabbar-item>
  </tabbar>
</template>

<script>
import { Tabbar, TabbarItem, XImg } from 'vux';
import api from '@/assets/js/api';
import { mapState } from 'vuex';

export default {
  props: ['type'],
  data() {
    return {
      menuData: {},
    }
  },
  components: {
    Tabbar,
    TabbarItem
  },
  computed: {
    ...mapState({
      defaultMenu: state => state.menu.defaultMenu,
      courseMenu: state => state.menu.courseMenu,
      activityMenu: state => state.menu.activityMenu,
    })
  },
  methods: {
    action(type, url) {
      if (type === 'link') {
        this.$router.push(url);
      } else if (type === 'lessonEnd') {
        const _this = this;
        this.$vux.confirm.show({
          title: '下课',
          content: '确定要下课吗？',
          onConfirm () {
            _this.$emit('courseEnd');
          }
        })
      }
    }
  }
}
</script>
