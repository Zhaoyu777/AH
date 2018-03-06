class activityAnimate {
  constructor({animateEl, imgSrc} = {animateEl: $('.js-rend-call'), imgSrc: 'rollcall'}) {
    this.animateEl = animateEl;
    this.imgSrc = imgSrc;
  }

  init() {
    // this.render();
    this.initEvent();
  }

  initEvent() {
    this.animateEl.one('click', (e) => this.animate(e));
  }

  render() {
    let html = `<div class="js-activity-animate activity-animate">
                  <img src="/static-dist/custombundle/img/${this.imgSrc}.png" alt="活动图">
                </div>`;
    $('.js-cz-activity-content').append(html);
  }

  animate() {
    $('.js-activity-animate').fadeOut('slow');
    $('.js-activity-animate img').animate({
      width: 0,
      height: 0,
      opacity: 0
    },'slow');
  }
}

export default activityAnimate;