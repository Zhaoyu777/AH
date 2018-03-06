import socket from '../../../common/socket';

export default class SignResult {
  constructor() {
    this.socket = socket;
  }

  bindSocketEvents() {
    this.on('student attend', (response) => this.getSignResults(response));
  }

  getSignResults(response) {
    let $orignalTr = $('body').find(`.js-signin-user-${response.userId}`),
        orignalStatus = $orignalTr.parents('.tab-pane').attr('id');
        console.log($orignalTr, orignalStatus)
    $orignalTr.remove();
    $(`#${orignalStatus}-students-num`).text(Number($(`#${orignalStatus}-students-num`).text()) - 1)
    $(`#attend-students-table`).prepend(`<tr class="js-signin-user-${response.userId}">
      <td><img class="avatar-xs" src="${response.avatar}" alt="" /></td>
      <td id=${response.id}>${response.truename}</td>
      <td>${response.number}</td>
      <td>${response.signInTime}</td>
      <td>${response.address}</td>
      <td>
        <div class="dropdown">
          <a class="link-primary dropdown-toggle" href="javascript:;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
          操作<span class="caret"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-right sign-status-dropdown">
            <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${response.id}/status/absent/set" data-status='absent'>设为缺勤</a></li>
            <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${response.id}/status/leave/set" data-status='leave'>设为请假</a></li>
            <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${response.id}/status/late/set" data-status='late'>设为迟到</a></li>
            <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${response.id}/status/early/set" data-status='early'>设为早退</a></li>
          </ul>
        </div>
      </td>
    </tr>`);
    $(`#attend-students-num`).text(Number($(`#attend-students-num`).text()) + 1);
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }
}