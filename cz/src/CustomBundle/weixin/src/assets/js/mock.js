import Mock from 'mockjs';
Mock.mock('/weixin/task/50/activity/50/questionnaire_show', {
  "status": "start",
  "questions|3-6": [
      {
          "id|1-100": 31,
          "type|1": ["single_choice", "choice", 'essay'],
          "stem": "@cparagraph()",
          // "metas": [
          //     "董建斌1",
          //     "谢希予1",
          //     "屁哥1",
          //     "张平1"
          // ],
          "value": '',
          "itemCount": {
              "0": {
                'text': 'aaa',
                'num': 50,
                'part': '20%'
              },
              "1": {
                'text': 'bbb',
                'num': 10,
                'part': '10%'
              },
              "2": {
                'text': 'ccc',
                'num': 150,
                'part': '60%'
              },
              "3": {
                'text': 'ddd',
                'num': 100,
                'part': '20%'
              }
          }
      }
  ],
  "questionnaire": {
      "id": "10",
      "title": "这个问卷很酷炫哟",
      "courseSetId": "978",
      "description": "贼棒棒哟",
      "itemCount": "3", //问题个数
  },
  "questionTypes": [
      "single_choice",
      "choice",
      "essay"
  ] //问题类型
});

Mock.mock('/questionnaire/result/undefined/finish', {
  okay: 123
});

Mock.mock('/weixin/list/result', {
  "pages": {
    "page": 0,
    "num": 5,
    "members|5-10": [
      {
        avatar: null,
        credit: "2",
        nickname: "薛黎清-9070000051",
        truename: "薛黎清"
      },
      {
        avatar: null,
        credit: "0",
        nickname: "薛黎清-9070000052",
        truename: "薛黎清1"
      },
      {
        avatar: null,
        credit: "0",
        nickname: "ccc762093577",
        truename: "ccc762093577"
      }
    ]
  }
})

var Random = Mock.Random;

Random.extend({
    questionTypes: function(type) {
        var questionTypes = ['single_choice', 'choice', 'essay'];
        return this.pick(questionTypes);
    },
    sex: function(date) {
        var sexes = ['男', '女', '中性', '未知'];
        return this.pick(sexes);
    }
});


