<template>
  <div class="weui-flex course-lesson-phase__task" :class="{ 'disabled': lessonState !== 'teached' && role === 'student' || preview == 1}" @click="taskSkip(link, lessonState)">
    <span class="course-lesson-phase__task-icon">
      <type-icon :activityType="link.activityType"></type-icon>
    </span>
    <div class="weui-flex__item course-lesson-phase__task-title">
      {{link.title}}
    </div>
    <span class="course-lesson-phase__task-assist" v-if="role == 'student'">
      <span class="code-tag code-tag--primary" v-if="link.status">已完成</span>
      <span class="code-tag code-tag--danger" v-else-if="!link.status">未完成</span>
    </span>
  </div>
</template>

<script>
import TypeIcon  from '@/pages/course/TypeIcon';
import * as types from '@/vuex/mutation-types';
import { mapActions, mapState } from 'vuex';

export default {
  props: ['link', 'lessonState', 'role', 'preview'],
  data() {
    return {
      courseId: this.$route.params.courseId,
    }
  },
  components: {
    TypeIcon
  },
  methods: {
    taskSkip(link, lessonState) {
      if (this.preview != 1) {
        if (this.role === 'student' && lessonState === 'created') {
          this.$vux.toast.show({
            text: '未开始上课',
            type: 'warn'
          });
          return;
        }

        if (this.role === 'student' && lessonState === 'teaching') {
          this.$vux.toast.show({
            text: '暂未下课',
            type: 'warn'
          });
          return;
        }

        this.$router.push(`/course/${link.courseId}/lesson/${link.lessonId}/task/${link.taskId}/activity/${link.id}/type/${link.activityType}`)
      }
    }
  }
}
</script>
