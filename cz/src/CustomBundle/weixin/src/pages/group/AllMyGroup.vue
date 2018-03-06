<template>
  <main class="app-main">
    <scroller :on-infinite="infinite">
      <div class="group-panel">
        <div class="weui-panel weui-panel_access">
          <div class="weui-panel__hd">
            <i class="cz-icon cz-icon-remen"></i>
              我的小组
          </div>
          <div class="weui-panel__bd" >
            <router-link
              class="weui-media-box weui-media-box_appmsg"
              v-for="group in myGroupData"
              :to="{ name: 'groupShow', params: { groupId: group.id } }"
              :key="group.id">
              <div class="weui-media-box__hd">
                <img
                  alt="小组图片"
                  class="weui-media-box__thumb"
                  :src="host + group.logo" />
              </div>
              <div class="weui-media-box__bd">
                <h4 class="weui-media-box__title">{{ group.title }}</h4>
                <p class="weui-media-box__desc">
                  <i class="cz-icon cz-icon-person"></i>
                    {{ group.memberNum }}
                  <i class="cz-icon cz-icon-textsms mls"></i>
                    {{ group.threadNum }}
                </p>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </scroller>
  </main>
</template>

<script>
import api from '@/assets/js/api';
import Group from './components/Group';

export default {
  props:['type'],
  components: {
    Group
  },
  data() {
    return {
      host: this.$getCookie('host'),
      myGroupData: [],
      currentPage: 1,
      limit: 10,
    }
  },
  methods: {
    infinite(done) {
      console.log('infinite');
      this.$http.get(api.group.myGroups(), {
        params: {
          page: this.currentPage,
          // 待删
          limit: 10,
        }
      }).then((res) => {
        for(let member of res.data.data) {
          this.myGroupData.push(member);
        };
        if (res.data.paging.total + 1 ==  this.currentPage) {
          done(true);
          return;
        };
        this.currentPage = this.currentPage + 1;
        done();
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });
    }
  }
}
</script>
