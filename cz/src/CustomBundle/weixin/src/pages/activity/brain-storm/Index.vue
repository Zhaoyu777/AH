<template>
  <div>
    <div class="activity-body">
      <div class="activity-body__title">
        问题
      </div>
      <div class="activity-body__content" v-html="activityData.activityContent">
      </div>
    </div>
    <action v-if="isAjaxEnd" :courseId="courseId" :lessonId="lessonId" :taskId="taskId"></action>
    <random-action
      v-if="!brainStorm.hasGroup && brainStorm.status == 'start' && role == 'student'"
      :groups="groupList" @randomJoin='randomJoin'>
    </random-action>
    <fixed-person v-if="brainStorm.submitWay === 'person'"></fixed-person>
    <fixed-group v-else-if="brainStorm.submitWay === 'group'"></fixed-group>
  </div>
</template>

<script>
import { XDialog } from 'vux';
import { mapState, mapMutations, mapActions } from 'vuex';
import api from '@/assets/js/api';
import * as types from '@/vuex/mutation-types';
import Action from './BrainStormAction';
import FixedPerson from './FixedPerson';
import FixedGroup from './FixedGroup';
import RandomAction from '@/components/RandomAction';

export default {
  components: {
    Action,
    FixedPerson,
    FixedGroup,
    RandomAction
  },
  computed: {
    ...mapState({
      activityData: state => state.activity.activityData,
      brainStorm: state => state.activity.brainStorm,
      role: state => state.activity.activityData.role,
      groups: state=> state.activity.brainStorm.groups
    }),
    groupList() {
      const menus = {};
      if (this.groups && this.groups.length) {
        this.groups.map((group) => {
          menus[group.groupId] = group.title;
        });
      }
      menus['title.noop'] = `<div style="color:#313131">请选择一组加入</div><div style="color:#616161;font-size:.75rem;">加入后无法更改哦</div>`
      return menus;
    },
  },
  data() {
    return {
      isAjaxEnd: false,
      taskId: this.$route.params.taskId,
      courseId: this.$route.params.courseId,
      lessonId: this.$route.params.lessonId
    }
  },
  created() {
    this.fetchData();
  },
  beforeRouteLeave (to, from, next) {
    next();
  },
  beforeRouteUpdate (to, from, next) {
    this[types.BRAIN_STORM_CLEAR]();
    this.updateData(to.params);
    this.fetchData();
    next();
  },
  beforeDestroy() {
    this[types.BRAIN_STORM_CLEAR]();
  },
  methods: {
    ...mapMutations([
      types.BRAIN_STORM_CLEAR
    ]),
    ...mapActions([
      types.BRAIN_STORM_INIT,
      types.BRAIN_STORM_CLEAR,
      types.BRAIN_STORM_RANDOM_JOIN
    ]),
    fetchData() {
      if (this.brainStorm.status !== 'end') {
        this[types.BRAIN_STORM_INIT](this.taskId).then(() => {
          this.isAjaxEnd = true;
        }).catch((response) => {
          this.$ajaxMessage(response.response.data.message);
        });
      }
    },
    updateData(data) {
      this.taskId = data.taskId;
      this.courseId = data.courseId;
      this.lessonId = data.lessonId;
      this.isAjaxEnd = false;
    },
    randomJoin(item) {
      this[types.BRAIN_STORM_RANDOM_JOIN](item).then(() => {
        this.$vux.toast.show({
          text: '分组成功'
        });
      });
    }
  }
}

</script>

<style lang='less'>
@import '~@/assets/less/variables.less';
@import '~@/assets/less/mixins.less';
@import '~@/assets/less/module/activity-body.less';
@import '~@/assets/less/module/student-list.less';
@import '~@/assets/less/module/wall-group.less';

.wall-content__body {
  margin-left: 0;
  margin-right: 0;
  margin-bottom: .625rem;
}
.activity-student__assist {
  text-align: right;
  .code-tag {
    display: inline-block;
    width: 1.875rem;
  }
}
</style>
