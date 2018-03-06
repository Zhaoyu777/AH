<template>
  <div>
    <div class="wall-photo-warp" v-for="result in results">
      <div class="wall-photo">
        <div class="weui-flex wall-photo__header">
          <img :src="result.avatar ? host + result.avatar : host + avatarCover" class="code-avatar wall-photo__avatar" />
          <div class="weui-flex__result wall-photo__title">{{ result.truename }}</div>
        </div>
        <div class="wall-photo__loading" v-if="!result.content.thumb">暂未提交作品</div>
        <div class="wall-photo__body" :style="{ backgroundImage: `url(${host}${result.content.thumb})` }"
             @click="photoEvent(result.content.id)"
             v-if="result.isTeacher === '0' && result.content.thumb">
          <div class="wall-photo__action">
            <div class="wall-photo__review" v-if="role === 'teacher'">
              <div class="code-tag code-tag--md code-tag--danger wall-photo-review" v-if="result.score === '0'"
                   @click.stop="reviewDialog(result.content.id, result.id)">评分</div>
              <div class="code-tag code-tag--md code-tag--primary wall-photo-review" v-else>{{result.score}}分</div>
            </div>
            <span class="wall-photo__star" v-if="!result.isStar" @click.stop="photoLike(result.content.id)">
              <i class="cz-icon cz-icon-praise"></i>
              {{ result.content.likeNum }}
            </span>
            <span class="wall-photo__star" v-else @click.stop="photoCancelLike(result.content.id)">
              <i class="cz-icon cz-icon-praise-hover"></i>
              {{ result.content.likeNum }}
            </span>
            <span class="wall-photo__comment">
              <i class="cz-icon cz-icon-forum"></i>
              {{ result.content.postNum }}
            </span>
          </div>
        </div>
        <div class="wall-photo__body" :style="{ backgroundImage: `url(${host}${result.content.thumb})` }"
             v-if="result.isTeacher === '1' && result.content.thumb">
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
      results: state => state.activity.practice.results,
      role: state => state.activity.activityData.role
    })
  },
  methods: {
    photoEvent(contentId) {
      this.$emit('photoEvent', contentId);
    },
    photoLike(contentId) {
      this.$emit('photoLike', contentId);
    },
    photoCancelLike(contentId) {
      this.$emit('photoCancelLike', contentId);
    },
    reviewDialog(resultId) {
      this.$emit('reviewDialog', resultId);
    }
  }
}
</script>
