<template>
  <div class="app has-bar">
    <tool-bar @barBack="handleBack"></tool-bar>
    <main class="app-main bg-white">
      <comment-header></comment-header>
      <comment-list></comment-list>
    </main>
    <comment-form></comment-form>
  </div>
</template>

<script>
import CommentHeader from './Header';
import CommentList from './List';
import CommentForm from './Form';
import ToolBar from '@/components/Toolbar';

import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      host: this.$getCookie('host'),
      contentId: this.$route.params.contentId,
    }
  },
  components: {
    CommentHeader,
    CommentList,
    CommentForm,
    ToolBar
  },
  created() {
    this.fetchData();
  },
  computed: {
    ...mapState({
      contentData: state => state.activity.practiceDetail.contentData,
      postData: state => state.activity.practiceDetail.postData,
    })
  },
  methods: {
    ...mapActions([types.PRACTICE_DETAIL_INIT]),
    fetchData() {
      this[types.PRACTICE_DETAIL_INIT](this.contentId).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    handleBack() {
      this.$router.go(-1);
    },
  }
}
</script>
