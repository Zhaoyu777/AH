<template>
  <main class="app-main">
    <iframe
      :src="host + '/course/'+ courseId"
      style="width:100%;height:100%;border:none"
      allowfullscreen webkitallowfullscreen>
    </iframe>
  </main>
</template>

<script>
export default {
  data() {
    return {
      courseId: this.$route.params.courseId,
      host: this.$getCookie('host'),
    }
  },
}
</script>
