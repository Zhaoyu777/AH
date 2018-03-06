<template>
  <div>
    <div class="wall-photo-warp" v-for="group in groups">
      <div class="weui-flex wall-group">
        <div class="wall-photo__number wall-photo__number--primary">{{ group.no }}</div>
        <div class="weui-flex__item wall-group__title">{{ group.title }}</div>
        <div class="wall-group__info">应答 {{ group.memberCount }} 人，已答 <span class="text-primary">{{ group.replyCount }}</span> 人</div>
      </div>
      <div class="wall-photo" v-if="!group.results">
        <div class="wall-photo__loading" >暂未提交作品</div>
      </div>
      <div class="wall-photo" v-for="result in group.results" v-else-if="group.results">
        <div class="weui-flex wall-photo__header">
          <img :src="result.avatar ? host + result.avatar : host + avatarCover" alt="" class="code-avatar wall-photo__avatar">
          <div class="weui-flex__item wall-photo__title">{{ result.truename }}</div>
        </div>
        <div class="wall-photo__body" :style="{ backgroundImage: `url(${host}${result.content.thumb})` }" @click="photoEvent(result.content.id)">
          <div class="wall-photo__action">
            <span class="wall-photo__star" v-if="!result.isStar" @click.stop="photoLike(result)">
              <i class="cz-icon cz-icon-praise"></i> {{ result.content.likeNum }}
            </span>
            <span class="wall-photo__star" v-else @click.stop="photoCancelLike(result)">
              <i class="cz-icon cz-icon-praise-hover"></i> {{ result.content.likeNum }}
            </span>
            <span class="wall-photo__comment">
              <i class="cz-icon cz-icon-forum"></i> {{ result.content.postNum }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';
import { avatarCover } from '@/assets/js/data';

export default {
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host')
    }
  },
  computed: {
    ...mapState({
      groups: state => state.activity.displayWall.groups,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    photoEvent(contentId) {
      this.$emit('photoEvent', contentId);
    },
    photoLike(item) {
      this.$emit('photoLike', item);
    },
    photoCancelLike(item) {
      this.$emit('photoCancelLike', item);
    },
    reviewDialog(resultId) {
      this.$emit('reviewDialog', resultId);
    }
  }
}
</script>
