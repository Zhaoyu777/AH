const learningActivityRoutes = [
  {
    path: '/course/:courseId/lesson/:lessonId/after-class',
    component: (resolve) => require(['@/pages/learning-activity/after-class/Index.vue'], resolve),
    name: 'after-class',
  },
  {
    path: '/course/:courseId/learning/lesson/:lessonId/task/:taskId/activity/:activityId',
    component: (resolve) => require(['@/pages/learning-activity/Index.vue'], resolve),
    name: 'learning-activity',
    children: [{
      path: 'type/video',
      name: 'learning-video',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/ppt',
      name: 'learning-ppt',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/audio',
      name: 'learning-audio',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/doc',
      name: 'learning-doc',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/text',
      name: 'learning-text',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/download',
      name: 'learning-download',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/testpaper',
      name: 'learning-testpaper',
      component: (resolve) => require(['@/pages/learning-activity/testpaper/Index.vue'], resolve)
    }, {
      path: 'type/homework',
      name: 'learning-homework',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/practiceWork',
      name: 'learning-practiceWork',
      component: (resolve) => require(['@/pages/learning-activity/practice-work/Index.vue'], resolve)
    }, {
      path: 'type/interval',
      name: 'learning-interval',
      component: (resolve) => require(['@/pages/learning-activity/base-type/Index.vue'], resolve)
    }, {
      path: 'type/rollcall',
      name: 'learning-rollcall',
      component: (resolve) => require(['@/pages/learning-activity/rollcall/Index.vue'], resolve)
    }, {
      path: 'type/displayWall',
      name: 'learning-displayWall',
      component: (resolve) => require(['@/pages/learning-activity/display-wall/Index.vue'], resolve)
    }, {
      path: 'type/oneSentence',
      name: 'learning-oneSentence',
      component: (resolve) => require(['@/pages/learning-activity/one-sentence/Index.vue'], resolve)
    }, {
      path: 'type/raceAnswer',
      name: 'learning-raceAnswer',
      component: (resolve) => require(['@/pages/learning-activity/race-answer/Index.vue'], resolve)
    }, {
      path: 'type/questionnaire',
      name: 'learning-questionnaire',
      component: (resolve) => require(['@/pages/learning-activity/question-naire/Index.vue'], resolve)
    }, {
      path: 'type/brainStorm',
      name: 'learning-brainStorm',
      component: (resolve) => require(['@/pages/learning-activity/brain-storm/Index.vue'], resolve),
    }, {
      path: 'type/randomTestpaper',
      name: 'learning-randomTestpaper',
      component: (resolve) => require(['@/pages/learning-activity/random-testpaper/Index.vue'], resolve),
    }, {
      path: 'type/practice',
      name: 'learning-practice',
      component: (resolve) => require(['@/pages/learning-activity/practice/Index.vue'], resolve),
    }]
  }, {
    path: '/course/:courseId/lesson/:lessonId/task/:taskId/activity/:activityId/type/displayWall/:contentId',
    name: 'displayWallContent',
    component: (resolve) => require(['@/pages/learning-activity/display-wall/detail/Index.vue'], resolve)
  },
  {
    path: '/course/:courseId/lesson/:lessonId/task/:taskId/activity/:activityId/type/practice/:contentId',
    name: 'practiceContent',
    component: (resolve) => require(['@/pages/learning-activity/practice/detail/Index.vue'], resolve)
  }
];

export default learningActivityRoutes;
