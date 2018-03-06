<template>
  <div class="wall-photo-upload">
    <div v-if="isAlready">
      <button class="weui-btn weui-btn_primary" @click="upload">
        <i class="cz-icon cz-icon-camera"></i>
      </button>
    </div>
    <div v-else>
    </div>
  </div>
</template>

<script>
import { TransferDom, Actionsheet } from 'vux';
import api from '@/assets/js/api';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  directives: {
    TransferDom
  },
  data() {
    return {
      isReTry: false,
      isAlready: false,
      taskId: this.$route.params.taskId,
      test: '',
    }
  },
  created() {
    let callback = this.hasAlready.bind(this);
    this.$jssdk(callback);
  },
  computed: {
    ...mapState({
      practiceWork: state => state.activity.practiceWork,
      activityData: state => state.activity.activityData,
    })
  },
  methods: {
    ...mapActions([types.PRACTICE_WORK_UPLOAD]),
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
          _this[types.PRACTICE_WORK_UPLOAD]({
              media_id: serverId,
              taskId: _this.taskId
          })
          .then((response) => {
            _this.test = response;
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
              text: response.response.data.message || '上传失败',
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
      let _this = this;
      this.$wechat.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['camera','album'],
        success: function(res) {
          _this.isReTry = true;
          console.log('选择图片成功');
          localId = res.localIds[0];
          _this.response = 'asdasdsa';
          _this.download(localId);
        },
        cancel: function() {
          _this.isReTry = true;
          console.log('选择图片取消');
        },
        fail: function() {
          console.log('选择图片失败');
        },
        trigger: function () {
          console.log('选择图片被触发!');
        },
        complete: function() {
          if(_this.isReTry === false) {
            _this.upload();
            _this.isReTry = true;
          }
          console.log('选择图片完成');
        }
      });
    },
  }
}
</script>

<style lang="less">
.wall-photo-upload {
  margin: 0.9375rem 0.625rem;
  i {
    font-size: 1.25rem;
  }
}
.finished {
  text-align: center;
  margin-top: 10px;
  margin-bottom: 10px;
}
.text-size {
  font-size: 1.1875rem;
}
.text-center {
  text-align: center;
}
</style>
