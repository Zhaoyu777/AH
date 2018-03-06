// import signInRoutes from './signIn';

const courseRoutes = [{
  path: '/course/:courseId',
  component: (resolve) => require(['@/pages/course/Index.vue'], resolve),
  children: [
    {
      path: 'lesson',
      name: 'lesson',
      component: (resolve) => require(['@/pages/course/lesson/Index.vue'], resolve)
    },
    {
      path: 'student-lesson',
      name: 'student-lesson',
      component: (resolve) => require(['@/pages/course/student-lesson/Index.vue'], resolve)
    },
    {
      path: 'student',
      component: (resolve) => require(['@/pages/course/student/Index.vue'], resolve)

    }, {
      path: 'resource',
      component: (resolve) => require(['@/pages/course/resource/Index.vue'], resolve)

    }, {
      path: 'study',
      name: 'study',
      component: (resolve) => require(['@/pages/course/study/Index.vue'], resolve)
    },
    {
      path: 'study?preview=1',
      name: 'preview',
      component: (resolve) => require(['@/pages/course/study/Index.vue'], resolve)
    },
    // ...signInRoutes
  ]
}];

export default courseRoutes;
