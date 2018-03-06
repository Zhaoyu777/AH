define(function(require, exports, module) {
  require('jquery-plugin/jquery.lavalamp/jquery.lavalamp.js');
  require("jquery.bootstrap-datetimepicker");
  var Validator = require('bootstrap.validator');
  require('common/validator-rules').inject(Validator);
  require('echarts');
  require('jquery.form');

  var now = new Date();

  exports.run = function () {

    teachCourseDate();
    registerSwitchEvent();
    registerClickShow();
    analysisSearchClick();
    searchBtnClick('#btn-search', teachCourseDate);
    $('#btn-share-day').click();
    $('#js-analysis-search').click();

    if ($(".nav.nav-tabs").length > 0) {
      $(".nav.nav-tabs").lavaLamp();
    };

  }

  var registerSwitchEvent = function () {
    DataSwitchEvent('.cz-js-switch-button');
  }

  var registerClickShow = function() {
    dataClickShow('#btn-day', 'startTime', 'endTime', 'currentDayStart', 'currentDayEnd', '#btn-search', teachCourseDate);
    dataClickShow('#btn-week', 'startTime', 'endTime', 'currentWeekStart', 'currentWeekEnd', '#btn-search', teachCourseDate);
    dataClickShow('#btn-month', 'startTime', 'endTime', 'currentMonthStart', 'currentMonthEnd', '#btn-search', teachCourseDate);
    shareTimeClick('#btn-share-day', 'startShareTime', 'endShareTime', 'currentDayStart', 'currentDayEnd', 'day');
    shareTimeClick('#btn-share-week', 'startShareTime', 'endShareTime', 'currentWeekStart', 'currentWeekEnd', 'week');
    shareTimeClick('#btn-share-month', 'startShareTime', 'endShareTime', 'currentMonthStart', 'currentMonthEnd', 'month');
    $('#btn-share-search').on('click', function() {
      $.get($('#share-form').attr('action'), $('#share-form').serialize(), function(count) {
        $('#share-num').text(count);
      })
    });
  }

  var shareTimeClick = function (element, startTime, endTime, currentTimeStart, currentTimeEnd, type) {
    $(element).on("click",function(){
      $("[name=" + startTime + "]").val($(element).attr(currentTimeStart));
      $("[name=" + endTime + "]").val($(element).attr(currentTimeEnd));

      $.get($('#share-form').attr('action'), $('#share-form').serialize(), function(count) {
        $("#share-num").text(count);
      })
    })
  }

  var teachCourseDate = function(data) {
    this.element = $("#study-source-statistic");
    var chart = echarts.init(this.element.get(0));
    chart.showLoading();
    return $.get(this.element.data('url'), $('#resource-form').serialize(), function(datas) {
      var option = {
        tooltip: {
          show: true
        },
        grid: {
          show: false
        },
        xAxis: {
          type: 'category',
          boundaryGap: true,
          data: datas.sourceTypes,
          axisTick: {
            alignWithLabel: true,
          }
        },
        yAxis: {
            type: 'value'
        },
        series: [{
          name: '数量',
          type: 'bar',
          data: datas.sourceCounts,
          barMaxWidth: 70,
          itemStyle: {
            normal: {
              color: function(params) {
                let colorList = ['#C1232B','#B5C334','#FCCE10','#E87C25'];
                return colorList[params.dataIndex]
              }
            }
          }
        }]
      };
      chart.hideLoading();
      chart.setOption(option);
    })
  }

  Validator.addRule('share_date_check', function() {
    var startTime = $('[name=startShareTime]').val();
    var endTime = $('[name=endShareTime]').val();
    startTime = startTime.replace(/-/g,"/");
    startTime = Date.parse(startTime)/1000;
    endTime = endTime.replace(/-/g,"/");
    endTime = Date.parse(endTime)/1000;

    if (endTime >= startTime) {
        return true;
    }else{
        return false;
    }
    Translator.trans('开始时间必须小于或等于结束时间')
  });

  $("[name=startTime]").datetimepicker({
    autoclose: true,
    format: 'yyyy-mm-dd',
    minView: 'month'
  }).on('changeDate', function(e) {
    $('[name=endTime]').datetimepicker('setStartDate', e.date);
  });
  $('[name=startTime]').datetimepicker('setEndDate', $('[name=endTime]').val().substring(0, 16));

  $("[name=startShareTime]").datetimepicker({
    autoclose: true,
    format: 'yyyy-mm-dd',
    minView: 'month'
  }).on('changeDate', function(e) {
    $('[name=endShareTime]').datetimepicker('setStartDate', e.date);
  });
  $('[name=startShareTime]').datetimepicker('setEndDate', $('[name=endShareTime]').val().substring(0, 16));

  $("[name=endTime]").datetimepicker({
      autoclose: true,
      format: 'yyyy-mm-dd',
      minView: 'month'
  }).on('changeDate', function(e) {
    $('[name=startTime]').datetimepicker('setEndDate', e.date);
  });
  $('[name=endTime]').datetimepicker('setStartDate', $('[name=startTime]').val().substring(0, 16));

  $("[name=endShareTime]").datetimepicker({
    autoclose: true,
    format: 'yyyy-mm-dd',
    minView: 'month'
  }).on('changeDate', function(e) {
    $('[name=startShareTime]').datetimepicker('setEndDate', e.date);
  });
  $('[name=endShareTime]').datetimepicker('setStartDate', $('[name=startShareTime]').val().substring(0, 16));

  var validator = new Validator({
    element: '#resource-form'
  });

  validator.addItem({
    element: '[name=startTime]',
    required: true,
    rule:'date_check'
  });

  validator.addItem({
    element: '[name=endTime]',
    required: true,
    rule:'date_check'
  });

  var validatorShare = new Validator({
    element: '#share-form'
  });

  validatorShare.addItem({
    element: '[name=startShareTime]',
    required: true,
    rule:'share_date_check'
  });

  validatorShare.addItem({
    element: '[name=endShareTime]',
    required: true,
    rule:'share_date_check'
  });


  var dataClickShow = function(el, startTime, endTime, currentStartDate, currentEndDate, btn, callback) {
    $(el).on("click",function(){
      $("[name=" + startTime + "]").val($(el).attr(currentStartDate));
      $("[name=" + endTime + "]").val($(el).attr(currentEndDate));

      teachCourseDate();
    })
  }

  var searchBtnClick = function(el, callback) {
    $(el).on('click', function() {
      $(this).siblings('.cz-data-nav-tabs').find('a').removeClass('btn-primary');
      callback();
    })
  }

  var analysisSearchClick = function() {
    var $form = $('#analysis-search-form');
    $('#js-analysis-search').on('click', function() {
      $.get($form.attr('action'), $form.serialize(), function(html) {
        $('.js-user-analysis').html(html);
      })
    })
  }

  var DataSwitchEvent = function (selecter) {
    $(selecter).on('click', function () {
      var $this = $(this);
      if (!$this.hasClass('btn-primary')) {
        $(this).addClass('btn-primary').siblings().removeClass('btn-primary');
        $this.parents('.js-panel').find('.study-source-statistic').data('url', $this.data('url'));
      }
    })
  }

})
