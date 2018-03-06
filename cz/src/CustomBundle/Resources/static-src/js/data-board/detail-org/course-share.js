import CountUp from '../../../common/countUp';

export default class CourseShare {
  constructor() {
    this.sValidator = {};
    this.shareCount;
  }

  init() {
    this.initShareCount();
    this.initValidateRules();
    this.addFormValidator();
    this.initDatetimepickers();
    this.bindClickEvents();
    this.triggerClickEvents();
  }

  bindClickEvents() {
    this.bindDaySearchBtnEvent();
    this.bindWeekSearchBtnEvent();
    this.bindMonthSearchBtnEvent();
    this.bindSearchBtnEvent();
  }

  bindDaySearchBtnEvent() {
    this.shareTimeClick('#btn-share-day', 'startShareTime', 'endShareTime', 'currentDayStart', 'currentDayEnd', 'day');
  }

  bindWeekSearchBtnEvent() {
    this.shareTimeClick('#btn-share-week', 'startShareTime', 'endShareTime', 'currentWeekStart', 'currentWeekEnd', 'week');
  }

  bindMonthSearchBtnEvent() {
    this.shareTimeClick('#btn-share-month', 'startShareTime', 'endShareTime', 'currentMonthStart', 'currentMonthEnd', 'month');
  }

  initShareCount() {
    let shareOptions = {
      useEasing: true,
      useGrouping: true,
      separator: ',',
      decimal: '.',
    };
    this.shareCount = new CountUp('shareNum', 0, 0, 0, 3, shareOptions);
    if (!this.shareCount.error) {
      this.shareCount.start();
    } else {
      console.error(this.shareCount.error);
    };
  }

  bindSearchBtnEvent() {
    $('#btn-share-search').on('click', (event) => {
      if (this.sValidator.form()) {
        var $self = $(event.currentTarget);
        $('.share-data-nav-tabs').find('a').removeClass('active');

        this.fetchDatas();
      } else {
        return;
      }
    })
  }

  triggerClickEvents() {
    $('#btn-share-month').click();
  }

  initDatetimepickers() {
    $("[name=startShareTime]").datetimepicker({
      language: document.documentElement.lang,
      autoclose: true,
      format: 'yyyy-mm-dd',
      minView: 'month'
    }).on('changeDate', function(e) {
      $('[name=endShareTime]').datetimepicker('setStartDate', e.date);
    });
    $('[name=startShareTime]').datetimepicker('setEndDate', $('[name=endShareTime]').val().substring(0, 16));

    $("[name=endShareTime]").datetimepicker({
      language: document.documentElement.lang,
      autoclose: true,
      format: 'yyyy-mm-dd',
      minView: 'month'
    }).on('changeDate', function(e) {
      $('[name=startShareTime]').datetimepicker('setEndDate', e.date);
    });
    $('[name=endShareTime]').datetimepicker('setStartDate', $('[name=startShareTime]').val().substring(0, 16));
  }

  initValidateRules() {
    $.validator.addMethod("share_date_check", function(element) {
      var startShareTime = $('[name=startShareTime]').val();
      var endShareTime = $('[name=endShareTime]').val();
      startShareTime = startShareTime.replace(/-/g,"/");
      startShareTime = Date.parse(startShareTime)/1000;
      endShareTime = endShareTime.replace(/-/g,"/");
      endShareTime = Date.parse(endShareTime)/1000;

      if (endShareTime >= startShareTime) {
        return true;
      }else{
        return false;
      }
    }, Translator.trans('开始时间必须小于或等于结束时间'));
  }

  addFormValidator() {
    var $shareForm = $('#data-share-form');

    this.sValidator = $shareForm.validate({
      groups: {
        nameGroup: 'startShareTime endShareTime'
      },
      rules: {
        startShareTime: 'required share_date_check',
        endShareTime: 'required share_date_check',
      }
    })
  }

  shareTimeClick(element, startTime, endTime, currentTimeStart, currentTimeEnd, type) {
    $(element).on("click", (event) => {
      var $self = $(event.currentTarget);
      if (!$self.hasClass('active')) {
        $self.addClass('active').siblings().removeClass('active');
      }

      $("[name=" + startTime + "]").val($(element).attr(currentTimeStart));
      $("[name=" + endTime + "]").val($(element).attr(currentTimeEnd));

      this.fetchDatas();
    })
  }

  fetchDatas() {
    $.get($('#data-share-form').attr('action'), $('#data-share-form').serialize(), (count) => {
      console.log(count);
      this.shareCount.update(count);
    })
  }
}