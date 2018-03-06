<template>
  <div class="wall-photo" v-if="selfData && selfData.thumb">
    <div class="wall-photo__header">
      <div class="weui-flex__item wall-photo__title">我的展示</div>
    </div>
    <div class="wall-photo__body" :style="{ backgroundImage: `url(${host}${selfData.thumb})` }" @click="photoEvent(selfData.contentId)">
      <div class="wall-photo__action">
        <span class="wall-photo__star">
          <i class="cz-icon cz-icon-favoriteoutline"></i> {{ selfData.likeNum }}
        </span>
        <span class="wall-photo__comment">
          <i class="cz-icon cz-icon-forum"></i> {{ selfData.postNum }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  data() {
    return {
      host: this.$getCookie('host')
    }
  },
  computed: {
    ...mapState({
      selfData: state => state.activity.displayWall.selfData
    })
  },
  methods: {
    photoEvent(contentId) {
      this.$emit('photoEvent', contentId);
    }
  }
}
</script>
