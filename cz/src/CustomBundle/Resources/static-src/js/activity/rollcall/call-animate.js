import lodash from 'lodash';
import notify from 'common/notify';
import socket from '../../../common/socket';

class callAnimate {
  constructor(options) {
    this.callBtn = options.callBtn;
    this.callAnimateWrap = options.callAnimateWrap;
    this.selectedContent = options.selectedContent;
    this.socket = socket;
    this.interval = null;
    this.studentHtml = null;
    this.studentList = null;
    this.selectedName = null;
    this.selectedNumber = null;
    this.starting = false;
  }

  init() {
    this.initEvent();
  }

  initEvent() {
    this.callBtn.on('click', () => {
      this.ajaxAnswer();
    });

    this.socket.on('rand rollcall start', (response) => this.randRollcallStart(response));
    this.socket.on('task result remark', (response) => this.taskResultRemark(response));
  }

  stopAnimate() {
    clearInterval(this.interval);
    this.callBtn.removeAttr('disabled').html('随机点名');
  }

  addHtml() {
    if (this.selectedName) {
      this.callAnimateWrap.html(`<span class="select-name">${this.selectedName}, ${this.selectedNumber}</span>`).fadeOut(5000);
    }
    if (this.studentHtml) {
      this.selectedContent.prepend(this.studentHtml);
    }
    this.selectedName = null;
    this.studentHtml = null;
  }

  ajaxAnswer() {
    let self = this;
    self.callBtn.attr('disabled', 'disabled').html('点名中...');
    $.ajax({
      url: self.callBtn.data('url'),
      type: 'POST',
      success(res) {
        if (res.message) {
          notify('danger', res.message);
          self.callBtn.html('随机点名');
          $('.js-rollcall-stu-num').css('right', '240px');
          return false;
        }
      }
    })
  }

  startCall() {
    if (!(this.callBtn.hasClass('disabled'))) {
      this.callBtn.addClass('disabled').html('点名中...');
    }
    this.setScroll();
    this.callAnimateWrap.after('<video controls="controls" autoplay="autoplay" src="/assets/music/call-animate.mp3" hidden></video>')
  }

  setScroll() {
    this.interval = setInterval(() => this.startAnimate(), 100);
  }

  startAnimate() {
    if (this.studentList) {
      let randNum = Math.floor(Math.random() * this.studentList.length);
      this.studentList.map((val, index) => {
        if (index == randNum) {
          this.callAnimateWrap.html(`<span class="select-name"><img class="avatar-sm mrm" src="${val.avatar}">${val.truename}<span>`).show();
        }
      })
    }
  }

  randRollcallStart(response) {
    if(this.starting) {
      return true;
    }

    if (response.taskId != $('#taskId').val()) {
      return;
    }

    this.starting = true;
    this.studentList = response.studentList;

    let $backImg = $(".js-activity-animate");
    let backImgStyle = $backImg.css('display');
    if (backImgStyle !== 'none') {
      $backImg.css('display', 'none');
    }

    this.startCall();
    this.initSelectStudentData(response);

    lodash.debounce(() => {
      this.stopAnimate();
      this.addHtml();
      this.starting = false;
    }, 3000)();
  }

  initSelectStudentData(res) {
    this.studentHtml = `
      <div class="called-stu-info pull-left inline-block mrl text-center js-called-stu-info">
        <img class="avatar-lg mbl" src="${res.result.avatar}" alt="">
        <p class="color-orange text-24 text-overflow">${res.result.truename}</p>
        <p class="gray-dark text-18 mbl text-overflow">${res.result.number}</p>
        <a class="btn cz-link-primary cz-btn-md text-bold js-grade-btn" href="javascript:;" data-target="#modal" data-toggle="modal" data-url="/rollcall/result/${res.result.resultId}/remark">评分</a>
        <span class="js-score-show color-primary text-24" id="${res.result.resultId}"></span>
      </div>
    `
    this.selectedName = res.result.truename;
    this.selectedNumber = res.result.number;
  }

  taskResultRemark(response) {
    if (response.taskId != $('#taskId').val()) {
      return;
    }
    $('#' + response.resultId).html(`+${response.score}分`).parent().find('a').addClass('hidden');
  }
}

export default callAnimate;