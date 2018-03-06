<template>
  <main class="app-main">
    <group-head></group-head>
    <div class="group-panel">
      <div class="weui-panel weui-panel_access">
        <div class="weui-panel__hd">创建话题</div>
        <div class="weui-panel__bd">
          <div class="group-content">
            <group>
              <x-textarea
                :rows="1"
                :title="'标题'"
                :placeholder="'请输入标题'"
                :show-counter="false"
                autosize
                v-model="value"
              ></x-textarea>
            </group>
          </div>
          <editor @changeContent="changeContentListener"></editor>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import { XTextarea, Group, XInput } from 'vux';
import GroupHead from './components/GroupHead';
import Editor from './components/Editor.vue';
import qs from 'qs';
import api from '@/assets/js/api';

export default {
  components: {
    XTextarea,
    Group,
    XInput,
    GroupHead,
    Editor,
  },
  data() {
    return {
      groupId: this.$route.params.groupId,
      threadId: this.$route.params.threadId,
      value: null,
      content: '',
    }
  },
  methods: {
    changeContentListener(data) {
      this.content = data;
      if (this.value === null) {
        this.$vux.toast.show({
          text: '请输入标题',
          type: 'warn'
        })
        return;
      }
      if (this.content === '') {
        this.$vux.toast.show({
          text: '请输入回复内容',
          type: 'warn'
        })
        return;
      }
      this.$http.post(api.group.create(this.groupId),
        qs.stringify({
          title: this.value,
          content: this.content,
          groupId: this.groupId,
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
        this.$router.push(`/groups/${this.groupId}/thread/${res.data.id}`);
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
  }
}
</script>

