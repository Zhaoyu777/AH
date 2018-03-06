<template>
  <x-dialog class="review-dialog" v-model="dialogShow" v-if="role == 'student'">
    <div class="dialog-title">匿名评价</div>
    <div class="code-btn-list">
      <div class="code-btn code-btn--lg"
            :class="{ 'code-btn--primary': (reviewScore === item.score), 'code-btn--default': (reviewScore !== item.value) }"
            v-for="item in reviews"
            @click="getReview(item.score, item.remark)">
        {{item.remark}}
      </div>
    </div>
    <div class="dialog-footer" @click="submitReviews">
      确定
    </div>
  </x-dialog>
</template>

<script>
import { XDialog } from 'vux';
import { reviewsData } from '@/assets/js/data';
import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';

  export default {
    data() {
      return {
        reviews: reviewsData,
        reviewScore: 0,
        reviewRemark: null,
        courseId: this.$route.params.courseId,
      };
    },
    props: ['reviewCurrentId', 'dialogShow'],
    components: {
      XDialog
    },
    computed: {
      ...mapState({
        role: state => state.lesson.courseData.role
      })
    },
    methods: {
      ...mapActions([
        types.LESSON_REMARK
      ]),
      getReview(score, remark) {
        this.reviewScore = score;
        this.reviewRemark = remark;
      },
      submitReviews({ remark } ) {
        this[types.LESSON_REMARK]({
          courseId: this.courseId,
          lessonId: this.reviewCurrentId,
          remark: this.reviewRemark,
          score: this.reviewScore
        }).then((res) => {
          console.log('success')
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
        this.$emit('getDialogShow', this.dialogShow);
      },
    },
  }
</script>