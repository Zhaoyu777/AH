<template>
  <div class="app has-bar">
    <tool-bar @barBack="handleBack"></tool-bar>
    <main class="app-main" v-if="activityData">
      <div class="activity-header">
        <div class="activity-header__lesson-title">
          课次{{ activityData.lessonNumber }} {{ activityData.lessonTitle }}
        </div>
        <div class="activity-header__title">
          {{ activityData.activityNumber }}.{{ activityData.activityTitle }}
        </div>
      </div>
      <router-view :courseId="courseId" :taskId="taskId"></router-view>
    </main>
    <code-menu :type="menuType" @courseEnd="handleCourseEnd"></code-menu>
  </div>
</template>

<script>
import ToolBar from '@/components/Toolbar';
import CodeMenu from '@/components/CodeMenu';
import { activityMenuData } from '@/assets/js/data';
import { mapState, mapActions, mapMutations } from 'vuex';
import * as types from '@/vuex/mutation-types';
import io from 'socket.io-client';

export default {
  components: {
    CodeMenu,
    ToolBar
  },
  created() {
    this.fetchData();
  },
  data() {
    return {
      lessonStatus: this.$store.state.study.lessonStatus,
      studyInfo: this.$store.state.study,
      menuType: 'activity'
    }
  },
  watch: {
    '$route': 'fetchData'
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      role: state => state.activity.activityData.role
    })
  },
  beforeDestroy() {
    this[types.ACTIVITY_CLEAR]();
    this.$closeSocket();
  },
  methods: {
    ...mapActions([types.ACTIVITY_INIT, types.ACTIVITY_CLEAR, types.COURSE_END, types.ACTIVITY_MENU_INIT]),
    ...mapMutations([
      types.SET_BRAIN_STORM_REVIEW_DIALOG,
    ]),
    fetchData() {
      this.courseId = this.$route.params.courseId;
      this.lessonId = this.$route.params.lessonId;
      this.taskId = this.$route.params.taskId;
      this.activityId = this.$route.params.activityId;
      this[types.ACTIVITY_CLEAR]();
      this[types.ACTIVITY_INIT]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
        activityId: this.activityId,
      }).then((res) => {
        this.$getSocket(this.courseId, this.lessonId);
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }

        this[types.ACTIVITY_MENU_INIT]({
          role: this.role,
          courseId: this.courseId,
          lessonId: this.lessonId,
          ingLessonId: this.studyInfo.lessonId,
          lessonStatus: this.lessonStatus,
          up: this.activityData.up,
          next: this.activityData.next,
        });
        document.title = this.activityData.activityTitle;
      }).catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.response.data.message);
      })
    },
    handleBack() {
      this.$router.push({name: 'study'});
    },
    handleCourseEnd() {
      this[types.COURSE_END](this.lessonId).then((res) => {
        if (res.data.message) {
          this.$ajaxMessage(res.data.message);
          return;
        }

        this.$vux.toast.show({
          text: '下课成功',
          time: 3000,
        });

        setTimeout(() => {
          this.$router.push({
            name: 'lesson',
            params: {
              courseId: this.courseId
            }
          });
        }, 3000)

      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>

<style lang="less">
.activity-header {
  border-radius: 0.625rem;
  background: #fff;
  padding: 1.25rem;
  margin: 0.9375rem 0.625rem;
  .activity-header__lesson-title {
    font-size: 0.9375rem;
    margin-bottom: 0.9375rem;
    color: #313131;
  }
  .activity-header__title {
    font-size: 0.875rem;
    // margin-bottom: 0.9375rem;
    color: #4993e9;
  }
}

.activity-body {
  position: relative;
  border-radius: 0.625rem;
  background: #fff;
  margin: 0.9375rem 0.625rem;
  padding: 2.5rem;
  height: calc(~"100% - 15rem");
  &.activity-body--sm {
    padding: 0.3125rem;
    height: calc(~"100% - 10rem");
  }
  .activity-body__title {
    text-align: center;
    font-size: 1.1875rem;
    color: #4993e9;
    line-height: 2;
  }
  .activity-body__content {
    font-size: 1.1875rem;
    text-align: center;
    line-height: 2;
    margin-bottom: 0.9375rem;
    word-break: break-all;
  }
}



.activity-content {
  border-radius: 0.625rem;
  background: #fff;
  padding: 1.25rem;
  margin: 0.9375rem 0.625rem;
}

//放按钮部分
.activity-action {
  margin: 0.9375rem 0.625rem;
}

</style>
