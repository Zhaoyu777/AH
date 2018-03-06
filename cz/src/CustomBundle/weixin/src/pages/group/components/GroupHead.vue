<template>
<div class="group-panel">
  <div class="weui-panel">
    <div class="group-panel__head"
        :style="{ 'background-image': 'url( '+ groupBg +')' }">
      <div v-if="currentGroupData">
        <a href="javascript:;" class="weui-media-box weui-media-box_appmsg">
          <div class="group-head__img">
            <img :src="host + currentGroupData.logo">
          </div>
          <div class="weui-media-box__bd">
            <h4 class="weui-media-box__title">{{ currentGroupData.title }}</h4>
            <p class="weui-media-box__desc text-sm">
              {{ currentGroupData.memberNum }}个成员
              <span class="mls">{{ currentGroupData.threadNum }}个话题</span>
            </p>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>
</template>

<script>
import { groupBg } from '@/assets/js/data';
import api from '@/assets/js/api';
import { mapMutations } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      groupId: this.$route.params.groupId,
      currentGroupData: null,
      host: this.$getCookie('host'),
      groupBg,
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    ...mapMutations([
      types.GROUP_ISMEMBER
    ]),
    fetchData() {
      this.$http.get(api.group.headDetail(this.groupId)).then((res) => {
        console.log(res);
        this.currentGroupData = res.data;
        this[types.GROUP_ISMEMBER](this.currentGroupData.isMember)
      })
    }
  }
}
</script>

<style lang="less">
.group-panel {
  margin: 10px;
}

.group-panel__head {
  padding: .9375rem .5rem;
  background-size: cover;
  background-position: center;
}

.group-head__img {
  margin-right: .8em;
  width: 3.75rem;
  height: 3.75rem;
  img {
    width: 100%;
    height: 100%;
  }
}

.weui-media-box__title {
  font-size: 1.0625rem;
  margin-bottom: .5rem;
  color: #414141;
}
</style>