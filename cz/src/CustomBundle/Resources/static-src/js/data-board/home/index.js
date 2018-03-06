import Cookies from 'js-cookie';
const TODAY_PV = 'TODAY_PV';
const TODAY_UV = 'TODAY_UV';
let $todayPv = $('#js-today-pv');
let $todayUv = $('#js-today-uv');
let randomPv, randomUv;
if (Cookies.get(TODAY_PV) && Cookies.get(TODAY_UV)) {
  randomPv = Cookies.get(TODAY_PV);
  randomUv = Cookies.get(TODAY_UV);
  $todayPv.text(randomPv);
  $todayUv.text(randomUv);
} else {
  randomPv = 11305;
  randomUv = 1284;
  $todayPv.text(randomPv);
  $todayUv.text(randomUv);
}

function RandomNumBoth(min,max){
  let range = max - min;
  let rand = Math.random();
  let num = min + Math.round(rand * range);
  return num;
}

setInterval(() => {
  let randomPvRate = RandomNumBoth(3,10);
  let randomUvRate = RandomNumBoth(3,8);
  randomUv = Number(randomUv) + randomUvRate;
  randomPv = Number(randomPv) + randomPvRate * randomUvRate;
  $todayPv.text(randomPv);
  $todayUv.text(randomUv);
  Cookies.set(TODAY_PV, randomPv);
  Cookies.set(TODAY_UV, randomUv);
}, 2000);


$('[data-toggle="popover"]').popover({
  html: true,
  trigger: 'hover'
});

let collegeScaleChart = echarts.init(document.getElementById('college-scale-chart'));
collegeScaleChart.showLoading({
  text: '加载中',
  textColor: '#fff',
  maskColor: '#00182E'
});
let collegeTargetChart = echarts.init(document.getElementById('college-target-chart'));
collegeTargetChart.showLoading({
  text: '加载中',
  textColor: '#fff',
  maskColor: '#00182E'
});
let courseGrowChart = echarts.init(document.getElementById('course-grow-chart'));
courseGrowChart.showLoading({
  text: '加载中',
  textColor: '#fff',
  maskColor: '#00182E'
});
let courseSatisfyChart = echarts.init(document.getElementById('course-satisfy-chart'));
courseSatisfyChart.showLoading({
  text: '加载中',
  textColor: '#fff',
  maskColor: '#00182E'
});

$.get($('.school-chart-data').data('url'), function(datas) {
  console.log(datas);
  let platArr = [];
  let targetArr = [];
  let xTargetArr = [];
  datas.map((item, index) => {
    targetArr.push(item.teachTargetFinishRate);
    xTargetArr.push({
      value: item.name,
      textStyle: {
        color: '#fff',
        fontSize: 10,
      }
    })
    platArr.push({
      value: item.platformUseRate,
      name: item.name,
    })
  });
  let scaleOption = {
      title : {
          text: '学院教学平台使用占比',
          subtext: '',
          x:'center',
          show: false
      },
      tooltip : {
          trigger: 'item',
          formatter: "{a} <br/>{b} : {d}%"
      },
      legend: {
          orient: 'vertical',
          right: 20,
          icon: 'circle',
          itemWidth: 5,
          textStyle: {
            color: '#fff',
            fontSize: 10,
          },
          data: platArr,
      },
      color: ['#FF7B3E', '#FD9827', '#FFC841', '#FFFD5E', '#AAE13F', '#3FCCE1', '#3FA6E1', '#833FE1', '#AB57FF', '#FF52B4', '#FF5256'],
      series : [
          {
              name: '学院名称',
              type: 'pie',
              radius : '80%',
              center: ['30%', '50%'],
              data: platArr,
              itemStyle: {
                normal: {
                },
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
              },
              label: {
                normal: {
                  show: false,
                }
              }
          }
      ]
  };
  let targetOption = {
    title: {
        text: '学院教学目标达成率',
        show: false
    },
    grid: {
      left: 'center',
      top: 20,
      width: '70%',
      height: '60%',
      borderColor: 'rgba(94,203,255,.14)',
    },
    tooltip : {
        trigger: 'axis',
        formatter: '{a} <br/>{b} : {c}%'
    },
    xAxis : [
        {
            type : 'category',
            data : xTargetArr,
            axisLine: {
              lineStyle: {
                color: 'rgba(42,204,255,.14)',
              }
            },
            axisLabel: {
              interval: 0,
              rotate: -45,
            },
            splitLine: {
              lineStyle: {
                color: ['rgba(42,204,255,.14)'],
              }
            },
        }
    ],
    yAxis : [
        {
            type : 'value',
            max: '100',
            axisLine: {
              lineStyle: {
                color: 'rgba(255,200,65,.14)',
              }
            },
            axisLabel: {
              formatter: '{value} %',
              color: '#fff',
              textStyle: {
                color: '#fff'
              }
            },
            splitLine: {
              lineStyle: {
                color: ['rgba(42,204,255,.14)'],
              }
            },
        }
    ],
    series : [
        {
            name:'学院名称',
            type:'bar',
            stack: '总量',
            barGap: '15%',
            barCategoryGap: 10,
            lineStyle: {
              normal: {
                color: '#AAE13F',
              }
            },
            itemStyle: {
              normal: {
                color: '#2ACCFF',
                barBorderRadius: [3, 3, 0, 0]
              }
            },
            data: targetArr,
        },
    ]
  };
  collegeScaleChart.hideLoading();
  collegeTargetChart.hideLoading();
  collegeScaleChart.setOption(scaleOption);
  collegeTargetChart.setOption(targetOption);
});

