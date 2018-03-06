<template>
  <form @submit.prevent="commentSubmit" class="weui-flex dialogue-from">
    <input type="text" class="weui-flex__item dialogue-input" :placeholder="placeholder" v-model="postContent">
    <button class="code-btn code-btn--md code-btn--primary" :class="{ disabled: !postContent || isSubmitting }" :disabled="postContent ? false : true">{{ isSubmitting ? '提交中' : '提交' }} </button>
  </form>
</template>

<script>
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      isSubmitting: false,
      postContent: null,
      contentId: this.$route.params.contentId,
    }
  },
  computed: {
    ...mapState({
      placeholder: state => state.activity.practiceDetail.placeholder,
      replyName: state => state.activity.practiceDetail.replyName,
      parentId: state => state.activity.practiceDetail.parentId,
    })
  },
  methods: {
    ...mapActions([types.PRACTICE_DETAIL_SUBMIT]),
    commentSubmit() {
      if(this.isSubmitting === false) {
        this[types.PRACTICE_DETAIL_SUBMIT]({
          contentId: this.contentId,
          content: this.postContent,
        }).then(() => {
          this.postContent = null;
          this.isSubmitting = false;
        }).catch((response) => {
          this.isSubmitting = false;
          this.$ajaxMessage(response.response.data.message);
        });
      }
      this.isSubmitting = true;
    },
  }
}
</script>

<style lang="less">
.dialogue-from {
  position: absolute;
  bottom: 0;
  right: 0;
  left: 0;
  height: 2.25rem;
  padding: 0.5rem 0.9375rem;
  background: #fff;
  border: 0.0625rem solid #e5e5e5;
  .dialogue-input {
    width: 100%;
    border-radius: 0.3125rem;
    border: 0.0625rem solid #e5e5e5;
    padding: 0.4375rem 0.625rem;
    font-size: 0.875rem;
    margin-right: 0.625rem;
    &:focus {
      outline: none;
    }
  }
}
</style>
