<template>
  <div>
    <div v-if="preview == 1">
      <div class="code-alert code-alert--primary block" v-if="courseData.currentTask && courseData.currentTask.taskId">
      正在上课：{{ courseData.currentTask.lessonTitle }}-{{courseData.currentTask.activityTitle}}
      </div>
    </div>
    <div v-else>
      <router-link class="code-alert code-alert--primary block" v-if="courseData.currentTask && courseData.currentTask.taskId" :to="`/course/${courseId}/lesson/${courseData.currentTask.lessonId}/task/${courseData.currentTask.taskId}/activity/${courseData.currentTask.activityId}/type/${courseData.currentTask.activityType}`">
        正在上课：{{ courseData.currentTask.lessonTitle }}-{{courseData.currentTask.activityTitle}}
      </router-link>
    </div>
  </div>
</template>

<script>
// 待改
export default {
  props: ['courseData'],
  data() {
    return {
      courseId: this.$route.params.courseId,
      preview: this.$route.query.preview,
    }
  }
}
</script>