$.get($('.course-chart-data').data('url'), function(datas) {
  console.log(datas);
  let xSourceArr = [];
  let sourceArr = [];
  let satisfyArr = [];
  datas.map((item, index) => {
    xSourceArr.push({
      value: item.termName,
      textStyle: {
        color: '#fff',
        fontSize: 10,
      }
    });
    sourceArr.push({
      value: item.resourcesIncreaseRate,
      name: item.termName,
    });
    satisfyArr.push({
      value: item.satisfactionTrend,
      name: item.termName,
    });
  });
  let growOption = {
      title: {
          text: '课程资源学年增长率',
          show: false
      },
      grid: {
        left: 'center',
        top: 'middle',
        width: '60%',
        height: '60%',
        // borderColor: 'rgba(94,203,255,.14)',
      },
      tooltip : {
          trigger: 'axis',
          formatter: '{a} <br/>{b} : {c}%'
      },
      xAxis : [
          {
            type : 'category',
            data : xSourceArr,
            axisLine: {
              lineStyle: {
                color: 'rgba(255,200,65,.14)',
              }
            },
            axisLabel: {
              interval: 0,
            },
            splitLine: {
              lineStyle: {
                color: ['rgba(255,200,65,.14)'],
              }
            },
        }
      ],
      yAxis : [
        {
          type : 'value',
          // max: '100',
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
              name:'学年增长率',
              type:'bar',
              // stack: '总量',
              barCategoryGap: 25,
              // lineStyle: {
              //   normal: {
              //     color: '#FFC841',
              //   }
              // },
              itemStyle: {
                normal: {
                  color: '#FFC841',
                  barBorderRadius: [5, 5, 0, 0]
                }
              },
              data: sourceArr,
          },
      ]
  };
  let satisfyOption = {
      title: {
          text: '课程满意度趋势',
          show: false
      },
      tooltip : {
          trigger: 'axis',
          formatter: '{a} <br/>{b} : {c}%'
      },
      grid: {
        left: 'center',
        top: 'middle',
        width: '60%',
        height: '60%',
      },
      xAxis : [
          {
              type : 'category',
              boundaryGap : false,
              data : xSourceArr,
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
              name:'满意度趋势',
              type:'line',
              // stack: '总量',
              lineStyle: {
                normal: {
                  color: '#AAE13F',
                }
              },
              itemStyle: {
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
              data: satisfyArr,
          },
      ]
  };
  courseGrowChart.hideLoading();
  courseSatisfyChart.hideLoading();
  courseGrowChart.setOption(growOption);
  courseSatisfyChart.setOption(satisfyOption);
});


