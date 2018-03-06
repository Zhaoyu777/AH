<template>
  <div>
    <div class="wall-photo-warp" v-for="(item, index) in results">
      <div class="weui-flex wall-group">
        <div class="wall-photo__number wall-photo__number--primary">
          {{ index + 1 }}组
        </div>
        <div class="weui-flex__item wall-group__title">
          <span v-for="(member, index) in item.replys" v-if="index < 4">
            <img class="code-avatar wall-photo__avatar"
              :src="member.avatar ? host + member.avatar : host + avatarCover">
          </span>
        </div>
        <div class="wall-group__info">应答 {{ item.replyCount }} 人，已答 <span class="text-primary">{{ item.currentReplyCount }}</span> 人</div>
      </div>
      <div class="wall-photo" v-for="member in item.replys">
        <div class="wall-photo-list">
          <div class="weui-flex wall-photo__header">
            <img class="code-avatar wall-photo__avatar"
              :src="member.avatar ? host + member.avatar : host + avatarCover">
            <div class="weui-flex__item wall-photo__title">{{ member.truename }}</div>
            <div class="text-primary">
              已提交
            </div>
          </div>
          <div class="wall-content__body">
            {{ member.content }}
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
      results: state => state.activity.oneSentence.results
    })
  },
  created() {
    console.log(this.$store.state.activity.oneSentence.results);
  }
}
</script>
