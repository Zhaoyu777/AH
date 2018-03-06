<template>
  <x-dialog class="review-dialog"
    v-model="isShow"
    :hide-on-blur="true"
    @on-hide="hideDialog">
    <div class="dialog-title">评分</div>
    <div class="dialog-subtitle">评价(可多选)</div>
    <div class="code-btn-list">
      <div class="code-btn code-btn--lg"
        v-bind:class="{ 'code-btn--primary': (reviewRemark.indexOf(item.value) !== -1), 'code-btn--default': (reviewRemark.indexOf(item.value) === -1) }"  v-for="item in reviewsData.remarks"
        @click="getRemark(item.value)">
        {{item.value}}
      </div>
    </div>
    <div class="dialog-subtitle">评分</div>
    <div class="code-btn-list weui-flex">
      <div class="code-btn code-btn--lg weui-flex__item"
        v-bind:class="{ 'code-btn--primary': (reviewScore === item.value), 'code-btn--default': (reviewScore !== item.value) }"
        v-for="item in reviewsData.scores"
        @click="getScore(item.value)">
        +{{item.value}}分
      </div>
    </div>
    <div class="dialog-footer" @click="submitReview()">
      确定
    </div>
  </x-dialog>
</template>

<script>
import { XDialog } from 'vux';
import { reviewsStudentData } from '@/assets/js/data';

export default {
  props: ['isDialogShow'],
  components: {
    XDialog
  },
  data() {
    return {
      isShow: this.isDialogShow,
      reviewsData: reviewsStudentData,
      reviewRemark: [],
      reviewScore: 0,
    }
  },
  watch: {
    isDialogShow: function(value) {
      this.isShow = value
    },
  },
  methods: {
    getRemark(remark) {
      if (this.reviewRemark.indexOf(remark) == -1) {
        this.reviewRemark.push(remark);
      } else {
        this.reviewRemark = this.reviewRemark.filter((item) => item != remark);
      }
    },
    getScore(score) {
      this.reviewScore = score;
    },
    submitReview() {
      if (this.reviewScore && this.reviewRemark) {
        this.$emit('submitReview', {
          score: this.reviewScore,
          remark: this.reviewRemark.join(',')
        });
      } else {
        this.$vux.toast.show({
          type: 'warn',
          text: '请选择评价',
          position: 'middle'
        })
      }
    },
    hideDialog() {
      this.reviewScore = 0;
      this.reviewRemark = [];
      this.isShow = false;
      this.$emit('hideDialog', this.isShow);
    }
  }
}
</script>
