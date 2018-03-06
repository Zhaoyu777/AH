<template>
  <div>
    <div class="uploader-box">
      <quill-editor
        v-model="content"
        ref="myQuillEditor"
        @focus="focus"
        :options="editorOption"
        @change="onEditorChange($event)">
        <div id="toolbar" slot="toolbar">
          <button>
            <i class="cz-icon cz-icon-xiangce"></i>
          </button>
        </div>
      </quill-editor>
      <input ref="input"
        class="uploader__input"
        type="file"
        accept="image/jpg,image/jpeg,image/png,image/gif"
        multiple="false"
        @change="uploadImage" />
    </div>
    <div class="group-content">
      <button class="weui-btn weui-btn_primary" @click="postClick()">
        发表
      </button>
    </div>
  </div>
</template>

<script>
import { quillEditor } from 'vue-quill-editor';
import api from '@/assets/js/api';

export default {
  components: {
    quillEditor,
  },
  data() {
    return {
      showModuleName: true,
      content: '',
      images: [],
      uploadUrl: api.uploadImage(),
      editorOption: {
        modules: {
          toolbar: '#toolbar',
        },
        placeholder: '请输入你的回复...',
      }
    }
  },
  mounted() {
    // 取消fastClick，需要添加class needsclick
    this.$refs.myQuillEditor.$refs.editor.children[0].setAttribute('class', 'ql-editor ql-blank needsclick');
  },
  methods: {
    uploadImage() {
      let formData = new window.FormData();
      formData.append('img', this.$refs.input.files[0]);
      this.$http.post(api.uploadImage(), formData , {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true
      }).then((res) => {
        if (this.$vux && this.$vux.loading) {
          this.$vux.loading.hide()
        }
        let imgLabel = `<img src="${res.data.data.url}" />`
        this.content += imgLabel;
      }).catch((response) => {
        console.log(response);
        this.$ajaxMessage(response.response.data.message);
      });
    },
    onEditorChange({ editor, html, text }) {
      console.log('editor change!', editor, html, text)
      this.content = html;
    },
    postClick() {
      this.$emit('changeContent', this.content);
    },
    focus() {
      console.log('focus');
    },
    handleBack() {
      this.$router.go(-1);
    }
  }
}
</script>

<style lang="less">
  .quill-editor {
    margin: .625rem;
  }
  .ql-container .ql-editor {
    min-height: 18.75rem;
    padding-bottom: 1em;
    max-height: 31.25rem;
    font-size: 1rem;
  }
  .uploader-box {
    position: relative;
    .quill-editor {
      background-color: #fff;
    }
    .uploader__input {
      position: absolute;
      opacity: 0;
      left: 1.25rem;
      top: .625rem;
      width: 1.625rem;
    }
  }
</style>
