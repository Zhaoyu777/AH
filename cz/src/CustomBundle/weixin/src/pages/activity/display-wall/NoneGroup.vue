<template>
  <div>
    <div class="wall-photo-warp" v-for="result in groups.results">
      <div class="wall-photo">
        <div class="weui-flex wall-photo__header">
          <img :src="result.avatar ? host + result.avatar : host + avatarCover" alt="" class="code-avatar wall-photo__avatar" />
          <div class="weui-flex__result wall-photo__title">{{ result.truename }}</div>
        </div>
        <div class="wall-photo__loading" v-if="!result.content.thumb">暂未提交作品</div>
        <div class="wall-photo__body" :style="{ backgroundImage: `url(${host}${result.content.thumb})` }" @click="photoEvent(result.content.id)" v-else-if="result.content.thumb">
          <div class="wall-photo__action">
            <div class="wall-photo__review" v-if="role === 'teacher'">
              <div class="code-tag code-tag--md code-tag--danger wall-photo-review" v-if="result.score === '0'" @click.stop="reviewDialog(result.id)">评分</div>
              <div class="code-tag code-tag--md code-tag--primary wall-photo-review" v-else>{{result.score}}分</div>
            </div>
            <span class="wall-photo__star" v-if="!result.isStar" @click.stop="photoLike(result)">
              <i class="cz-icon cz-icon-favoriteoutline"></i> {{ result.content.likeNum }}
            </span>
            <span class="wall-photo__star" v-else @click.stop="photoCancelLike(result)">
              <i class="cz-icon cz-icon-favorite"></i> {{ result.content.likeNum }}
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
      groups: state => state.activity.displayWall.groups[0],
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
