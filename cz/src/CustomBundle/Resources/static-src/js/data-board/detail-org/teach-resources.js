export default class TeachResources {
  constructor() {
    this.rValidator = {};
  }

  init() {
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
    this.dataClickShow('#btn-day', 'startTime', 'endTime', 'currentDayStart', 'currentDayEnd', '#btn-search');
  }

  bindWeekSearchBtnEvent() {
    this.dataClickShow('#btn-week', 'startTime', 'endTime', 'currentWeekStart', 'currentWeekEnd', '#btn-search');
  }

  bindMonthSearchBtnEvent() {
    this.dataClickShow('#btn-month', 'startTime', 'endTime', 'currentMonthStart', 'currentMonthEnd', '#btn-search');
  }

  bindSearchBtnEvent() {
    console.log(this.addFormValidator());
    $('#btn-search').on('click', (event) => {
      if (this.rValidator.form()) {
        var $self = $(event.currentTarget);
        $('.data-nav-tabs').find('a').removeClass('active');
        this.fetchDatas();
      } else {
        return;
      }
    })
  }

  triggerClickEvents() {
    $('#btn-month').click();
  }

  initDatetimepickers() {
    $("[name=startTime]").datetimepicker({
      language: document.documentElement.lang,
      autoclose: true,
      format: 'yyyy-mm-dd',
      minView: 'month'
    }).on('changeDate', function(e) {
      $('[name=endTime]').datetimepicker('setStartDate', e.date);
    });
    $('[name=startTime]').datetimepicker('setEndDate', $('[name=endTime]').val().substring(0, 16));

    $("[name=endTime]").datetimepicker({
      language: document.documentElement.lang,
      autoclose: true,
      format: 'yyyy-mm-dd',
      minView: 'month'
    }).on('changeDate', function(e) {
      $('[name=startTime]').datetimepicker('setEndDate', e.date);
    });
    $('[name=endTime]').datetimepicker('setStartDate', $('[name=startTime]').val().substring(0, 16));
  }

  initValidateRules() {
    $.validator.addMethod("date_check", function(element) {
      var startTime = $("[name='startTime']").val();
      var endTime = $("[name='endTime']").val();
      startTime = startTime.replace(/-/g,"/");
      startTime = Date.parse(startTime)/1000;
      endTime = endTime.replace(/-/g,"/");
      endTime = Date.parse(endTime)/1000;

      if (endTime >= startTime) {
        return true;
      }else{
        return false;
      }
    }, Translator.trans('开始时间必须小于或等于结束时间'));
  }

  addFormValidator() {
    var $resourceForm = $('#data-resource-form');

    this.rValidator = $resourceForm.validate({
      groups: {
        nameGroup: 'startTime endTime'
      },
      rules: {
        startTime: 'required date_check',
        endTime: 'required date_check',
      }
    });
  }

  dataClickShow(el, startTime, endTime, currentStartDate, currentEndDate, btn) {
    $(el).on("click", (event) => {
      var $self = $(event.currentTarget);
      if (!$self.hasClass('active')) {
        $self.addClass('active').siblings().removeClass('active');
      }

      $("[name=" + startTime + "]").val($(el).attr(currentStartDate));
      $("[name=" + endTime + "]").val($(el).attr(currentEndDate));

      this.fetchDatas();
    })
  }

  fetchDatas() {
    var element = $("#data-study-source-chart");
    var chart = echarts.init(element.get(0));
    chart.showLoading({
      text: '加载中',
      textColor: '#fff',
      maskColor: '#00182E'
    });

    return $.get(element.data('url'), $('#data-resource-form').serialize(), function(datas) {
      let xSourceArr = [];
      let sourceArr = [];
      console.log(datas);
      datas.sourceTypes.map((item, index) => {
        xSourceArr.push({
          value: item,
          textStyle: {
            color: '#fff',
            fontSize: 10,
          }
        });
      });
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
          data: xSourceArr,
          axisTick: {
            alignWithLabel: true,
          },
          splitLine: {
            lineStyle: {
              color: ['rgba(170,225,63,.14)'],
            }
          },
        },
        yAxis: {
          type: 'value',
          splitLine: {
            lineStyle: {
              color: ['rgba(255,200,65,.14)'],
            }
          },
          axisLabel: {
            textStyle: {
                color: '#fff'
            }
          },
        },
        series: [{
          name: '数量',
          type: 'bar',
          data: datas.sourceCounts,
          barMaxWidth: 70,
          itemStyle: {
            normal: {
              color: '#AAE13F',
            }
          }
        }]
      };
      chart.hideLoading();
      chart.setOption(option);
    })
  }
}