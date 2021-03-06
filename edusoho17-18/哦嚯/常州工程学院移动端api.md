日期：2017-7-24 黄昭宇0.0

## 一，公共接口

### 1.获取当前用户信息
url:/weixin/current_user

请求方式：git

输入：

输出：输出实例

```json
{
    role: 当前用户角色
    csrf_token: 表单提交所需token
    host: 域名
}
```

### 2.获取微信jsjdk连接参数

url:/weixin/current_user

请求方式：git

输入：

输出：输出实例

```json
{
    appId: 企业微信应用ID
    nonceStr: 随机六位字符串
    timestamp: 但前时间戳
    signature：企业微信签名
}
```

### 3.微信图片上传（展示墙）

url:/weixin/picture/download

请求方式：git

输入：media_id, taskId

输出：输出实例

{
    uri: 图片路径（服务器端）
}

### 4.图片上传（小组）

url:/weixin/upload/image

请求方式：git

输入：file

输出：输出实例

{
    data：{
        uri: 图片路径（服务器端）
    }
    
}

## 二，课程接口

### 1.课程列表-教师端

url: /weixin/teachingCourses

请求方式：get

输入：

输出：输出实例

```json
{
  "courses":[
    0: {
      courseSetTitle:"光照万物",
      courseTitle: "班级哦阿",
      cover: null,
      id: "1741"(班级id),
      isLesson: 1/0(是否备课),
      taskNum: 1(未完成任务数),
    }
  ]
}
```

### 2.课程列表-学生端

url: /weixin/learningCourses

请求方式：get

输入：

输出：输出实例

```json
{
  courses:[
    0:{
      courseSetTitle:"光照万物",
      courseTitle: "班级哦阿",
      cover: null,
      id: "1741"(班级id),
      isLesson: 是否备课(1/0),
      taskNum: 未完成(任务数)
    }
  ]
}
```

### 3.课程资源

url: /weixin/course/${courseId}/resources

请求方式：git

输入：

输出：输出实例

```json
{
  files:[
    0:{
      type: video,
      file: [
        0: {
          cover:""
          id:"27"
          title:"小1.mp4"
        }    
      ]
    }
  ]
}
```

### 4.课程学生

url: /weixin/course/${courseId}/students

请求方式：git

输入：

输出：输出实例

```json
{
  count: 1,
  members: [
    0: {
      avatar:"",
      credit:"2",
      nickname:"黄zy-8000000901",
      number:"8000000901",
      truename:"黄zy",
    }
  ]
  paging: {
    limit:20,
    page:"1",
    total:1,
  }
}
```

### 5.完整课程

url: /weixin/course/${courseId}/lessons

请求方式：git

输入：

输出：输出实例

```json
{
  courseSetTitle:"光照万物"
  courseTitle:"班级哦阿"
  cover:""
  role:"teacher"
  currentTask:[]
  lessons:[
    0: {
      id:"26966"
      isEvaluation:false
      isShowPhase:false
      number:"1"
      status:"teached"
      title:"课次1"
      after:[]
      before:[]
      in:[
        0: {
          activityType:"oneSentence"
          courseId:"1741"
          id:"727"
          isVisible:"end"
          lessonId:"26966"
          status:false
          taskId:"727"
          taskType:"lesson"
          title:"一句话问答"
        }
      ]
    }
  ]
}
```

### 6.学习课程详情

url: /weixin/course/${courseId}/study

请求方式：git

输入：

输出：输出实例

```json
{
  courseTitle:"班级哦阿"
  cover:null
  id:"26968"
  lessonNumber:"3"
  lessonStatus:"teaching"
  lessonTitle:null
  role:"teacher"
  signIn:{status: null, time: 0}
  termCode:"16-17-2"
  after:[]
  before:[]
  in:[
    0:{
      activityType:"doc"
      courseId:"1741"
      id:"841"
      lessonId:"26968"
      status:false
      taskId:"833"
      taskType:"lesson"
      title:"文档-4"
    }
  ]
  currentTask:{
    activityId:"851"
    activityTitle:"课间休息-6"
    activityType:"interval"
    lessonId:"26968"
    lessonTitle:"课次3"
    taskId:"843"
  }
}
```

