<template>
  <div class="activity-body">
    <div class="activity-body__title">
      视听活动
    </div>
    <div class="activity-body__content">
      请注意观看大屏幕
    </div>
    <div class="activity-body__icon">
      <i class="cz-icon cz-icon-ceyan"></i>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      role: state => state.activity.activityData.role
    })
  },
}
</script>

<style lang="less">
.activity-body {
  .activity-body__title {
    text-align: center;
    font-size: 1.1875rem;
    color: #4993e9;
    line-height: 2;
  }
  .activity-body__content {
    font-size: 1.1875rem;
    text-align: center;
    line-height: 2;
    margin-bottom: 0.9375rem;
    word-break: break-all;
  }
  .activity-body__icon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 6.25rem;
    height: 6.25rem;
    margin-top: -3.125rem;
    margin-left: -3.125rem;
    i {
      font-size: 6.25rem;
      color: #b0cff2;
      opacity: 0.16;
    }
  }
  .activity-body__warning {
    margin-top: 1.25rem;
    text-align: center;
    font-size: 0.875rem;
    color: #fd4852;
  }
}
</style>
