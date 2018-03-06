/**
 * Created by wubo on 2017/8/2.
 */
import 'jquery-circle-progress';


/**
 * @param obj
 *  1. type: 类型
 *  2. data: 值
 *  3. number: 值
 * @returns {string}
 */
const processData = function (obj) {
  let returnData = '';

  if (obj.type === 'percent') {
    returnData = Math.round(obj.value * 100) + '%';
  } else if (obj.type === 'decimals') {
    returnData = (obj.value).toFixed(1);
  } else if (obj.type === 'number') {
    returnData = obj.value;
  }

  return returnData;
};
/**
 * @param elem 需要创建图形的元素
 * @param obj
 *      1. value 图形的值
 *      2. type 想要写上的文字类型; percent 百分比;  decimals: 小数 number: 纯数字
 *      3. number type为'number'时才有此参数
 */
const generateCircle = function (elem, obj) {
  if (!elem) return;

  elem.circleProgress({
    value: obj.value,
    size: 120,
    startAngle: -190,
    fill: {
      gradient: ["#59B0E5", "#408FEF"]
    }
  }).on('circle-animation-end', function () {
    let spanElem = $(this).find('span');
    spanElem.text(processData(obj));
  });
};
/**
 * @param myChart  想要创建折线图的元素
 * @param obj
 *    1. name 折线图点的名称
 *    2. data 折线图点的值
 *    3. type 折线图上title展示的形式
 */
const generateChart = function (myChart, obj) {
  if (!myChart) return;

  let option = {
    tooltip: {
      trigger: 'axis'
    },
    grid: {
      top: '3%',
      right: '3%',
      bottom: '3%',
      left: '0',
      y: 0,
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      nameTextStyle: {
        color: '#12bcfc',
        fontSize: 14
      },
      data: obj.xData
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: obj.name,
        data: obj.data,
        type: 'line',
        areaStyle: {
          normal: {
            color: '#12bcfc',
            opacity: 0.8
          }
        },
        lineStyle: {
          normal: {
            color: '#12bcfc'
          }
        },
        itemStyle: {
          normal: {
            color: '#ccc'
          }
        }
      }
    ]
  };
  myChart.setOption(option);
};
/**
 * @param myChart 想要创建折线图的元素
 * @param obj
 *      1. title 折线图的title
 *      2. value 平均值，圆形图的值
 *      2. name 折线图点的名称
 *      3. data 折线图点的值
 */
const generateChartMobile = function (myChart, obj) {
  if (!myChart) return;

  let text = obj.name + '  ' + processData(obj);
  let option = {
    title: {
      text: text,
      textStyle: {
        color: '#4591EC',
        fontSize: 18
      }
    },
    tooltip: {
      trigger: 'axis'
    },
    grid: {
      right: '3%',
      bottom: '3%',
      left: '0',
      y: 40,
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      nameTextStyle: {
        color: '#12bcfc',
        fontSize: 14
      },
      data: obj.xData
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: obj.name,
        data: obj.data,
        type: 'line',
        areaStyle: {
          normal: {
            color: '#12bcfc',
            opacity: 0.8
          }
        },
        lineStyle: {
          normal: {
            color: '#12bcfc'
          }
        },
        itemStyle: {
          normal: {
            color: '#ccc'
          }
        }
      }
    ]
  };
  myChart.setOption(option);
};
/**
 * 依赖项：
 *  1. data 数据
 *    @value: 圆形图的数值(0-1)
 *    @type: 表示数据展示的方式
 *      1. percent : 百分比
 *      2. decimals： 小数点（保留一位）
 *      3. number： 100%的圆形，需要另外传入一个number的属性值，代表100%时的值
 *    @name: 图形的名称(数据类型的名称)
 *    @number: type 为 number时才有的值,表示100%的圆形代表的值，为数字
 *    @xData: 数组，表示X轴的说明
 *    @data: 数组，表示坐标的值
 *  2. 插件：jquery-circle-progress
 *  3. 插件：echarts
 */
export default class Graph{
  constructor(data) {
    this.data = data;
    this.windowWidth = $(window).width();
    this.myChartArr = [];
    this.currentWidth = this.windowWidth;
    this.lastWidth = this.windowWidth;
    this.init();
  }
  init() {
    if (this.windowWidth <= 768) {
      this.mobileFunc();
    } else {
      this.pcFunc();
    }
    this.responsive();
  }
  pcFunc() {
    if (!this.data) return;
    this.data.map((obj, index) => {
      const elem1 = $(".js-chart-" + index)[0];
      const elem2 = $(".js-circle-" + index);
      if (!elem1 || !elem2) return;
      // 折线图
      const myChart = echarts.init(elem1);
      this.myChartArr.push(myChart);
      generateChart(myChart, obj);
      // circle图
      generateCircle(elem2, obj);
    });
  }
  mobileFunc() {
    this.data.map((item, index) => {
      const elem = $(".js-chart-" + index)[0];
      if (!elem) return;

      const myChart = echarts.init(elem);
      this.myChartArr.push(myChart);
      generateChartMobile(myChart, item);
    });
  }
  responsive() {
    $(window).on('resize', () => {
      this.currentWidth = $(this).width();
      if (this.lastWidth <= 768 && this.currentWidth <= 768) {
        // 总是小屏
        this.myChartArr.map(chart => {
          chart.resize();
        });
      } else if (this.lastWidth > 768 && this.currentWidth > 768) {
        // 总是大屏
        this.myChartArr.map(chart => {
          chart.resize();
        });
      } else if (this.lastWidth > 768 && this.currentWidth <= 768) {
        // 大屏切到小屏
        this.mobileFunc();
      } else if (this.lastWidth <= 768 && this.currentWidth > 768) {
        // 小屏切到大屏
        this.pcFunc();
      }

      this.lastWidth = this.currentWidth;
    });
  }
}