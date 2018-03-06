<template>
  <div>
    <div v-for="item in results">
      <div v-for="member in item">
        <div class="wall-photo-warp">
          <div class="wall-photo">
            <div class="wall-photo-list">
              <div class="weui-flex wall-photo__header">
                <img class="code-avatar wall-photo__avatar"
                  :src="member.avatar ? host + member.avatar : host + avatarCover">
                <div class="weui-flex__item wall-photo__title">
                  {{ member.truename }}
                </div>
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
  }
}
</script>
