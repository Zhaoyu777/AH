/**
 * Created by wubo on 2017/8/2.
 */

import Graph from './graph';
/**
 * @value: 圆形图的数值(0-1)
 * @type: 表示数据展示的方式
 *  1. percent : 百分比
 *  2. decimals： 小数点（保留一位）
 *  3. number： 100%的圆形，需要另外传入一个number的属性值，代表100%时的值
 * @name: 图形的名称(数据类型的名称)
 * @number: type 为 number时才有的值,表示100%的圆形代表的值，为数字
 * @xData: 数组，表示X轴的说明
 * @data: 数组，表示坐标的值
 */

getData();

function getData() {
  $.get($("#statisticsData").data("url")).done((data) => {
      new Graph(data);
  }).fail((data) => {
  });
}
