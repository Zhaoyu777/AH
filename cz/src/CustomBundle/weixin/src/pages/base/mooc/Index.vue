<template>
  <div class='mooc-page'>
    <iframe class="mooc-iframe" :src='link'></iframe>
  </div>
</template>

<script>
export default {
  name: 'Mooc',
  data() {
    return {
      role: this.$getCookie('role'),
      link: `http://${window.location.host}`
    }
  }
}
</script>

<style type='less'>
  .mooc-page { 
    width: 100%;
    height: 100%;
    -webkit-overflow-scrolling: touch;
    overflow: scroll;
  } 
  .mooc-iframe {
    position: relative;
    left: 0;
    right: 0;
    top: 0;
    bottom: 3.25rem;
    z-index: 999;
    width: 100%;
    height: 100%;
    border: none;
  }
</style>