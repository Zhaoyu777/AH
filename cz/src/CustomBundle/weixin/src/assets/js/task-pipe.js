/**
 * Created by wubo on 2017/8/31.
 */
import postal from "postal";
import "lodash";
import "postal.federation";
import "postal.xframe";
import qs from "qs";
import api from "@/assets/js/api";

class TaskPipe {
  constructor(courseId, taskId, $http) {
    this.$http = $http;
    this.courseId = courseId;
    this.taskId = taskId;
    this.lastTime = parseInt(new Date().getTime() / 1000);
    this.eventDatas = {};
    this.eventMap = {
      receives: {}
    };
    this.postalId = null;
    this._registerChannel();
  }

  _registerChannel() {
    postal.instanceId("task");

    postal.fedx.addFilter([
      {
        channel: "activity-events", //接收 activity iframe的事件
        topic: "#",
        direction: "in"
      },
      {
        channel: "task-events", // 发送事件到activity iframe
        topic: "#",
        direction: "out"
      }
    ]);

    this.postalId = postal.subscribe({
      channel: "activity-events",
      topic: "#",
      callback: ({ event, data }) => {
        this.eventDatas[event] = data;
        this._flush();
      }
    });
    return this;
  }

  removeChannel() {
    postal.unsubscribe(this.postalId);
  }

  _flush() {
    this.$http
      .post(
        api.activity.trigger(this.courseId, this.taskId),
        qs.stringify({
          data: {
            lastTime: this.lastTime,
            events: this.eventDatas
          }
        }),
        {
          headers: {
            "X-Requested-With": "XMLHttpRequest"
          },
          xsrfHeaderName: "X-CSRF-TOKEN",
          emulateJSON: true
        }
      )
      .then(response => {
        console.log("客户端发送了请求：", response, this);
        response = response.data;
        this._publishResponse(response);
        this.eventDatas = {};
        this.lastTime = response.lastTime;
        if (response && response.result && response.result.status) {
          let listeners = this.eventMap.receives[response.result.status];
          if (listeners) {
            listeners.map(listener => {
              listener(response);
            });
          }
        }
      });
  }

  addListener(event, callback) {
    this.eventMap.receives[event] = this.eventMap.receives[event] || [];
    callback && this.eventMap.receives[event].push(callback);
  }

  _publishResponse(response) {
    postal.publish({
      channel: "task-events",
      topic: "#",
      data: { event: response.event, data: response.data }
    });
  }
}

const generatePipe = function(courseId, taskId, $http) {
  return new TaskPipe(courseId, taskId, $http);
};

export { generatePipe };
