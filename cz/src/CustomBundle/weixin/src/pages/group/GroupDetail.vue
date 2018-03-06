<template>
  <main class="app-main">
    <scroller :on-infinite="infinite">
      <group-head></group-head>
      <div class="group-panel" v-if="detailData">
        <div class="weui-panel weui-panel_access">
          <div class="weui-panel__hd">
            {{ detailData.title }}
          </div>
          <div class="weui-panel__bd">
            <div class="weui-media-box">
              <div class="thread-detail_label gray">
                <span class="mrm">发表于{{ detailData.timeStr }}</span>
                {{ detailData.hitNum }}次查看
              </div>
              <div class="reply-content" v-html="detailData.content"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="group-content">
        <router-link
          class="weui-btn weui-btn_primary"
          :to="{ name: 'create' , params: { groupId: groupId, threadId: threadId} }">
          发表回复
        </router-link>
      </div>
      <reply-list></reply-list>
    </scroller>
  </main>
</template>
<script>
import qs from 'qs';
import api from '@/assets/js/api';
import ToolBar from '@/components/Toolbar';
import GroupHead from './components/GroupHead';
import Editor from './components/Editor';
import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import ReplyList from './components/ReplyList';

export default {
  components: {
    ToolBar,
    GroupHead,
    Editor,
    ReplyList,
  },
  data() {
    return {
      groupId: this.$route.params.groupId,
      threadId: this.$route.params.threadId,
      detailData: [],
      replyData: [],
      currentPage: 1,
      replyedContent: '',
    }
  },
  created() {
    this.$http.get(api.group.detail(this.groupId, this.threadId)).then((res) => {
      this.detailData = res.data;
    });
  },
  computed: {
    ...mapState({
      isShow: state => state.group.isShow,
      postId: state => state.group.postId,
      fromUserId: state => state.group.fromUserId,
    })
  },
  methods: {
    ...mapMutations([
      types.GROUP_INIT,
      types.GROUP_REPLY_ISSHOW,
      types.GROUP_REPLY_POSTID,
      types.GROUP_REPLY_FROMUSERID,
    ]),
    infinite(done) {
      this.$http.get(api.group.posts(this.groupId, this.threadId), {
        params: {
          page: this.currentPage,
          // 待删
          limit: 10,
        }
      }).then((res) => {
        for (let member of res.data.data) {
          this.replyData.push(member);
        };
        this[types.GROUP_INIT](this.replyData);
        if (res.data.paging.total + 1 ==  this.currentPage) {
          done(true);
          return;
        };
        this.currentPage = this.currentPage + 1;
        done();
      }).catch((response) => {
        // this.$ajaxMessage(response.response);
      })
    },
    handleBack() {
      this.$router.push({
        name: 'group'
      });
    },
  }
}
</script>
<style lang="less">
.thread-detail_label {
  padding: .5625rem;
  background-color: #fafafa;
  border: .0625rem solid #f5f5f5;
  border-radius: .125rem;
  margin-bottom: 1.25rem;
}
.reply-content {
  margin: 10px 10px 10px 0;
  p,span {
    margin-bottom: 10px;
    font-size: .875rem;
    line-height: 1.6;
  }
  img {
    margin: 10px 0;
  }
  table, img {
    width: 100% !important;
  }
  ol {
    padding-left: 40px;
    li {
      padding: .1875rem;
    }
  }
}

</style>
