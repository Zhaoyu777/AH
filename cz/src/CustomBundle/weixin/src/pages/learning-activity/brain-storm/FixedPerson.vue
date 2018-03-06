<template>
  <div>
    <div class="wall-photo-warp" v-for="(item, index) in groups" >
      <div class="weui-flex wall-group">
        <div class="wall-photo__number wall-photo__number--primary">{{ index + 1 }}组</div>
        <div class="weui-flex__item wall-group__title">
          <span v-for="(member, index) in item.results" v-if="index < 4">
            <img class="code-avatar wall-photo__avatar"
                 :src="member.avatar ? host + member.avatar : host + avatarCover">
          </span>
        </div>
        <div class="wall-group__info">应答 {{ item.memberCount }} 人，已答 <span class="text-primary">{{ item.replyCount
          }}</span> 人
        </div>
      </div>
      <div class="wall-photo" v-if="!item.results || item.results.length <= 0">
        <div class="wall-photo__loading" >暂未提交作品</div>
      </div>
      <div class="wall-photo" v-for="member in item.results" v-else-if="item.results">
        <div class="wall-photo-list">
          <div class="wall-photo__header">
            <div class="weui-flex">
              <img class="code-avatar wall-photo__avatar"
                   :src="member.avatar ? host + member.avatar : host + avatarCover" />
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
  </div>
</template>

<script>
  import {mapState, mapMutations, mapActions} from 'vuex';
  import {avatarCover} from '@/assets/js/data';
  import * as types from '@/vuex/mutation-types';

  export default {
    data() {
      return {
        avatarCover,
        host: this.$getCookie('host'),
      }
    },
    computed: {
      ...mapState({
        brainStorm: state => state.activity.brainStorm,
        groups: state => state.activity.brainStorm.groups
      })
    },
    methods: {
      ...mapMutations([
        types.SET_BRAIN_STORM_REVIEW_DIALOG
      ]),
      ...mapActions([
        types.SET_BRAIN_STORM_REMARK_CURRENT_ID,
        types.BRAIN_STORM_REMARK
      ]),
      handleSubmitReview({score, remark, resultId}) {
        this[types.BRAIN_STORM_REMARK]({
          resultId: this.brainStorm.currentReviewId,
          score,
          remark
        }).then((res) => {
          if (res.data.message) {
            this.$ajaxMessage(res.data.message);
            return;
          }
          this.handleHideDialog();
          this.$vux.toast.show({
            text: '评分成功'
          })
        }, (res) => {
          this.handleHideDialog();
          this.$ajaxMessage(res.response.data.message);
        });
      },
      handleHideDialog() {
        this[types.SET_BRAIN_STORM_REVIEW_DIALOG](false);
      },
      showReviewDialog(resultId) {
        this[types.SET_BRAIN_STORM_REMARK_CURRENT_ID](resultId);
        this[types.SET_BRAIN_STORM_REVIEW_DIALOG](true);
      }
    }
  }
</script>
