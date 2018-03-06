/**
 * Created by wubo on 2017/10/31.
 */
import 'jquery-circle-progress';

(function() {
  const colorGreen = '#61B565';
  const colorRed = '#D96547';
  let currentColor = '';


  $('.js-statistics-circle').map(function() {
    const $elem = $(this);
    const count = $elem.data('count');
    const unCount = $elem.data('un-count');
    const rate = (count / (count + unCount)).toFixed(2);

    currentColor = currentColor === colorGreen ? colorRed : colorGreen;
    $elem.circleProgress({
      value: rate,
      size: 220,
      startAngle: -190,
      fill: currentColor,
      thickness: 24,
      emptyFill: '#616161'
    }).on('circle-animation-progress', function (event, animationProgress, stepValue) {
      //let spanElem = $elem.find('.js-rate');
      //spanElem.text(parseInt(stepValue * 100) + '%');
    });
  });
  
})();