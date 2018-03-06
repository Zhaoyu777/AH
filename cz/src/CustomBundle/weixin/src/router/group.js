const groupRoutes = [{
  path: '/groups',
  name: 'groups',
  component: (resolve) => require(['@/pages/group/Show.vue'], resolve),
  children: [
    {
      path: ':groupId/show',
      name: 'groupShow',
      component: (resolve) => require(['@/pages/group/GroupShow.vue'], resolve)
    }, {
      path: ':groupId/thread/create',
      name: 'createThread',
      component: (resolve) => require(['@/pages/group/CreateThread.vue'], resolve)
    }, {
      path: ':groupId/thread/:threadId',
      name: 'threadDetail',
      component: (resolve) => require(['@/pages/group/GroupDetail.vue'], resolve)
    }, {
      path: 'all',
      name: 'allGroup',
      component: (resolve) => require(['@/pages/group/AllGroup.vue'], resolve)
    }, {
      path: 'myAll',
      name: 'allMyGroup',
      component: (resolve) => require(['@/pages/group/AllMyGroup.vue'], resolve)
    }, {
      path: ':groupId/thread/:threadId/reply',
      name: 'create',
      component: (resolve) => require(['@/pages/group/Create.vue'], resolve)
    },
  ]
}];

export default groupRoutes;
