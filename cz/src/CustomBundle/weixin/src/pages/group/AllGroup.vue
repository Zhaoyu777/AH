<template>
  <main class="app-main">
    <scroller :on-infinite="infinite">
      <div class="group-panel">
        <div class="weui-panel weui-panel_access">
          <div class="weui-panel__hd">
            <i class="cz-icon cz-icon-remen"></i>
              所有小组
          </div>
          <div class="weui-panel__bd" >
            <router-link
              class="weui-media-box weui-media-box_appmsg"
              v-for="group in allGroupData"
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
import Group from './components/Group';
import api from '@/assets/js/api';

export default {
  components: {
    Group
  },
  data() {
    return {
      host: this.$getCookie('host'),
      type: null,
      allGroupData: [],
      currentPage: 1,
    }
  },
  methods: {
    infinite(done) {
      console.log('infinite');
      this.$http.get(api.group.groups(), {
        params: {
          page: this.currentPage,
          // 待删
          limit: 10,
        }
      }).then((res) => {
        for(let member of res.data.data) {
          this.allGroupData.push(member);
        };
        if (res.data.paging.total + 1 ==  this.currentPage) {
          console.log('done');
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

<style lang="less">
  .group-panel {
  margin: 10px;
  .weui-panel {
    border-radius: .3125rem;
  }
  .weui-panel__hd {
    font-size: .875rem;
    padding: .9375rem;
    color: #414141;
  }
}
</style>