### 7.开始上课

url: /weixin/course/lesson/start

请求方式：git

输入：lessonId

输出：输出实例

```json
{
  lessonStatus: teaching
  next: {
      taskId:1
      activityId:1
      activityType:doc
  }
}
```

### 8.下课

url: /weixin/course/lesson/end

请求方式：git

输入：lessonId

输出：输出实例

```json
{
    true
}
```

### 9.取消上课

url: /weixin/course/lesson/cancel

请求方式：git


输出：输出实例

```json
{
    true
}
```

### 10.学生对课次评价

url:/weixin/course/${courseId}/lesson/${lessonId}/evaluation

请求方式：git

输入：remark， score

输出：输出实例

```json
{
  true
}
```

## 三，签到模块
### 1.老师开始签到

url:/weixin/lesson/${lessonId}/start

请求方式：git

输入：

输出：输出实例

```json
{
  code:"0163"
  count:1
  signIn:{
    createdTime:"1500867821"
    id:"133"
    lessonId:"26968"
    status:"start"
    time:"1"
    updatedTime:"1500867821"
    verifyCode:"0163"
  }
}
```

### 2.签到详情-教师端

url:/weixin/lesson/${lessonId}/sign_in/detail

请求方式：git

输入：

输出：输出实例

```json
{
  lessonTitle:"课次3："
  signIn:{
    code:"0163"
    createdTime:"1500867821"
    currentTime:1500868159
    id:"133"
    status:"start"
    surplusTime:1
    time:"1"
  }
}
```

### 3.结束签到

url:/weixin/sign_in/${signInId}/end

请求方式：git

输入：

输出：输出实例

```json
{
   status:end 
}
```

### 4.签到结果

url:/weixin/lesson/${lessonId}/time/${timeId}/result

请求方式：git

输入：

输出：输出实例

```json
{
  attendCount:1
  memberCount:1
  members:{
    absent:[]
    attend:[
      0:{
        address:"浙江省杭州市滨江区明德路"
        avatar:""
        createdTime:"1500867821"
        id:"3545"
        lat:"30.1816"
        lessonId:"26968"
        lng:"120.1529"
        nickname:"黄zy-8000000901"
        opUserId:null
        signinId"133"
        status:"attend"
        time:"1"
        type:"default"
        updatedTime:"1500868052"
        userId:"31747"
      }
    ]
    early:[]
    late:[]
    leave:[]
  }
}
```

### 5.修改学员签到状态

url:/weixin/sign_in/member/${memberId}/set_status

请求方式：git

输入：status

输出：输出实例

```json
{
  attendCount:1
  memberCount:1
  members:{
    absent:[]
    attend:[
      0:{
        address:"浙江省杭州市滨江区明德路"
        avatar:""
        createdTime:"1500867821"
        id:"3545"
        lat:"30.1816"
        lessonId:"26968"
        lng:"120.1529"
        nickname:"黄zy-8000000901"
        opUserId:null
        signinId"133"
        status:"attend"
        time:"1"
        type:"default"
        updatedTime:"1500868052"
        userId:"31747"
      }
    ]
    early:[]
    late:[]
    leave:[]
  }
}
```

### 6.签到-学生

url:/weixin/lesson/${lessonId}/time/${timeId}/student/sign_in

请求方式：git

输入：code(签到码)

输出：输出实例

```json
{
  true
}
```

### 7.签到详情

url:/weixin/lesson/${lessonId}/time/${timeId}/student/sign_in/status

请求方式：git

输入：

输出：输出实例

```json
{
  status:"absent"
}
```

### 8.签到记录

url:/weixin/course/${courseId}/sign_in/record

