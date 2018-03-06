import blurSelect from '../../../common/blur-select';
import SignResult from './sign-in-result';

class signBlurSelect extends blurSelect {
	constructor() {
		super();
    this.selectedStudents = $('#attend-students-table');
	}

	getLiHtml(students) {
    let html = '';
    students.map((student) => {
      html += `<tr><td class="stu-name" id=${student.id} width="25%">${student.truename}</td><td><span hidden>${student.avatar}</span>${student.role}</td><td>${student.nickname}</td></tr>`;
    })
    return html;
  }

  searchEleClick(e) {
    let $targetTr = $(e.target.parentElement);
    let selectHtml = e.target.parentElement.outerHTML;
    let nickname = $targetTr.find('td.stu-name').text();
    let selectId = $targetTr.find('td.stu-name').attr('id');

    this.arr.push(selectId);
    $targetTr.remove();
    $.post($('#student-list').data('url'), {userId:selectId}, (data) => {
      $(`#attend-students-table`).prepend(`<tr>
          <td><img class="avatar-xs" src="${data.avatar}" alt="" /></td>
          <td id=${data.id}>${data.truename}</td>
          <td>${data.nickname}</td>
          <td>${data.updatedTime}</td>
          <td>${data.address}</td>
          <td>
            <a class="link-primary js-delete-single-member" href="javascript:;" data-url="/sign_in/member/${data.id}/delete">
              移除
            </a>
          </td>
        </tr>`);
    })
  }
}

new signBlurSelect().init();
let $attendTable = $('#attend-students-table');

$attendTable.on('click', '.js-delete-single-member', (e) => {
  let target = e.currentTarget;
  if (!confirm(Translator.trans('是否确定移除该学生？'))) {
    return;
  }
  $.post($(target).data('url'), () => {
    $(target).closest('tr').remove();
  })
})

// 签到结果
const signStatusClick = () => {
  let $statusLi = $('.js-sign-status-li');
  $('body').off('click', '.js-sign-status-li');
  $('body').on('click', '.js-sign-status-li', (e) => {
    let $target = $(e.currentTarget);
    let url = $target.data('url');
    let status = $target.data('status');

    $.post(url, (data) => {
      if (data.success == 'error') {
        return;
      }
      $target.closest('tr').remove();

      let $tab = $('.cz-sign-modal-nav').find('.active a');
      let targetName = $tab.attr('aria-controls');
      console.log($(`#${targetName}-students-num`).text());
      $(`#${targetName}-students-num`).text($(`#${targetName}-students-num`).text()-1);

      let signInTime;
      if (status == 'absent') {
        signInTime = '——';
      } else {
        signInTime = data.updatedTime;
      }

      $(`#${status}-students-table`).prepend(`<tr class="js-signin-user-${data.userId}">
          <td><img class="avatar-xs" src="${data.avatar}" alt="" /></td>
          <td id=${data.id}>${data.truename}</td>
          <td>${data.nickname}</td>
          <td>${signInTime}</td>
          <td>${data.address}</td>
          <td>
            <div class="dropdown">
              <a class="link-primary dropdown-toggle" href="javascript:;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
              操作<span class="caret"></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-right sign-status-dropdown">
                <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${data.id}/status/attend/set" data-status='attend'>设为出勤</a></li>
                <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${data.id}/status/absent/set" data-status='absent'>设为缺勤</a></li>
                <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${data.id}/status/leave/set" data-status='leave'>设为请假</a></li>
                <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${data.id}/status/late/set" data-status='late'>设为迟到</a></li>
                <li><a class="js-sign-status-li" href="javascript:;" data-url="/sign_in/member/${data.id}/status/early/set" data-status='early'>设为早退</a></li>
              </ul>
            </div>
          </td>
        </tr>`);
      $(`#${status}-students-table a.js-sign-status-li[data-status = ${status}]`).closest('li').hide();
      // $(`#${status}-students-table`).find("a").attr('data-status', status).closest('li').hide();
      let num = $(`.js-${status}-students-tr-num`).find('tr').length;
      $(`#${status}-students-num`).text(num);
    })
  })
};

signStatusClick();

let signResult = new SignResult();
signResult.bindSocketEvents();
