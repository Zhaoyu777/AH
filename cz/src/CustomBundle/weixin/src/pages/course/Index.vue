<template>
  <div class="app has-bar">
    <tool-bar @barBack="handleBack"></tool-bar>
    <router-view></router-view>
    <!-- <code-menu :type="menuType" v-if="role"></code-menu> -->
    <code-menu :type="menuType"></code-menu>
  </div>
</template>

<script>
import ToolBar from '@/components/Toolbar';
import CodeMenu from '@/components/CodeMenu';
import { courseMenuData } from '@/assets/js/data';

import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  components: {
    ToolBar,
    CodeMenu
  },
  data() {
    return {
      courseId: this.$route.params.courseId,
      menuType: 'course'
    }
  },
  computed: {
    ...mapState({
      role(state) {
        return state.course.info[this.courseId].role
     }
    }),
  },
  created() {
    this.fetchData();
  },
  methods: {
    ...mapActions([types.COURSE_MENU_INIT,types.COURSE_INFO]),
    handleBack() {
      // this.$router.go(-1);
      this.$router.push({name: 'courseList'});
    },
    fetchData() {
      this[types.COURSE_INFO]({
        courseId: this.courseId,
      }).then((res) => {
        this[types.COURSE_MENU_INIT]({
          courseId: this.courseId,
          role: this.role
        });
      }).catch((response) => {
        // this.$ajaxMessage(response.data.message);
      });
    }
  }
}
</script>
