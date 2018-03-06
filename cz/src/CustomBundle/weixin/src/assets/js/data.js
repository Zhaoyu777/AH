// 默认底部菜单
export const menuData = [
  {
    type: 'link',
    url: '/base/course/list',
    iconClass: 'cz-icon cz-icon-lesson',
    labelTitle: '课程'
  },
  {
    type: 'link',
    url: '/base/mooc',
    iconClass: 'cz-icon cz-icon-lesson',
    labelTitle: '慕课'
  },
  {
    type: 'link',
    url: '/base/group',
    iconClass: 'cz-icon cz-icon-quanzi',
    labelTitle: '学校圈'
  },
  {
    type: 'link',
    url: '/base/my',
    iconClass: 'cz-icon cz-icon-person',
    labelTitle: '我的'
  }
];

// 老师端 - 课程详情底部菜单
export const courseMenuData = (id, role) => {
  const data = [
  {
    type: 'link',
    url: `/course/${id}/study`,
    iconClass: 'cz-icon cz-icon-pencil',
    labelTitle: role === 'teacher' ? '上课' : '学习'
  },
  {
    type: 'link',
    url: `/course/${id}/resource`,
    iconClass: 'cz-icon cz-icon-folder',
    labelTitle: '资源'
  },
  {
    type: 'link',
    url: `/course/${id}/student`,
    iconClass: 'cz-icon cz-icon-group',
    labelTitle: '成员'
  },
  {
    type: 'link',
    url: `/course/${id}/lesson`,
    iconClass: 'cz-icon cz-icon-viewlist',
    labelTitle: '完整课程'
  }
  ];

  return data;
};

// 学生端 - 课程详情底部菜单
export const studentCourseMenuData = (id) => {
  const data = [
    {
      type: 'link',
      url: `/course/${id}/student-lesson`,
      iconClass: 'cz-icon cz-icon-list',
      labelTitle: '目录'
    },
    {
      type: 'link',
      url: `/course/${id}/resource`,
      iconClass: 'cz-icon cz-icon-Resources',
      labelTitle: '资源'
    },
    {
      type: 'link',
      url: `/course/${id}/student`,
      iconClass: 'cz-icon cz-icon-member',
      labelTitle: '成员'
    }
  ];
  return data;
};

// 活动
export const activityMenuData = ({ role, courseId, lessonId, ingLessonId, lessonStatus, up, next }) => {
  const data = [{
    type: up.activityId ? 'link' : null,
    url: `/course/${courseId}/lesson/${lessonId}/task/${up.taskId}/activity/${up.activityId}/type/${up.activityType}`,
    iconClass: 'cz-icon cz-icon-chevronleft',
    labelTitle: up.activityId ? '上一活动' : '无上一活动'
  },
  {
    type: next.activityId ? 'link' : null,
    url: `/course/${courseId}/lesson/${lessonId}/task/${next.taskId}/activity/${next.activityId}/type/${next.activityType}`,
    iconClass: 'cz-icon cz-icon-chevronright',
    labelTitle: next.activityId ? '下一活动' : '无下一活动'
  }, {
    type: 'link',
    url: `/course/${courseId}/study?lessonId=${lessonId}`,
    iconClass: 'cz-icon cz-icon-viewlist',
    labelTitle: '所有活动'
  }];

  if (role === 'teacher' && lessonStatus === 'teaching' && lessonId === ingLessonId) {
    data.push({
      iconClass: 'cz-icon cz-icon-shangxiake',
      labelTitle: '下课',
      type: 'lessonEnd'
    });
  } else if (role === 'teacher' && lessonStatus === 'teached' && lessonId === ingLessonId) {
    data.push({
      iconClass: 'cz-icon cz-icon-shangxiake',
      labelTitle: '已下课',
      type: null
    });
  }

  return data;
};

// 评价数据 学生对老师
export const reviewsData = [
  {
    remark: '很满意',
    score: '5',
  },
  {
    remark: '较满意',
    score: '4',
  },
  {
    remark: '一般',
    score: '3',
  },
  {
    remark: '较不满意',
    score: '2',
  },
  {
    remark: '不满意',
    score: '1',
  }
];

// 评价数据 老师对学生
export const reviewsStudentData = {
  remarks: [
    {
      value: '积极',
    },
    {
      value: '认真努力',
    },
    {
      value: '回答得好',
    },
    {
      value: '专注',
    },
    {
      value: '帮助他人',
    }
  ],
  scores: [
    {
      value: 1,
    },
    {
      value: 2,
    },
    {
      value: 3,
    }
  ]
};

//试卷题目类型
export const testpaperTypes = {
  single_choice: '单选题',
  choice: '多选题',
  essay: '问答题',
  uncertain_choice: '不定项选择',
  determine: '判断题',
  fill: '填空题',
  material: '材料题',
}

export const testpaperResultStatus = {
  unpassed: '不合格',
  passed: '合格',
  good: '良好',
  excellent: '优秀',
}


export const courseCover = '/assets/img/default/courseSet.png';
export const avatarCover = '/assets/img/default/avatar.png';
export const resourceCover = '/static/img/resource.jpg';
export const bulidingCover = '/static/img/building.png';
export const raceCover = '/static/img/race.png';
export const groupBg = '/static/img/background_group.jpg';
export const screenCover = '/static/img/screen.png';
export const signBtnCover = '/static/img/sign_btn.png';
export const signIconCover = '/static/img/sign_icon.png';
export const activityIcon = '/static/img/activity-icon.png';
export const activityIcon2 = '/static/img/activity-icon@2x.png';
export const oldActivityIcon = '/static/img/before-activity-icon.png';
export const oldActivityIcon2 = '/static/img/before-activity-icon@2x.png';