请求方式：git

输入：

输出：输出实例

```json
{
  lesson:"课次3"
  updateTime:"1500877080"
  address:null
}
```

## 四，任务活动接口

### 1.任务详情
url:/weixin/course/${courseId}/lesson/${lessonId}/activity/${activityId}/show

请求方式：git

输入：taskId

输出：输出实例

```json
{
  about:"图文的教学说明"
  activityContent:"<p>额鹅鹅鹅</p>↵"
  activityNumber:21
  activityStatus:"start"
  activityTitle:"图文-7"
  activityType:"text"
  duration:"7"
  id:"842"
  lessonNumber:"3"
  lessonTitle:null
  role:"teacher"
  stage:"in"
  status:"teaching"
  next:{activityId: "843", activityType: "audio", taskId: "835"}
  up:{activityId: "841", activityType: "doc", taskId: "833"
}
```

### 2.任务完成情况
url:/course/${courseId}/task/${taskId}/trigger

请求方式：git

输入：

输出：输出实例

```json
{
  event:"doing"
  lastTime:1500878112
  result:{
    activityId:"842"
    courseId:"1741"
    courseTaskId:"834"
    createdTime:"1500878052"
    finishedTime:"0"
    id:"1330"
    status:"start"
    time:"0"
    updatedTime:"1500878052"
    userId:"2"
    watchTime:"0"
  }
}
```

### 3.加入随机分组
url:/weixin/task/${taskId}/random_group/${groupId}/join

请求方式：git

输入：

输出：输出实例

```json
{
  status:"start"
}
```

### 4.活动开始
url:/weixin/course/${courseId}/lesson/${lessonId}/task/${taskId}/start

请求方式：git

输入：

输出：输出实例

```json
{
  status："start"
}
```

### 5.活动结束
url:/weixin/course/{courseId}/lesson/{lessonId}/task/{taskId}/end

请求方式：git

输入：

输出：输出实例

```json
{
  status："end"
}
```

## 五，我的模块

### 1.我的
url:/weixin/my

请求方式：git

输入：

输出：输出实例

```json
{
  avatar:"/files/user/2017/05-06/143734ec0d04054904.jpg?version=8.0.21"
  credit:"49"
  nickname:"测试管理员"
  truename:"测试管理员"
}
```

### 2.我的积分
url:/weixin/my/score

请求方式：git

输入：

输出：输出实例

```json
{
  credit:16
  result:[
    0:{
      courseTitle:课程
      activityTitle:"huohuo"
      updateTime:"2017-05-08 09:44:41"
      score:"1"
    }
  ]
}
```

### 3.我的教师积分
url:/weixin/my/teacher/score

请求方式：git

输入：

输出：输出实例

```json
{
  credit:"49"
  result:[
    0:{
      createdTime:"2017-07-04 10:00:29"
      remark:"课程 12,课次9,任务创建"
      score:"1"
    }
  ]
}
```

### 4.我的在教课程
url:/weixin/my/courses/teaching

请求方式：git

输入：

输出：输出实例

```json
[
  0:{
    courseTitle:"默认教学计划"
    cover:null
    id:"1738"
    title:"77q"
  }
]
```

### 4.我的在学课程
url:/weixin/my/courses/learning

请求方式：git

输入：

输出：输出实例

```json
{
  course:{
    [
      0:{
        courseSetTitle:"光照万物",
        courseTitle: "班级哦阿",
        cover: null,
        id: "1741"(班级id),
        isLesson: 1/0(是否备课),
        taskNum: 1(未完成任务数),  
      }
    ]
  }
}
```

### 4.我的相册
url:/weixin/my/albums

请求方式：git

输入：

输出：输出实例

```json
[
  0:{
    id:"1"
    uri:null
    month:"今天"
    day:"6"
    likeNum:"11"
    postNum:"11"
  }
]
```

## 六，学校圈（小组）

### 1.热门小组

