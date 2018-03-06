const signInRoutes = [{
  path: '/course/:courseId/lesson/:lessonId/signIn',
  name: 'signIn',
  component: (resolve) => require(['@/pages/course/signIn/Index.vue'], resolve),
  children: [{
    path: 'signIn/time/:timeId',
      name: 'signInTime',
      component: (resolve) => require(['@/pages/course/signIn/code/Index.vue'], resolve)
    }, {
      path: 'record',
      name: 'signInRecord',
      component: (resolve) => require(['@/pages/course/signIn/record/Index.vue'], resolve)

    }, {
      path: 'time/:timeId/success',
      name: 'signInSuccess',
      component: (resolve) => require(['@/pages/course/signIn/success/Index.vue'], resolve)

    }, {
      path: 'detail',
      name: 'signInDetail',
      component: (resolve) => require(['@/pages/course/signIn/detail/Index.vue'], resolve)

    }, {
      path: 'time/:timeId/result',
      name: 'signInResult',
      component: (resolve) => require(['@/pages/course/signIn/result/Index.vue'], resolve)
    }]
}];

export default signInRoutes;
