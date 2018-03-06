<template>
  <i class="cz-icon" :class="{
    'cz-icon-pptclass': activityType == 'ppt',
    'cz-icon-videoclass': activityType == 'video',
    'cz-icon-flashclass': activityType == 'flash',
    'cz-icon-audioclass': activityType == 'audio',
    'cz-icon-graphicclass': activityType == 'text',
    'cz-icon-filedownload': activityType == 'download',
    'cz-icon-dianmingdati': activityType == 'rollcall',
    'cz-icon-ceyan': (activityType == 'testpaper' || activityType == 'randomTestpaper'),
    'cz-icon-zuoye': activityType == 'homework',
    'cz-icon-zhanshiqiang': activityType == 'displayWall',
    'cz-icon-yijuhuawenda': activityType == 'oneSentence',
    'cz-icon-qiangda': activityType == 'raceAnswer',
    'cz-icon-kejianxiuxi': activityType == 'interval',
    'cz-icon-tounaofengbao': activityType == 'brainStorm',
    'cz-icon-tiaocha': activityType == 'questionnaire',
    'cz-icon-mylibrarybooks': activityType == 'doc' ,
    'cz-icon-practiceWork' : activityType == 'practiceWork',
    'cz-icon-practice' : activityType == 'practice',
  }">
  </i>
</template>

<script>
export default {
  props: ['activityType']
}
</script>
