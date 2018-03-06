<template>
  <div id="input_code">
    <div class="input" @click="focusInput">
      <div class="input_code" :style="{left:left,'z-index':zIndex}">
        <input ref="input_code" type="tel" v-model="inputCode" @keyup="inputCodeEvent($event)" @blur="blurInput"
               @keyup.delete="deleteInput"
               maxlength="1" autofocus/>
      </div>
      <span class="code-number" v-for="(item, index) in number"
            v-text="codeArray[index]"></span>
    </div>
  </div>
</template>

<script>
  export default {
    props: {
      code: {
        type: Array,
        required: true,
        default: function () {
          return [];
        }
      }
    },
    data() {
      return {
        inputCodeNum: 0,//输入框的位置
        left: '0',
        zIndex: -10,
        inputCode: "",//单次输入的值
        codeArray: [],//输入的值数组
        number: 4, //数字个数
      }
    },
    methods: {
      /** 删除输入 */
      deleteInput(){
        if (this.inputCodeNum > 0) {
          this.inputCodeNum--;
          let n = this.inputCodeNum / this.number;
          this.left = n * 100 + "%";
          this.codeArray.pop();
          this.code.pop();
        }
      },
      /** 每次输入的事件 */
      inputCodeEvent(event){
        console.log('输入');
        let code = parseInt(event.target.value);
        if (isNaN(code)) {
          return;
        }
        if (this.inputCodeNum < this.number) {
          this.inputCodeNum++;
          if (this.inputCodeNum !== this.number) {
            let n = this.inputCodeNum / this.number;
            this.left = n * 100 + "%";
            this.codeArray.push(code);
            this.code.push(code);
            this.inputCode = "";
          } else {
            this.codeArray.push(code);
            this.code.push(code);
          }
        }
      },
      /** 失去焦点 */
      blurInput(){
        this.zIndex = -10;
      },
      /** 获得焦点 */
      focusInput() {
        this.zIndex = 10;
        this.$refs.input_code.focus();
      }
    }
  }
</script>

<style lang="less" scoped>
  #input_code {
    width: auto;
    padding: 0 3.75rem;

    .input {
      position: relative;
      display: flex;
      width: 100%;
      height: 3.125rem;
      flex-direction: row;
      justify-content: center;
      align-items: center;

      > span {
        display: inline-block;
        width: 25%;
        height: 3.125rem;
        line-height: 3.125rem;
        font-size: 1.25rem;
        text-align: center;
        color: rgb(97, 97, 97);
        font-weight: 900;
        border: 1px solid #ccc;
        background-color: white;
        &:last-child {
          border-right: 1px solid #ccc;
        }
      }
    }

    .input > span:not(:last-child) {
      border-right: none;
    }

    .input_code {
      position: absolute;
      top: 0;
      left: 0;
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
      width: 24%;
      height: 3.125rem;
      margin-left: 1%;
      font-weight: 900;
      border: none;
      background: none;
      z-index: -10;
      > input {
        width: 90%;
        margin-right: 5px;
        font-size: 1.5rem;
        color: rgb(97, 97, 97);
        text-align: center;
        outline: none;
        border: none;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0);
      }
    }
  }
</style>