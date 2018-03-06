let attendTrendChart = echarts.init(document.getElementById('attend-trend-chart'));
attendTrendChart.showLoading({
  text: '加载中',
  textColor: '#fff',
  maskColor: '#00182E'
});

$.get($('.attend-chart-data').data('url'), function(datas) {
  console.log(datas);
  let xAttendArr = [];
  let attendArr = [];
  datas.map((item, index) => {
    xAttendArr.push({
      value: item.day,
      textStyle: {
        color: '#fff',
        fontSize: 10,
      }
    });
    attendArr.push({
      value: item.data,
      name: item.day,
    });
  })
  let attendTrendOption = {
      backgroundColor: '#00182E',
      title: {
          text: '出勤总趋势',
          show: false
      },
      tooltip : {
          trigger: 'axis',
          formatter: '{a} <br/>{b} : {c}%'
      },
      grid: {
        left: 'center',
        top: 20,
        width: '75%',
        height: '70%',
      },
      xAxis : [
          {
              type : 'category',
              boundaryGap : false,
              data : xAttendArr,
              axisLine: {
                lineStyle: {
                  color: 'rgba(170,225,63,.14)',
                }
              },
              splitLine: {
                lineStyle: {
                  color: ['rgba(170,225,63,.14)'],
                }
              },
          }
      ],
      yAxis : [
          {
              type : 'value',
              axisLine: {
                lineStyle: {
                  color: 'rgba(255,200,65,.14)',
                }
              },
              axisLabel: {
                formatter: '{value} %',
                color: '#fff',
                fontSize: 10,
                textStyle: {
                  color: '#fff'
                }
              },
              splitLine: {
                lineStyle: {
                  color: ['rgba(255,200,65,.14)'],
                }
              },
          }
      ],
      series : [
          {
              name:'出勤率',
              type:'line',
              // stack: '总量',
              itemStyle: {
                normal: {
                  color: '#AAE13F',
                }
              },
              lineStyle: {
                normal: {
                  color: '#AAE13F',
                }
              },
              areaStyle: {
                normal: {
                    color:'#AAE13F',
                    opacity: 0.48
                }
              },
              data: attendArr,
          },
      ]
  };
  attendTrendChart.hideLoading();
  attendTrendChart.setOption(attendTrendOption);
});