url:/weixin/groups

请求方式：get

输入：type=hot

输出：

```json
{
  date:[
    0:{
        about:"<p>啦啦啦奥绿</p>↵"
        backgroundLogo:"/assets/img/default/background_group.jpg?version=8.0.21"
        id:"1"
        logo:"/assets/img/default/group.png?version=8.0.21"
        memberNum:"2"
        postNum:"0"
        status:"open"
        threadNum:"0"
        title:"圈圈"
    }
  ]
  paging:{
    limit:10
    page:1
    total:1
  }
}
```

### 2.我的小组

url:/weixin/groups

请求方式：get

输入：

输出：

同上

### 3.话题

url:/weixin/group/${groupId}/threads

请求方式：get

输入：page,limit, groupid=0为热门话题

输出：

```json
{
  date:[
    0:{
        avatar:"/files/user/2017/05-06/143734ec0d04054904.jpg?version=8.0.21"
        content:"<p>123321</p>"
        groupId:"1"
        groupTitle:"圈圈"
        hitNum:"0"
        id:"1"
        nickname:"测试管理员"
        postNum:"0"
        timeStr:"5秒前"
        title:"1123"
    }
  ]
  paging:{
    limit:10
    page:1
    total:1
  }
}
```

### 4.创建话题

url:/weixin/group/${groupId}/create/thread

请求方式：post

输入：

输出：

```json
{
  id:1,
  title:"lalalala",
  content:"lolololo",
  groupId:"5"
}
```

### 5.小组成员

url:/weixin/group/${groupId}/members

请求方式：get

输入：page,limit

输出：

```json
{
  date:[
    0:{
        groupId:"2"
        id:"3"
        nickname:"测试管理员"
        truename:"测试管理员"
    }
  ]
  paging:{
    limit:10
    page:1
    total:1
  }
}
```

### 6.话题详情

url:/weixin/group/${groupId}/thread/${threadId}/detail

请求方式：get

输入：

```json
{
  content:"<p>321</p>↵"
  hitNum:"5"
  postNum:"2"
  timeStr:"17分钟前"
  title:"123"
}
```

### 7.话题回复列表

url:/weixin/group/${groupId}/thread/${threadId}/posts

请求方式：get

输入：page,limit

```json
{
  date:[
    0:{
        avatar:"/files/user/2017/05-06/143734ec0d04054904.jpg?version=8.0.21"
        childPosts:[]
        content:"<p>123123231</p>"
        id:"1"
        nickname:"测试管理员"
        timeStr:"10秒前"
        truename:"测试管理员"
    }
  ]
  paging:{
    limit:10
    page:1
    total:1
  }
}

childPosts[
  0:{
    avatar:"/files/user/2017/05-06/143734ec0d04054904.jpg?version=8.0.21"
    content:"<p>123123231</p>"
    id:"1"
    nickname:"测试管理员"
    timeStr:"10秒前"
    truename:"测试管理员"
  }
]
```

### 8.发出评论和回复

url:/weixin/group/${groupId}/thread/${threadId}/post

请求方式：post

输入：

```json
{
  adopt:"0"
  content:"<p>qweewq</p>"
  createdTime:"1500884085"
  fromUserId:"0"
  id:"3"
  postId:"0"
  threadId:"2"
  userId:"2"
}
```

### 9.小组头部信息

url:/weixin/group/${groupId}/detail

请求方式：get

输入：

```json
{
  about:"<p>小鸡顿蘑菇</p>↵"
  backgroundLogo:"/assets/img/default/background_group.jpg?version=8.0.21"
  id:"2"
  isMember:true
  logo:"/assets/img/default/group.png?version=8.0.21"
  memberNum:"1"
  postNum:"3"
  status:"open"
  threadNum:"1"
  title:"天王盖地虎"
}
```

### 10.加入小组

url:/weixin/group/${groupId}/join

请求方式：get/post

输入：

```json
{
  true
}
