import io from 'socket.io-client';

class TaskSocket {
  constructor(mode) {
    this.mode = mode;
    this.tokenParams = [];
    this.serverUrl = null;
    this.token;
    this.canConnectSocket = $("input[name='canConnectSocket']").val() ? $("input[name='canConnectSocket']").val() : 'true';
    this.socket = {
      on:() => {}
    };

    this.init();
  }

  init() {
    if(this.mode === 'preview') {
      return;
    }

    if (this.canConnectSocket == 'true') {
      this.getToken();
      window.socket = window.socket || io(this.serverUrl, { query: { token: this.token } });
      this.socket = window.socket;
      this.socket.on('connect', () => {
        console.log('已连接');
      });

      this.socket.on('reconnect_attempt', () => {
        this.getToken();
        socket.io.opts.query = {
          token: this.token
        }
        console.log('重新连接中……');
      });
    } else {
      console.log("no connect");
      this.socket = {
        on:() => {}
      }
    }
  }

  getToken() {
    let currentTime = Date.parse(new Date()) / 1000;
    const url = $('#js-socket-params').val();

    if (this.tokenParams && currentTime < this.tokenParams['deadline']) {
      return;
    }
    
    $.get({url: url, async: false}, (response) => {
      this.tokenParams = response.token;
      this.token = this.tokenParams.token;
      this.tokenParams['deadline'] = currentTime + response.token['lifetime'];
      this.serverUrl = response.serverUrl;
    });
  }
}

const taskSocket = new TaskSocket($('#js-hidden-data [name="mode"]', window.parent.document).val());
window.taskSocket = taskSocket;

export default taskSocket.socket;
