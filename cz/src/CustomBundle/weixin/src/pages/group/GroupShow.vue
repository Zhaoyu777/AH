<template>
  <main class="app-main">
    <scroller :on-infinite="infinite">
      <group-head></group-head>
      <div class="group-content">
        <router-link
          class="weui-btn weui-btn_primary"
          v-if="isMember"
          :to="{ name: 'createThread' }">
          发表话题
        </router-link>
        <button
          class="weui-btn weui-btn_primary"
          v-else="!isMember"
          @click="joinGroup">
          加入小组
        </button>
      </div>
      <div class="group-content">
        <button-tab v-model="buttonShow">
          <button-tab-item>小组话题</button-tab-item>
          <button-tab-item @on-item-click="groupMember()">小组成员</button-tab-item>
        </button-tab>
        <div v-show="!buttonShow">
          <thread
            :threadData="threadData"
            :groupId="groupId"
          ></thread>
        </div>
        <div v-show="buttonShow">
          <member :memberData="memberData"></member>
        </div>
      </div>
    </scroller>
  </main>
</template>

<script>
import { ButtonTab, ButtonTabItem } from 'vux';
import { avatarCover } from '@/assets/js/data';
import GroupHead from '@/pages/group/components/GroupHead';
import Member from '@/pages/group/components/Member';
import Thread from '@/pages/group/components/Thread';
import { mapState, mapMutations } from 'vuex';
import * as types from '@/vuex/mutation-types';
import api from '@/assets/js/api';
import qs from 'qs';

export default {
  components: {
    ButtonTab,
    ButtonTabItem,
    Thread,
    GroupHead,
    Member,
  },
  data() {
    return {
      buttonShow: 0,
      groupId: this.$route.params.groupId,
      currentThreadPage: 1,
      currentMemberPage: 1,
      memberData: [],
      threadData: [],
      isJoin: true,
    }
  },
  computed: {
    ...mapState({
      isMember: state => state.group.isMember,
    })
  },
  methods: {
    ...mapMutations([
      types.GROUP_ISMEMBER
    ]),
    infinite(done) {
      console.log('infinite');
      // 有点冗余，待改善
      if (this.buttonShow == 0) {
        this.$http.get(api.group.topics(this.groupId), {
          params: {
            page: this.currentThreadPage,
            // 待删
            limit: 10,
          }
        }).then((res) => {
          for(let member of res.data.data) {
            this.threadData.push(member);
          };
          if (res.data.paging.total + 1 ==  this.currentThreadPage) {
            done(true);
            return;
          };
          this.currentThreadPage = this.currentThreadPage + 1;
          done();
        }).catch((response) => {
          // this.$ajaxMessage(response.response.data.message);
        });
      } else {
        this.$http.get(api.group.members(this.groupId), {
          params: {
            page: this.currentMemberPage,
            // 待删
            limit: 10,
          }
        }).then((res) => {
          for(let member of res.data.data) {
            this.memberData.push(member);
          };
          if (res.data.paging.total + 1 ==  this.currentMemberPage) {
            done(true)
            return;
          };
          this.currentMemberPage = this.currentMemberPage + 1;
          done();
        }).catch((response) => {
          // this.$ajaxMessage(response.response.data.message);
        });
      }
    },
    joinGroup() {
      this.$http.get(api.group.join(this.groupId)).then((res) => {
        this[types.GROUP_ISMEMBER](true);
        this.$vux.toast.show({
          text: '加入成功',
        });
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    },
    groupMember() {
      this.buttonShow = 1;
      this.infinite();
    },
  }
}
</script>

<style>
  .group-content {
    margin: .625rem;
  }
</style>
