<template>
  <main class="app-main" v-if="studentData">
    <scroller :on-infinite="infinite" :noDataText="showText">
      <div class="course-student__header">
        总数： {{ studentCount }}人
      </div>
      <ul class="course-student-list">
        <li class="course-student-item weui-flex" v-for="(item, index) in studentData">
          <span class="course-student__icon" :class="{
              'course-student__icon-1': index == 0,
              'course-student__icon-2': index == 1,
              'course-student__icon-3': index == 2 }">
            <i class="cz-icon cz-icon-crown"></i>
            <span class="course-student__rank">{{ index + 1 }}</span>
          </span>
          <img class="course-student__avatar" :src="host + item.avatar">
          <div class="weui-flex__item course-student__name">
            {{ item.truename }}
          </div>
          <span class="course-student__scores">{{ item.credit }}积分</span>
        </li>
      </ul>
    </scroller>
  </main>
</template>

<script>
import api from '@/assets/js/api';
import qs from 'qs';

export default {
  data() {
    return {
      host: this.$getCookie('host'),
      studentData: [],
      studentCount: 0,
      showText: '',
      courseId: this.$route.params.courseId,
      currentPage: 1
    }
  },
  methods: {
    infinite(done) {
      this.$http.get(api.course.students(this.courseId), {
          params: {
            page: this.currentPage
          }
        }).then((res) => {
        document.title = res.data.courseTitle; 
        this.studentCount = res.data.count;
        if (res.data.count === 0) {
          this.showText = '该班级无学生';
        }
        if (res.data.paging.total + 1 == this.currentPage) {
          done(true)
          return;
        }
        for(let member of res.data.members) {
          this.studentData.push(member);
        };
        console.log(this.studentData);
        this.currentPage = this.currentPage + 1;
        done();
      })
    },
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

.course-student__icon {
  position: relative;
  margin-right: 0.9375rem;
  margin-top: 0.0625rem;
  i {
    font-size: 1.625rem;
  }
  &.course-student__icon-1 {
    i {
      color: #f5bc23;
    }
  }
  &.course-student__icon-2 {
    i {
      color: #4993e9;
    }
  }
  &.course-student__icon-3 {
    i {
      color: #f48029;
    }
  }
}
.course-student__rank {
  position: absolute;
  top: 0.1875rem;
  left: 0;
  right: 0;
  text-align: center;
  color: #fff;
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
.course-student__scores {
  color: #919191;
}
</style>
