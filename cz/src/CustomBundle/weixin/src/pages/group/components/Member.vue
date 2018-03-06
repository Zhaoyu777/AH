<template>
  <div>
    <div class="course-student__header">
      总数： {{ memberData.length }}人
    </div>
    <ul class="course-student-list">
      <li class="course-student-item weui-flex"
          v-for="(item, index) in memberData">
        <img class="course-student__avatar" :src="item.avatar ? host + item.avatar : host + avatarCover">
        <div class="weui-flex__item course-student__name">
          {{ item.nickname }}
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
import { avatarCover } from '@/assets/js/data';
import api from '@/assets/js/api';
import qs from 'qs';

export default {
  props: ['memberData'],
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
    }
  }
}
</script>

<style lang="less">
.course-student__header {
  background: #f3f3f7;
  padding: 1.25rem 0.9375rem;
  font-size: 0.875rem;
  color: #919191;
}
.course-student-list {
  background: #fff;
}

.course-student-item {
  padding: 0.75rem 0.9375rem;
  border-bottom: 0.0625rem solid #f0f0f0;
  line-height: 1.875rem;
}

.course-student__avatar {
  width: 1.875rem;
  height: 1.875rem;
  border-radius: 50%;
  margin-right: 0.625rem;
}
.course-student__name {
  font-size: 1.0625rem;
  color: #313131;
}

</style>