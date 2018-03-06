import socket from '../../../common/socket';

// 本JS中注释的代码保留到项目上线！！！
export default class signPush {
  constructor() {
    window.attendMembers = window.attendMembers || [];
    this.signTimer = null;
    // this.signMinutes = 45;
    // this.signSeconds = 0;
    this.socket = socket;
    this.memberCount = parseInt($('.js-due-num').text());
  }

  init() {
    // this.initSignTime();
    this.reCalcNum();
    this.bindEvents();
  }

  reCalcNum() {
    const signNum = $('.js-signed-num').text();
    const allStudentNum = parseInt($('.js-all-num').text());
    const unSignNum = allStudentNum - parseInt(signNum);
    $('.js-un-sign-num').text(unSignNum);
  }

  on(event, fun) {
    this.socket.on(event, fun);
  }

  bindEvents() {
    this.on('start first sign in', (response) => this.startFirstSignIn(response));
    this.on('start second sign in', (response) => this.startSecondSignIn(response));
    this.on('cancel sign in', (response) => this.cancelSignIn(response));
    this.on('end sign in', (response) => this.endSignIn(response));
    this.on('set attendance', (response) => this.setStudentStatus(response));
    this.on('student attend', (response) => this.studentAttend(response));
  }

  startFirstSignIn(response) {
    $('.js-start-sign-1').text('发起签到（1）').removeClass('disabled');
    $('.js-result-1').removeClass('hidden');
    $('.js-sign-detail-1').removeClass('hidden');
    $('.js-sign-detail-2').addClass('hidden');
    this.startSign($('.js-start-sign-1'), response);
  }

  startSecondSignIn(response) {
    $('.js-sign-start-2').text('发起签到（2）').removeClass('disabled');
    $('.js-sign-detail-1').addClass('hidden');
    $('.js-sign-detail-2').removeClass('hidden');
    $('.js-result-2').removeClass('hidden');
    this.startSign($('.js-start-sign-2'), response);
  }

  startSign($target, response) {
    // this.resetTime();
    let startSignTime = Date.now() + (45 * 60 * 1000);
    // $target.hide().parent();
    // $('.js-sign-content').show();
    $('.js-store-start-time').attr('data-start-sign', startSignTime);
    // this.stopSignTimer();
    // this.startSignTimer();
    let signInId = response.signIn.id;
    this.memberCount = response.count;

    $target.addClass('hidden');

    $target.text('初始化签到').addClass('disabled');
    $('.js-sign-status').text('正在签到...');
    $('.js-sign-code-num').text(response.code);
    $('.js-sign-code').removeClass('hidden');
    $('.js-signed-num').text(0);
    $('input[name=signInId]').val(response.signIn.id);
    $('.js-all-num').text(response.count); // 总人数

    $('.js-sign-before').hide();
    // $('.js-sign-result').hide();
    $('.js-sign-in').show();

    this.reCalcNum();
  }

  cancelSignIn(response) {
    window.attendMembers = [];

    // $('.js-sign-result').show();
    $(`.js-start-sign-${response.time}`).text(`发起签到（${response.time}）`);
    $(`.js-start-sign-${response.time}`).removeClass('hidden disabled');
    $('.js-sign-status').text('签到');

    $('.js-sign-before').show();
    $('.js-sign-in').hide();
    $('.js-sign-code').addClass('hidden');
    if (response.time == 1) {
      $('.js-result-1').addClass('hidden');
      $('.js-result-2').addClass('hidden');
    } else if (response.time == 2) {
      $('.js-result-1').removeClass('hidden');
      $('.js-result-2').addClass('hidden');
    }
  }

