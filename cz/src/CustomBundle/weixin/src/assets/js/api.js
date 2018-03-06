const api = {
  // 获取用户信息
  currentUserInfo() { return '/weixin/current_user'; },
  hostName() { return '/weixin/host_name'; },
  // 用户登录
  userLogin() { return '/weixin/user_login'; },
  // 图片下载
  mediaDownload() { return '/weixin/picture/download'; },
  practiceWorkPictureUpload() { return '/weixin/practice_work/picture/upload'; },
  // 获取位置信息-第三方
  // getAddress() { return '/mapapis/ws/geocoder/v1/'; },
  // 验证微信jssdk接口
  jsjdk() { return '/weixin/jssdk'; },
  //图片上传
  uploadImage() { return '/weixin/upload/image'; },
  //socket token && params
  socketPrams(courseId, lessonId) { return `/weixin/instant/course/${courseId}/lesson/${lessonId}/push_params`; },

  course: {
    info(courseId) { return `/weixin/current/course/${courseId}/role`; },

    // 课程列表-教师端
    teaching() { return '/weixin/teachingCourses'; },
    // 课程列表-学生端
    learning() { return '/weixin/learningCourses'; },
    // 课程资源
    resources(courseId) { return `/weixin/course/${courseId}/resources`; },
    // 课程学生
    students(courseId) { return `/weixin/course/${courseId}/students`; },
    // 完整课程
    lessons(courseId) { return `/weixin/course/${courseId}/lessons/complete`; },
    // 学习
    study(courseId) { return `/weixin/course/${courseId}/study`; },

    // 开始上课
    start() { return '/weixin/course/lesson/start'; },
    // 下课
    end() { return '/weixin/course/lesson/end'; },
    // 取消上课
    cancel() { return '/weixin/course/lesson/cancel'; },
    //student comment
    evaluation(courseId, lessonId) { return `/weixin/course/${courseId}/lesson/${lessonId}/evaluation`; },
  },

  signIn: {
    // 老师开始签到
    start(lessonId) { return `/weixin/lesson/${lessonId}/start`; },
    // 签到详情-教师端
    detail(lessonId) { return `/weixin/lesson/${lessonId}/sign_in/detail`; },
    // 结束签到
    end(signInId) { return `/weixin/sign_in/${signInId}/end`; },
    // 签到结果
    result(lessonId, timeId) { return `/weixin/lesson/${lessonId}/time/${timeId}/result`; },
    // 修改学员签到状态
    setStatus(memberId) { return `/weixin/sign_in/member/${memberId}/set_status`; },

    // 签到-学生
    signIn(lessonId, timeId) { return `/weixin/lesson/${lessonId}/time/${timeId}/student/sign_in`; },
    // 签到详情
    status(lessonId, timeId) { return `/weixin/lesson/${lessonId}/time/${timeId}/student/sign_in/status`; },
    // 签到记录
    record(courseId) { return `/weixin/course/${courseId}/sign_in/record`; },
    // 签到成功
    success(lessonId, timeId) { return `/weixin/lesson/${lessonId}/time/${timeId}/student/sign_in/success`; },
  },

  activity: {

    // 任务详情
    detail(courseId, lessonId) { return `/weixin/course/${courseId}/lesson/${lessonId}/task/show`; },

    // 任务完成情况
    trigger(courseId, taskId) { return `/course/${courseId}/task/${taskId}/trigger`; },

    //随机分组
    join(taskId, groupId) { return `/weixin/task/${taskId}/random_group/${groupId}/join`; },

    //活动开始
    start(courseId, lessonId, taskId) { return `/weixin/course/${courseId}/lesson/${lessonId}/task/${taskId}/start` },

    end(courseId, lessonId, taskId) { return `/weixin/course/${courseId}/lesson/${lessonId}/task/${taskId}/end` }
  },

  displayWall: {
    // 展示墙详情
    result(taskId) { return `/weixin/display_wall/task/${taskId}/result`; },
    end(taskId, activityId) { return `/weixin/task/${taskId}/activity/${activityId}/display_wall/end` },
    // 展示墙作品点赞
    like(contentId) { return `/weixin/display_wall/content/${contentId}/like`; },
    // 展示墙作品取消点赞
    cancelLike(contentId) { return `/weixin/display_wall/content/${contentId}/cancel_like`; },
    // 展示墙作品评分
    remark(resultId, groupWay, submitWay) { return `/weixin/display_wall/result/${resultId}/group_way/${groupWay}/submit_way/${submitWay}/remark`; },
    // 展示墙作品详情
    content(contentId) { return `/weixin/display_wall/content/${contentId}/show`; },
    // 展示墙作品评论
    post(contentId) { return `/weixin/display_wall/content/${contentId}/post`; },
  },

  practiceWork: {
    //实践作业
    result(taskId) { return `/weixin/practice_work/task/${taskId}/result` },
  },

  practice: {
    // 结果集
    result(taskId) { return `/weixin/practice/task/${taskId}/result` },
    // 点赞
    like(contentId) { return `/weixin/practice/content/${contentId}/like` },
    // 取消点赞
    cancelLike(contentId) { return `/weixin/practice/content/${contentId}/cancel_like` },
    // 详情
    content(contentId) { return `/weixin/practice/result/content/${contentId}/show` },
    // 评论
    post(contentId) { return `/weixin/practice/content/${contentId}/post` }, // post请求：courseId, resultId, score, remark
    // 评分
    remark(resultId) { return `/weixin/practice/result/${resultId}/remark` },
  },

  rollcall: {
    // 点名答题详情-学生
    status() { return '/weixin/instant/course/student/task/status'; },
    // 点名
    rand() { return '/weixin/instant/course/student/rand'; },
    // 点名评分
    remark() { return '/weixin/instant/course/teacher/task/remark/result'; },
    // 被点名的学生列表
    students() { return '/weixin/instant/course/teacher/task/rollcall/student'; },
  },

  oneSentence: {
    //一句话问答结束按钮
    end(taskId, activityId) { return `/weixin/task/${taskId}/activity/${activityId}/one_sentence/end`; },

    answer(taskId) { return `/weixin/task/${taskId}/one_sentence/answer`; },

    result(taskId) { return `/weixin/task/${taskId}/one_sentence/result`; }
  },

  raceAnswer: {
    end(taskId, activityId) { return `/weixin/task/${taskId}/activity/${activityId}/race_answer/end`; },

    race(courseId, taskId, activityId) { return `/weixin/course/${courseId}/task/${taskId}/activity/${activityId}/race_answer/race`; },

    result(taskId) { return `/weixin/task/${taskId}/race_answer/result`; },

    remark(courseId, resultId) { return `/weixin/course/${courseId}/result/${resultId}/race_answer/remark_result`; },
  },

  questionNaire: {
    result(taskId, activityId) { return `/weixin/task/${taskId}/activity/${activityId}/questionnaire_show`; },

    submit(resultId) { return `/weixin/questionnaire/result/${resultId}/finish`; }
  },

  brainStorm: {
    end(courseId, lessonId, taskId) { return `/weixin/course/${courseId}/lesson/${lessonId}/task/${taskId}/end`; },

    answer(taskId) { return `/weixin/task/${taskId}/brain_storm/answer`; },

    result(taskId) { return `/weixin/task/${taskId}/brain_storm/result`; },

    remark(resultId) { return `/weixin/brain_storm/result/${resultId}/remark`; }
  },

  testpaper: {
    result(taskId) {
      return `/weixin/task/${taskId}/testpaper/result`;
    },

    start(taskId) {
      return `/weixin/task/${taskId}/testpaper/do`;
    },

    submit(resultId) {
      return `/weixin/testpaper/result/${resultId}/finish`;
    },

    statis(taskId) {
      return `/weixin/task/${taskId}/testpaper/result/statis`;
    }
  },

  randomTestpaper: {
    result(taskId) {
      return `/weixin/task/${taskId}/random_testpaper/result`;
    },

    redo(taskId) {
      return `/weixin/task/${taskId}/random_testpaper/redo`;
    },

    submit(taskId) {
      return `/weixin/task/${taskId}/random_testpaper/submit`;
    }
  },

  my: {
    // 我的
    index() { return '/weixin/my'; },
    // 我的积分
    score() { return '/weixin/my/score'; },
    // 我的教师积分
    teacherScore() { return '/weixin/my/teacher/score'; },
    // 我的在教课程
    teachingCourses() { return '/weixin/my/courses/teaching'; },
    // 我的在学课程
    learningCourses() { return '/weixin/my/courses/learning'; },
    // 我的相册
    albums() { return '/weixin/my/albums'; },
  },

  group: {
    // 热门小组
    groups() { return '/weixin/groups'; },
    // 我的小组
    myGroups() { return '/weixin/my/groups'; },
    // 近期话题
    // groupid=0,热门话题
    topics(groupId) { return `/weixin/group/${groupId}/threads`; },
    // 创建话题
    create(groupId) { return `/weixin/group/${groupId}/create/thread`; },
    // 小组成员
    members(groupId) { return `/weixin/group/${groupId}/members`; },
    // 话题详情
    detail(groupId, threadId) { return `/weixin/group/${groupId}/thread/${threadId}/detail`; },

    posts(groupId, threadId) { return `/weixin/group/${groupId}/thread/${threadId}/posts`; },
    // 评论和回复
    post(groupId, threadId) { return `/weixin/group/${groupId}/thread/${threadId}/post`; },
    // 小组头部信息
    headDetail(groupId) { return `/weixin/group/${groupId}/detail`; },
    // 加入小组
    join(groupId) { return `/weixin/group/${groupId}/join`; },
  },

  studentTask: {
    // 某课程当前进行的课次和活动
    current(courseId) { return `/weixin/student/course/${courseId}/in/task` },

    // 任务详情
    detail(courseId, taskId) { return `/weixin/student/course/${courseId}/task/${taskId}` },
  }
};

export default api;