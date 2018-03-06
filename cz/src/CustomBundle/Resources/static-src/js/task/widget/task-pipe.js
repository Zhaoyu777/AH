import postal from 'postal';
import 'postal.federation';
import 'postal.xframe';

export default class TaskPipe {
  constructor(element) {
    this.element = $(element);
    this.eventUrl = this.element.data('eventUrl');

    if (this.eventUrl === undefined) {
      throw Error('task event url is undefined');
    }

    this.eventDatas = {};
    this.intervalId = null;
    this.lastTime = this.element.data('lastTime');
    this.eventMap = {
      receives: {}
    };

    this._registerChannel();

    if (this.element.data('eventEnable') == 1) {
        this._initInterval();
    }
  }

  _registerChannel() {
    postal.instanceId('task');

    postal.fedx.addFilter([
      {
        channel: 'activity-events', //接收 activity iframe的事件
        topic: '#',
        direction: 'in'
      },
      {
        channel: 'task-events',  // 发送事件到activity iframe
        topic: '#',
        direction: 'out'
      }
    ]);

    postal.subscribe({
      channel: 'activity-events',
      topic: '#',
      callback: ({event, data}) => {
        this.eventDatas[event] = data;
        this._flush();
      }
    });

    return this;
  }

  _initInterval() {
    window.onbeforeunload = () => {
      this._clearInterval();
      this._flush();
    };
    this._clearInterval();
    let minute = 60 * 1000;
    this.intervalId = setInterval(() => this._flush(), minute);
  }

  _clearInterval() {
    clearInterval(this.intervalId);
  }

  _flush() {
    let ajax = $.post(this.eventUrl, {data: {lastTime: this.lastTime, events: this.eventDatas}})
      .done((response) => {
        this._publishResponse(response);
        this.eventDatas = {};
        this.lastTime = response.lastTime;
        if (response && response.result && response.result.status) {
          let listners = this.eventMap.receives[response.result.status];
          if (listners) {
            for (var i = listners.length - 1; i >= 0; i--) {
              let listner = listners[i];
              listner(response);
            }
          }
        }
      })
      .fail((error) => {
      });

    return ajax;
  }

  _publishResponse(response) {
    postal.publish({
      channel: 'task-events',
      topic: '#',
      data: {event: response.event, data: response.data}
    });
  }

  addListener(event, callback) {
    this.eventMap.receives[event] = this.eventMap.receives[event] || [];
    this.eventMap.receives[event].push(callback);
  }

  _changeInterval(eventUrl) {
    this._clearInterval();
    this._setInitInterval(eventUrl);
  }

  _setInitInterval(eventUrl) {
    let minute = 60 * 1000;
    this.intervalId = setInterval(() => this._changeFlush(eventUrl), minute);
  }

  _changeFlush(eventUrl) {
    let ajax = $.post(eventUrl, {data: {lastTime: this.lastTime, events: this.eventDatas}})
      .done((response) => {
        this._publishResponse(response);
        this.eventDatas = {};
        this.lastTime = response.lastTime;
        if (response && response.result && response.result.status) {
          let listners = this.eventMap.receives[response.result.status];
          if (listners) {
            for (var i = listners.length - 1; i >= 0; i--) {
              let listner = listners[i];
              listner(response);
            }
          }
        }
      })
      .fail((error) => {
      });

    return ajax;
  }
}
