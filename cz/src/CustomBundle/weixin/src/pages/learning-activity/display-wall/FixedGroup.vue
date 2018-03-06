<template>
  <div>
    <div class="wall-photo-warp" v-for="group in groups">
      <div class="wall-photo">
        <div class="weui-flex wall-photo__header">
          <div class="wall-photo__number wall-photo__number--primary">{{ group.no }}</div>
          <div v-for="(member, index) in group.members" v-if="index < 4">
            <img :src="member.avatar ? host + member.avatar : host + avatarCover" alt="" class="code-avatar wall-photo__avatar">
          </div>
          <div class="wall-photo__number" v-if="group.members.length > 4"><i class="cz-icon cz-icon-othermore"></i></div>
        </div>
        <div class="wall-photo__loading" v-if="!group.results">暂未提交作品</div>
          <div v-for="result in group.results" v-else-if="group.results">
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
  </div>
</template>

<script>
import { mapState } from 'vuex';
import { avatarCover } from '@/assets/js/data';

export default {
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
    }
  },
  computed: {
    ...mapState({
      groups: state => state.activity.displayWall.groups,
      role: state => state.study.role
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
