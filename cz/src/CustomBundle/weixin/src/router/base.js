const baseRoutes = [{
  path: '/',
  redirect: {
    name: 'courseList'
  }
}, {
  path: '/login',
  name: 'login',
  component: (resolve) => require(['@/pages/Login.vue'], resolve)
}, {
  path: '/noaccount',
  name: 'noAccount',
  component: (resolve) => require(['@/pages/NoAccount.vue'], resolve)
}, {
  path: '/base',
  component: (resolve) => require(['@/pages/base/Index.vue'], resolve),
  children: [
    {
      path: 'course/list',
      meta: {
        // keepAlive: true
      },
      name: 'courseList',
      component: (resolve) => require(['@/pages/base/course-list/Index.vue'], resolve)
    }, {
      path: 'group',
      // 大坑啊
      // meta: {
      //   keepAlive: true
      // },
      name: 'group',
      component: (resolve) => require(['@/pages/base/group/Index.vue'], resolve)
    }, {
      path: 'my',
      meta: {
        // keepAlive: true
      },
      name: 'my',
      component: (resolve) => require(['@/pages/base/my/Index.vue'], resolve)
    }, {
      path: 'mooc',
      name: 'mooc',
      component: (resolve) => require(['@/pages/base/mooc/Index.vue'], resolve)
    }
  ]
}];

export default baseRoutes;
