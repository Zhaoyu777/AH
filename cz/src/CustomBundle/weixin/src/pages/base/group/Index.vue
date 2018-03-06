<template>
  <main class="app-main">
    <scroller :on-infinite="infinite">
      <hot-group></hot-group>
      <my-group></my-group>
      <!-- <recent-thread :threadData="threadData" :groupId="groupId"></recent-thread> -->
      <div class="group-panel">
        <div class="weui-panel weui-panel_access">
          <div class="weui-panel__hd">
            <i class="cz-icon cz-icon-textsms mrs"></i>
            最近话题
          </div>
          <thread :threadData="threadData" :groupId="groupId"></thread>
        </div>
      </div>
    </scroller>
  </main>
</template>
<script>
import HotGroup from '@/pages/group/HotGroup';
import MyGroup from '@/pages/group/MyGroup';
import Thread from '@/pages/group/components/Thread';
import api from '@/assets/js/api';

import {
  Panel,
  Group,
  Radio
} from 'vux'

export default {
  components: {
    HotGroup,
    MyGroup,
    Thread,
    Panel,
    Group,
    Radio
  },
  data() {
    return {
      groupId: 0,
      currentPage: 1,
      threadData: [],
      limit: 10,
    }
  },
  created() {

  },
  methods: {
    infinite(done) {
      this.$http.get(api.group.topics(this.groupId), {
        params: {
          page: this.currentPage,
          // 待删
          limit: 10,
        }
      }).then((res) => {
        for(let member of res.data.data) {
          this.threadData.push(member);
        };
        console.log(this.threadData);
        if (res.data.paging.total + 1 ==  this.currentPage) {
          console.log('done');
          done(true);
          return;
        };
        this.currentPage = this.currentPage + 1;
        console.log(this.currentPage);
        done();
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>