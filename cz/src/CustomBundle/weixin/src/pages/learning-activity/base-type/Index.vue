<template>
  <div class="activity-body activity-body--sm" v-if="isAudioVisual === false">
    <iframe
        :src="'/course/'+ courseId + '/task/' + taskId + '/activity_show'"
        style="width:100%;height:100%;border:none"
        allowfullscreen webkitallowfullscreen>
    </iframe>
  </div>
  <audio-visual v-else-if="isAudioVisual === true " :activityData="activityData"></audio-visual>
</template>

<script>
import api from '@/assets/js/api';
import AudioVisual from './AudioVisual';
import {mapState, mapActions} from 'vuex';
import * as types from '@/vuex/mutation-types';
import qs from 'qs';

export default {
  data() {
    return {
      host: this.$getCookie('host'),
      isAudioVisual: null,
      interval: null,
      taskPipe: null,
      lastTime: parseInt(Date.now() / 1000)
    }
  },
  props: ['courseId', 'taskId'],
  components: {
    AudioVisual
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData
    })
  },
  beforeRouteLeave (to, from, next) {
    clearInterval(this.interval);
    this.taskPipe.removeChannel();
    next();
  },
  beforeRouteUpdate (to, from, next) {
    clearInterval(this.interval);
    this.taskPipe.courseId = to.params.courseId;
    this.taskPipe.taskId = to.params.taskId;
    this.taskPipe.lastTime = parseInt(Date.now() / 1000);
    next();
  },
  created() {
    this.fetchData();
    this.taskPipe = this.$generatePipe(this.courseId, this.taskId, this.$http);
  },
  methods: {
    ...mapActions([types.ACTIVITY_INIT]),
    trigger() {
      this.$http.post(api.activity.trigger(this.courseId, this.taskId),
        qs.stringify({
          data: {
            lastTime: this.lastTime
          }
        }), {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          xsrfHeaderName: 'X-CSRF-TOKEN',
          emulateJSON: true

        }).then((res) => {
        this.lastTime = res.data.lastTime
      })
    },
    fetchData() {
      this.lessonId = this.$route.params.lessonId;
      this.activityId = this.$route.params.activityId;
      this[types.ACTIVITY_INIT]({
        courseId: this.courseId,
        lessonId: this.lessonId,
        taskId: this.taskId,
        activityId: this.activityId,
      }).then((res) => {
        if (res.data.activityType === 'testpaper') {
          this.interval = setInterval(() => this.trigger(), 60000);
          this.isAudioVisual = false;
          return;
        }
        let audioVisualTypes = ['video', 'audio', 'interval', 'ppt', 'text', 'doc'];
        this.isAudioVisual = false;

        if (res.data.status === 'teaching' && res.data.stage === 'in') {
          for (let index = 0; index < audioVisualTypes.length; index++) {
            if (res.data.activityType === audioVisualTypes[index]) {
              this.isAudioVisual = true;
              break;
            }
          }
        }

        if(this.isAudioVisual === false) {
          this.interval = setInterval(() => this.trigger(), 60000);
        }
      }).catch((response) => {
        this.$endLoading();
        this.$ajaxMessage(response.data.message);
      })
    },
  }
}
</script>

