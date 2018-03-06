import { fullScreen, exitFullScreen } from '../../../common/util';

$('.js-full-screen').on('click', function() {
  const fullscreenElement = document.fullscreenElement ||
    document.mozFullScreenElement ||
    document.webkitFullscreenElement ||
    document.msFullscreenElement;

  if (fullscreenElement) {
    exitFullScreen(document);
    $(".js-full-screen-icon").toggleClass('hidden');
  } else {
    $(".js-full-screen-icon").toggleClass('hidden');
    const fullscreenEnabled = document.fullscreenEnabled ||
      document.mozFullScreenEnabled ||
      document.webkitFullscreenEnabled ||
      document.msFullscreenEnabled;
    if (fullscreenEnabled) {
      fullScreen(document.documentElement);
    } else {
      alert('浏览器不支持全屏!');
    }
  }
});