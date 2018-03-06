<template>
  <div>
    <div class="weui-panel__bd" >
      <router-link
        class="weui-media-box weui-media-box_appmsg"
        v-for="group in hotGroupData"
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
</template>

<script>
import api from '@/assets/js/api';

export default {
  props:['type'],
  data() {
    return {
      host: this.$getCookie('host'),
      hotGroupData: null,
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      if (this.type == "my") {
        console.log('my');
        this.$http.get(api.group.myGroups()).then((res) => {
          this.hotGroupData = res.data.data;
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
      } else {
        console.log('hot');
        this.$http.get(api.group.groups(), {
          params: {
            type: this.type
          }
        }).then((res) => {
          this.hotGroupData = res.data.data;
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
      }
    }
  }
}
</script>

<style lang="less">
.weui-panel__hd {
  font-size: .875rem;
  padding: .9375rem;
  color: #414141;
}
.weui-media-box_appmsg {
  padding: 10px;
  .weui-media-box__hd {
    width: 50px;
    height: 50px;
    border-radius: .3125rem;
    line-height: 1.45;
    .weui-media-box__thumb {
      border-radius: .3125rem;
      width: 100%;
      height: 100%;
      border-radius: .3125rem;
    }
    .weui-media-box__reply {
      width: 100%;
      height: 100%;
      border-radius: .3125rem;
      background-color: #d7e6f8;
      color: #fff
    }
    .reply-num {
      padding-top: .1875rem;
      font-size: 1.0625rem;
    }
  }
}
.weui-media-box__title {
  // font-size: .875rem;
  margin-bottom: .5rem;
  color: #414141;
}
.weui-media-box__desc {
  // color: #ccc;
  font-size: .875rem;
  -webkit-line-clamp: 1;
  .code-avatar {
    vertical-align: bottom;
  }
}
</style>