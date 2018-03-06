import io from 'socket.io-client';
import typed_socket_handler from './typed-socket-handler';
import api from './api';


const getSocket = function (courseId, lessonId) {
  this.$http.get(api.socketPrams(courseId, lessonId))
    .then((response) => {
      if(window.socket) {
        return;
      }

      let tokenParams = response.data.token,
          serverUrl = response.data.serverUrl,
          currentTime = Date.parse(new Date()) / 1000;
      tokenParams.deadline = currentTime + tokenParams.lifetime;

      window.socket = io(serverUrl, {query: {token: tokenParams.token}});

      Object.keys(typed_socket_handler).map((event) => {
        window.socket.off(event);
        window.socket.on(event, typed_socket_handler[event].bind(this));
      });

      window.socket.on('reconnect_attempt', () => {
        console.log('socket重连');
        if (tokenParams && tokenParams.deadline > (Date.parse(new Date()) / 1000)) {
          window.socket.io.opts.query = {token: tokenParams.token};
        } else {
          this.$http.get(api.socketPrams(courseId, lessonId)).then((response) => {
            tokenParams = response.data.token;
            window.socket.io.opts.query = {token: tokenParams.token}
          });
        }
      });
    })
};

const closeSocket = function () {
  window.socket.disconnect();
  window.socket = null;
}


export {getSocket, closeSocket};

