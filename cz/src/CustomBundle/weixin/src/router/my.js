const myRoutes = [{
  path: '/my',
  component: (resolve) => require(['@/pages/my/Index.vue'], resolve),
  children: [
    {
    path: 'score',
    meta: {
      // keepAlive: true
    },
    name: 'myScore',
    component: (resolve) => require(['@/pages/my/score/Index.vue'], resolve)
  }, {
    path: 'teacherScore',
    meta: {
      // keepAlive: true
    },
    name: 'myteacherScore',
    component: (resolve) => require(['@/pages/my/teacher-score/Index.vue'], resolve)
  }, {
    path: 'course/list',
    meta: {
      keepAlive: true
    },
    name: 'myCourseList',
    component: (resolve) => require(['@/pages/my/course-list/Index.vue'], resolve)
  }, {
    path: 'course/:courseId',
    name: 'myCourse',
    meta: {
      keepAlive: true
    },
    component: (resolve) => require(['@/pages/my/course/Index.vue'], resolve)
  }, {
    path: 'attend-course/list',
    // meta: {
    //   keepAlive: true
    // },
    name: 'myAttendCourseList',
    component: (resolve) => require(['@/pages/my/attend-course-list/Index.vue'], resolve)
  }, {
    path: 'albums',
    meta: {
      keepAlive: true
    },
    name: 'myAlbums',
    component: (resolve) => require(['@/pages/my/albums/Index.vue'], resolve)
  }]
}];

export default myRoutes;
