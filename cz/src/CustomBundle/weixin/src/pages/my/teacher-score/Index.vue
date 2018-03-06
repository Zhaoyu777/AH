<template>
  <main class="app-main">
    <scroller :on-infinite="infinite" :noDataText="showText">
    <div class="my-total-score">当期积分：{{ credit }}分</div>
    <div class="my-score" v-for="item in myScoreData" v-if="item.score > 0">
      <div class="my-score__title">{{item.remark}}</div>
      <div class="my-score__time">{{ item.updatedTime }}</div>
      <div class="my-score__score"> +{{ item.score }}</div>
    </div>
    </scroller>
  </main>
</template>

<script>
import api from '@/assets/js/api';

export default {
  data() {
    return {
      myScoreData: [],
      credit: 0,
      currentPage: 1,
      pageCount: 1,
      showText: '还未获得积分',
    }
  },
  created() {
  },
  methods: {
    infinite(done) {
      this.$http.get(api.my.teacherScore(), {
          params: {
            page: this.currentPage
          }
        }).then((res) => {
        if (this.pageCount < this.currentPage) {
          if (this.credit > 0) {
            this.showText = '没有更多积分纪录';
          }
          done(true);
          return;
        }
        this.credit = res.data.credit;
        this.pageCount = res.data.pageCount;
        for(let score of res.data.result) {
          this.myScoreData.push(score);
        };
        this.currentPage = this.currentPage + 1;
        done();
      })
    },
  }
}
</script>

<style lang="less" scoped>
@import '~@/assets/less/mixins.less';
.my-total-score {
  background: #e5e5e5;
  padding: 0.9375rem;
  font-size: 0.875rem;
}

.my-score {
  position: relative;
  border-bottom: 0.0625rem solid #e5e5e5;
  padding: 0.625rem 0.9375rem;
}

.my-score__title {
  color: #313131;
  font-size: 0.9375rem;
  margin-bottom: 0.25rem;
  margin-right: 1.875rem;
  line-height: 1.25rem;
  .text-overflow;
}

.my-score__time {
  font-size: 0.75rem;
  color: #919191;
}

.my-score__score {
  position: absolute;
  top: 1.25rem;
  right: 0.9375rem;
  text-align: right;
  color: #4993e9;
  font-size: 1.25rem;
}
</style>

