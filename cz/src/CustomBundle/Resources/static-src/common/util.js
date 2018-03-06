/**
 * Created by wubo on 2017/8/18.
 */
export const calcTime = function calcTime(time) {
  let h = Math.floor(time / 3600);
  time -= h * 3600;
  h = h < 10 ? ("0" + h) : h;
  let m = Math.floor(time / 60);
  time -= m * 60;
  m = m < 10 ? ("0" + m) : m;
  let s = time;
  s = s < 10 ? ("0" + s) : s;

  return h + ":" + m + ":" + s;

};

export const fullScreen = function (el) {
  if(!el) {
    return;
  }

  let rfs = el.requestFullScreen || el.webkitRequestFullScreen ||
    el.mozRequestFullScreen || el.msRequestFullScreen;
  if (typeof rfs !== 'undefined' && rfs) {
    rfs.call(el);
  } else if (typeof window.ActiveXObject !== 'undefined') {
    let wScript = new ActiveXObject("WScript.Shell");
    if(wScript !== null) {
      wScript.SendKeys("{F11}");
    }
  }
};

export const exitFullScreen = function (el) {
  if(!el) {
    return;
  }

  let cfs = el.cancelFullScreen || el.webkitCancelFullScreen ||
    el.mozCancelFullScreen || el.exitFullScreen;
  if (typeof cfs !== 'undefined' && cfs) {
    cfs.call(el);
  } else if (typeof window.ActiveXObject !== 'undefined') {
    let wScript = new ActiveXObject("WScript.Shell");
    if(wScript !== null) {
      wScript.SendKeys("{F11}");
    }
  }
};