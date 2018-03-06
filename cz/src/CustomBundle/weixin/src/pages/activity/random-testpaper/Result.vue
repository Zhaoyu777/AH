<template>
  <div>
    <div class="activity-content">
      <div class="testpaper-result__title">考试结果</div>
      <table class="testpaper-result__table">
        <thead>
          <tr>
            <th width="35%" style="text-align: left;">题型</th>
            <th width="30%" style="text-align: center;">答对题</th>
            <th width="35%" style="text-align: right;">总分</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(one, type) in accuracy">
            <td style="text-align: left;">{{ types[type] }}</td>
            <td style="text-align: center;">
              <span class="text-primary">{{ one.right }}</span>/{{ one.all }}
            </td>
            <td style="text-align: right;">
              <span class="text-primary">{{ one.score }}</span>/{{ one.totalScore }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="result-summary">
      <div class="result-summary__title">
        总分
      </div>
      <div class="result-summary__status">
        <span class="text-warning" v-if="result.status == 'unpassed'">不合格</span>
        <span class="text-primary" v-else>合格</span>
      </div>
      <div>
        (
        <span class="result-summary__realscore">
          {{result.score}}
        </span>
        <span>
          合格分数线：
        </span>
        <span class="text-warning">{{ result.passedScore}}</span>
        )
      </div>

    </div>

  </div>
</template>

<script>
import { XDialog } from 'vux';

import { mapState, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';
import { testpaperTypes } from '@/assets/js/data';
import { testpaperResultStatus } from '@/assets/js/data';

export default {
  components: {

  },
  computed: {
    ...mapState({
      result: state => state.activity.randomTestpaper.result,
      accuracy: state => state.activity.randomTestpaper.accuracy
    })
  },
  data() {
    return {
      types: testpaperTypes
    }
  },
  created() {
  },
  beforeRouteLeave(to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    next();
  },
  methods: {
    ...mapActions([

    ])
  }
}
</script>

<style lang="less">
@import '~@/assets/less/module/activity-body.less';
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';

.testpaper-result__title {
  font-size: 1.1875rem;
  color: #2B333B;
  text-align: center;
  font-family: PingFangSC-Medium;
}

.testpaper-result__table {
  width: 100%;
  font-size: .875rem;
  color: #2B333B;
  border-collapse: collapse;
  thead th {
    font-family: PingFangSC-Medium;
    padding: 1.65625rem 1.15625rem 0 .4375rem;
  }
  tbody {
    tr:not(:last-child) td {
      border-bottom: .03125rem solid #F0F0F0;
      font-family: PingFangSC-Regular;
      padding: 1.4375rem 1.15625rem 1.4375rem .4375rem;
    }
    tr:last-child td {
      padding: 1.4375rem 1.15625rem 1.4375rem .4375rem;
      font-family: PingFangSC-Regular;
    }
  }
}

.result-summary {
  text-align: center;
  border-radius: 0.625rem;
  background: #fff;
  padding: 1.25rem;
  margin: 0.9375rem 0.625rem;
  .result-summary__title {
    font-size: .9375rem;
    color: #2B333B;
    font-family: PingFangSC-Medium;
  }

  .result-summary__realscore {
    font-size: .625rem;
    color: #919191;
  }

  .result-summary__status {
    font-size: 1.1875rem;
    font-family: PingFangSC-Regular;
    margin: 1.25rem;
  }

  .result-summary__notify {
    font-family: PingFangSC-Regular;
    font-size: .75rem;
    color: #919191;
    margin-bottom: 1.5625rem;
  }
}

</style>
