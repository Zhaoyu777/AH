<template>
	<div class="after-class-page">
		<div class="after-class-content">
			<p class="tips">本次授课已结束，请给予评价</p>
			<p class="title text-primary">匿名评价</p>
	    <div class="code-btn-list">
	      <div class="code-btn code-btn--lg item"
	            :class="{ 'code-btn--primary': (reviewScore === item.score), 'code-btn--default': (reviewScore !== item.value) }"
	            v-for="item in reviews"
	            @click="getReview(item.score, item.remark)">
	        {{item.remark}}
	      </div>
	    </div>
		</div>
    <button class="weui-btn weui-btn_primary" @click="submitReviews" v-if="reviewRemark">提交</button>
    <button class="weui-btn weui-btn_default" v-if="!reviewRemark" disabled>提交</button>
  </div>
</template>

<script>
import { reviewsData } from '@/assets/js/data';
import { mapState, mapActions, mapMutations } from 'vuex';
import * as types from '@/vuex/mutation-types';

export default {
  data() {
    return {
      reviews: reviewsData,
      reviewScore: 0,
      reviewRemark: null,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId
    };
  },
  computed: {
    ...mapState({
      role: state => state.lesson.courseData.role,
      courseData: state => state.lesson.courseData,
    })
  },
  created() {
    this.fetchData();
  },
  methods: {
    ...mapMutations([
      types.LESSON_REVIEW_CURRENT_ID
    ]),
    ...mapActions([
      types.LESSON_REMARK,
      types.LESSON_INIT, 
      types.LESSON_CLEAR,
    ]),
    fetchData() {
      this[types.LESSON_CLEAR]();
      this[types.LESSON_INIT](this.courseId)
        .then(() => {
          const index = this.findIndex();
          this[types.LESSON_REVIEW_CURRENT_ID](index);
        })
        .catch((response) => {
          this.$endLoading();
          this.$ajaxMessage(response.response.data.message);
        });
    },
    findIndex() {
      const index = this.courseData.lessons.findIndex((elem) => {
        return elem.id === this.lessonId;
      });
      return index;
    },
    getReview(score, remark) {
      this.reviewScore = score;
      this.reviewRemark = remark;
    },
    submitReviews({ remark } ) {
      this[types.LESSON_REMARK]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        remark: this.reviewRemark,
        score: this.reviewScore
      }).then((res) => {
        this.$vux.toast.show({
          type: 'text',
          text: `评价成功`,
          width: '80%'
        });
        this.$router.go(-1);
      }).catch((response) => {
        console.log(response);
        this.$ajaxMessage(response.data.message);
      });
    },
  },
}
</script>

<style type="less" scoped>
/*@import '~@/assets/less/mixins.less';*/

	.after-class-page {
    margin: 0.9375rem 0.625rem;
	}

	.after-class-content {
		margin-bottom: 0.9375rem;
		padding: 2.75rem 3rem;
		text-align: center;
		border-radius: .625rem;
		background-color: #ffffff;
	}

	.tips {
		margin-bottom: 2.5rem;
		font-size: 1.1875rem;
		color: #414141;
	}

	.title {
		margin-bottom: 1.875rem;
		font-size: 1.125rem;
	}

	.item {
		margin: 0 .9375rem .9375rem 0;
	}
</style>