<template>
  <main class="app-main">
    <editor @changeContent="changeContentListener"></editor>
  </main>
</template>

<script>
import Editor from './components/Editor';
import api from '@/assets/js/api';
import qs from 'qs';

export default {
  components: {
    Editor
  },
  data() {
    return {
      groupId: this.$route.params.groupId,
      threadId: this.$route.params.threadId,
      content: '',
    }
  },
  methods: {
    changeContentListener(data) {
      console.log(data);
      this.content = data;
      this.$http.post(api.group.post(this.groupId, this.threadId),
        qs.stringify({
          content: this.content
        }), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true

      }).then((res) => {
        this.$vux.toast.show({
          text: '发表成功',
        })
        this.content = '';
        this.$router.push(`/groups/${this.groupId}/thread/${this.threadId}`);
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>