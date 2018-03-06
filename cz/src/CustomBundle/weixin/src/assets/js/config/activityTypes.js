/**
 * Created by wubo on 2017/7/27.
 */

// 试听活动类型

export const DISPLAY_WALL = {
  'key': 'displayWall',
  'value': '展示墙'
};

export const BRAIN_STORM = {
  'key': 'brainStorm',
  'value': '头脑风暴'
};

export const ONE_SENTENCE = {
  'key': 'oneSentence',
  'value': '一句话问答'
};

export const RACE_ANSWER = {
  'key': 'raceAnswer',
  'value': '抢答'
};

export const ROLL_CALL = {
  'key': 'rollcall',
  'value': '点名答题'
};

export const QUESTION_NAIRE = {
  'key': 'questionnaire',
  'value': '调查问卷'
};

export const TEST_PAPER = {
  'key': 'testpaper',
  'value': '测验题'
};

export const PRACTICE_WORK = {
  'key': 'practiceWork',
  'value': '实践作业'
};

export const PRACTICE = {
  'key': 'practice',
  'value': '练一练'
};

// 非试听活动的类型
export const PPT = {
  'key': 'ppt',
  'value': 'PPT'
};

export const TEXT = {
  'key': 'text',
  'value': '图文'
};

export const VIDEO = {
  'key': 'video',
  'value': '视频'
};

export const DOC = {
  'key': 'doc',
  'value': '文档'
};

export const AUDIO = {
  'key': 'audio',
  'value': '音频'
};

export const INTERVAL = {
  'key': 'interval',
  'value': '课间活动'
};

export const COLLECT_BEFORE_TASKS = {
  'key': 'collectBeforeTasks',
  'value': '课前活动汇总'
};

export const OLD_ACTIVITY_NAME = {
  [PPT.key]: PPT.value,
  [TEXT.key]: TEXT.value,
  [VIDEO.key]: VIDEO.value,
  [DOC.key]: DOC.value,
  [AUDIO.key]: AUDIO.value,
  [INTERVAL.key]: INTERVAL.value,
  [COLLECT_BEFORE_TASKS.key]: COLLECT_BEFORE_TASKS.value,
};

// 所有活动类型
export const ACTIVITY_NAME = {
  [DISPLAY_WALL.key]: DISPLAY_WALL.value,
  [BRAIN_STORM.key]: BRAIN_STORM.value,
  [ONE_SENTENCE.key]: ONE_SENTENCE.value,
  [RACE_ANSWER.key]: RACE_ANSWER.value,
  [ROLL_CALL.key]: ROLL_CALL.value,
  [QUESTION_NAIRE.key]: QUESTION_NAIRE.value,
  [TEST_PAPER.key]: TEST_PAPER.value,
  [PRACTICE_WORK.key]: PRACTICE_WORK.value,
  [PPT.key]: PPT.value,
  [TEXT.key]: TEXT.value,
  [VIDEO.key]: VIDEO.value,
  [DOC.key]: DOC.value,
  [AUDIO.key]: AUDIO.value,
  [INTERVAL.key]: INTERVAL.value,
  [COLLECT_BEFORE_TASKS.key]: COLLECT_BEFORE_TASKS.value,
  [PRACTICE.key]: PRACTICE.value
};






