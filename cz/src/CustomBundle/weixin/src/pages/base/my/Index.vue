<template>
  <main class="app-main" v-if="myData">
    <div class="my-header weui-flex">
      <img :src="myData.avatar ? host + myData.avatar : host + avatarCover" class="code-avatar code-avatar--lg my-header__avatar" alt="">
      <div class="weui-flex__item">
        <div class="my-header__name">
          {{ myData.truename ? myData.truename : myData.nickname }}
          <span v-if="role === 'teacher'">老师</span>
        </div>
        <div class="my-header__score">总积分：{{ myData.credit }}</div>
      </div>
    </div>
    <group class="my-list">
      <cell :title="role === 'student' ? '在学课程' : '在教课程'" is-link :link="{name: 'myCourseList'}"><i class="cz-icon cz-icon-tag-book" slot="icon"></i></cell>
      <cell title="我的旁听课" is-link :link="{ name: 'myAttendCourseList' }" v-if="role === 'teacher'"><i class="cz-icon cz-icon-tag-book" slot="icon"></i></cell>
      <cell title="相册" is-link :link="{ name: 'myAlbums' }" v-if="role === 'student'"><i class="cz-icon cz-icon-xiangce" slot="icon"></i></cell>
      <cell title="积分明细" is-link :link="{name: 'myScore'}"  v-if="role === 'student'"><i class="cz-icon cz-icon-label" slot="icon"></i></cell>
      <cell title="教师积分明细" is-link :link="{name: 'myteacherScore'}"  v-if="role === 'teacher'"><i class="cz-icon cz-icon-label" slot="icon"></i></cell>
      <cell title="收藏" is-link v-if="role === 'none'"><i class="cz-icon cz-icon-favorite" slot="icon"></i></cell>
    </group>
  </main>
</template>

<script>
import { Group, Cell } from 'vux';
import { avatarCover } from '@/assets/js/data';
import api from '@/assets/js/api';

export default {
  components: {
    Group,
    Cell
  },
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
      role: this.$getCookie('role'),
      myData: null
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$isLoading();
      this.$http.get(api.my.index()).then((response) => {
        this.$endLoading();
        this.myData = response.data;
        console.log(response.data);

      }, (response) => {
        this.$endLoading();
        this.$ajaxError();
      });
    }
  }
}

</script>

<style lang="less">
.my-header {
  margin-bottom: 0.9375rem;
  background: #fff;
  padding: 1.125rem 1.25rem;
}

.my-header__avatar {
  margin-right: 0.9375rem;
}

.my-header__name {
  font-size: 0.9375rem;
  color: #313131;
  margin-top: 0.625rem;
  margin-bottom: 0.625rem;
}

.my-header__score {
  font-size: 0.8125rem;
  color: #4993e9;
}

.my-list {
  .weui-cells {
    &:before,
    &:after {
      border-color: #e5e5e5;
    }
  }
  .weui-cell {
    &:before {
      border-color: #e5e5e5;
      left: 0;
    }
  }
  i {
    margin-right: 0.625rem;
    color: #919191;
  }
}
</style>
