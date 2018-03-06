<template>
  <div class="activity-body" v-if="role == 'teacher'">
    <div class="activity-body__title" v-if="activityData.duration > 0">
      参考时长
    </div>
    <div class="activity-body__content" v-if="activityData.duration > 0">
      {{ activityData.duration }}分钟
    </div>
    <div class="activity-body__title" v-if="activityData.about">
      教学说明
    </div>
    <div class="activity-body__content" v-if="activityData.about">
      {{ activityData.about }}
    </div>
    <div class="activity-body__warning">
      视听活动，请在PC端控制播放
    </div>
    <div class="activity-body__icon" v-if="!activityData.about">
      <i class="cz-icon cz-icon-ceyan"></i>
    </div>
  </div>
  <div class="activity-body" v-else-if="role == 'student'">
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
