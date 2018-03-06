<template>
  <div class="wall-photo-upload" v-if="practice.taskStatus === 'start'">
    <div v-if="isAlready">
      <button class="weui-btn weui-btn_primary" @click="upload">
        <i class="cz-icon cz-icon-camera"></i>
      </button>
    </div>
    <div v-else>
      <button class="weui-btn weui-btn_default" disabled>加载中...</button>
    </div>
  </div>
</template>

<script>
import { TransferDom, Actionsheet } from 'vux';
import api from '@/assets/js/api';
import { mapState, mapActions } from 'vuex';

export default {
  directives: {
    TransferDom
  },
  props: ['taskId'],
  data() {
    return {
      isReTry: false,
      isAlready: false,
    }
  },
  created() {
    let callback = this.hasAlready.bind(this);
    this.$jssdk(callback);
  },
  computed: {
    ...mapState({
      practice: state => state.activity.practice,
    })
  },
  methods: {
    hasAlready() {
      this.isAlready = true;
    },
    download(localId) {
      let _this = this;
      _this.$wechat.uploadImage({
        localId: localId, // 需要上传的图片的本地ID，由chooseImage接口获得
        isShowProgressTips: 1, // 默认为1，显示进度提示
        success(res) {
          let serverId = res.serverId; // 返回图片的服务器端ID
          _this.$http.get(api.mediaDownload(), {
            params: {
              media_id: serverId,
              taskId: _this.taskId
            }
          }).then((response) => {
            if (response.data.message) {
              _this.$ajaxMessage(response.data.message);
              return;
            }
            _this.$vux.toast.show({
              text: '上传成功',
            });
            _this.$emit('download');
          }, (response) => {
            _this.$vux.toast.show({
              text: '上传失败',
              type: 'warn',
            })
          }).catch(error => {
            console.log(error);
          })
        }
      });
    },
    upload() {
      // 调用微信接口
      let localId = null;
      const _this = this;
      this.$wechat.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['camera','album'],
        success: function(res) {
          _this.isReTry = true;
          localId = res.localIds[0];
          _this.download(localId);
        },
        cancel: function() {
          _this.isReTry = true;
        },
        complete: function() {
          if(_this.isReTry === false) {
            _this.upload();
            _this.isReTry = true;
          }
        }
      });
    },
  }
}
</script>

<style lang="less">
.wall-photo-upload {
  margin: 0.9375rem;
}
</style>
