
const activityRoutes = [{
  path: '/course/:courseId/lesson/:lessonId/task/:taskId/activity/:activityId',
  component: (resolve) => require(['@/pages/activity/Index.vue'], resolve),
  children: [
    {
      path: 'type/video',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/ppt',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/audio',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/doc',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/text',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/download',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/testpaper',
      component: (resolve) => require(['@/pages/activity/testpaper/Index.vue'], resolve)
    }, {
      path: 'type/homework',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/practiceWork',
      component: (resolve) => require(['@/pages/activity/practice-work/Index.vue'], resolve)
    }, {
      path: 'type/interval',
      component: (resolve) => require(['@/pages/activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/rollcall',
      component: (resolve) => require(['@/pages/activity/rollcall/Index.vue'], resolve)
    }, {
      path: 'type/displayWall',
      component: (resolve) => require(['@/pages/activity/display-wall/Index.vue'], resolve)
    }, {
      path: 'type/oneSentence',
      component: (resolve) => require(['@/pages/activity/one-sentence/Index.vue'], resolve)
    }, {
      path: 'type/raceAnswer',
      component: (resolve) => require(['@/pages/activity/race-answer/Index.vue'], resolve)
    }, {
      path: 'type/questionnaire',
      component: (resolve) => require(['@/pages/activity/question-naire/Index.vue'], resolve)
    }, {
      path: 'type/brainStorm',
      component: (resolve) => require(['@/pages/activity/brain-storm/Index.vue'], resolve),
    }, {
      path: 'type/randomTestpaper',
      component: (resolve) => require(['@/pages/activity/random-testpaper/Index.vue'], resolve),
    }, {
      path: 'type/practice',
      component: (resolve) => require(['@/pages/activity/practice/Index.vue'], resolve),
    }
  ]
}];

export default activityRoutes;