  endSignIn(response) {
    window.attendMembers = [];
    // $('.js-sign-result').show();
    if (response.time == 2) {
      $('.js-sign-status').text('查看签到结果');
    } else {
      $('.js-sign-status').text('签到');
    }

    $(`.js-result-${response.time}`).show();
    $(`.js-start-sign-${parseInt(response.time) + 1}`).removeClass('hidden');
    $('.js-sign-code').addClass('hidden');
    $('.js-sign-before').show();
    $('.js-sign-in').hide();
    // this.stopSignTimer();
    // if (response.time == 1) {
    //   $('.js-sign-start-again').removeClass('hidden');
    // }
    // $('.sign-code').addClass('hidden');
    // this.resetTime();
  };

  setStudentStatus(response) {
    let signInId = $('input[name=signInId]').val();
    if (signInId == response.signinId) {
      let num = parseInt($('.js-signed-num').text());
      let arrIndex = $.inArray(response.userId, window.attendMembers);
      if (response.originStatus != 'attend' && response.status == 'attend') {
        if (arrIndex === -1) {
          window.attendMembers.push(response.userId);
          num++;
        }
      } else if (response.originStatus == 'attend' && response.status != 'attend') {
        if (arrIndex !== -1) {
          window.attendMembers.splice(arrIndex, 1);
        }
        num--;
      }
      if (num > this.memberCount) {
        num = this.memberCount;
      }
      if (num < 0) {
        num = 0;
      }
      $('.js-signed-num').text(num);

      const allStudentNum = parseInt($('.js-all-num').text());
      const unSignNum = allStudentNum - num;
      $('.js-un-sign-num').text(unSignNum);
    }
  }

  studentAttend(response) {
    let signInId = $('input[name=signInId]').val();
    if (signInId == response.signinId) {
      if ($.inArray(response.userId, window.attendMembers) !== -1) {
        return;
      }

      window.attendMembers.push(response.userId);
      let num = parseInt($('.js-signed-num').text());

      num++;
      const allStudentNum = parseInt($('.js-all-num').text());
      const unSignNum = allStudentNum - num;
      $('.js-un-sign-num').text(unSignNum);

      if (num > this.memberCount) {
        num = this.memberCount;
      }
      $('.js-signed-num').text(num);
    }
  }

  // subtractSeconds() {
  //   if (this.signSeconds == 0 && this.signMinutes > 0) {
  //     this.signSeconds = 59;
  //     this.signMinutes --;
  //   } else if (this.signSeconds <= 59 && this.signSeconds > 0 && this.signMinutes >= 0) {
  //     this.signSeconds --;
  //   } else {
  //     // $.post($signEnd.data('url'), {signInId:$('input[name=signInId]').val()}, (data) => {
  //     //   $('.js-sign-in').hide();
  //
  //     //   if (data.time == 1) {
  //     //     $('.js-sign-start-again').removeClass('hidden');
  //     //   }
  //     // });
  //     // stopSignTimer();
  //     // toggleSignStatus();
  //   }
  //
  //   $('.js-sign-rest-minutes').text(`${this.fillNum(this.signMinutes)}`);
  //   $('.js-sign-rest-seconds').text(`${this.fillNum(this.signSeconds)}`);
  // };

  // fillNum(num) {
  //   return (num >= 10) ? num : '0' + num;
  // };

  // resetTime() {
  //   this.signMinutes = 45;
  //   this.signSeconds = 0;
  // };

  // initSignTime() {
  //   if ($('.js-store-start-time').attr('data-start-sign')) {
  //     let now = $('.custom_current_time').attr('data-current-time');
  //     let later = Number($('.js-store-start-time').attr('data-start-sign')) + (45*60);
  //     let signDuration = later - now;
  //     console.log(later);
  //     // if (signDuration < (45*60)) {
  //       // let min = Math.floor(signDuration/60);
  //       // let minRest = signDuration%60;
  //       // let sec = Math.floor(minRest/1000);
  //       // $('.js-sign-rest-minutes').text(`${min}`);
  //       // $('.js-sign-rest-seconds').text(`${minRest}`);
  //       // this.signMinutes = min;
  //       // this.signSeconds = minRest;
  //       // this.startSignTimer();
  //     // } else {
  //     //   $('.js-sign-rest-time').text('签到结束');
  //     // }
  //   }
  // }
}
