import PracticeWorkSocket from '../practice-work-socket';


class PracticeWorkTeacher {
	constructor() {
	}

	init() {
		const practiceWorkSocket = new PracticeWorkSocket();
		practiceWorkSocket.bindEvents();

		this.toggleBtn();
		this.perfectScrollbar();
	}

	toggleBtn() {
	  $('.activity-show-content-new').on('click', '.js-rend-call', (e) => {
	    let $target = $(e.target);
	    $.post($target.data('url'));
	  })
	}

	perfectScrollbar() {
    const $elem = $('#practive-work-activity')
    $elem.perfectScrollbar();
    $elem.perfectScrollbar('update');

    $('#modal').on('show.bs.modal', (e) => {
      $elem.perfectScrollbar('destroy');
    });

    $('#modal').on('hide.bs.modal', (e) => {
      $elem.perfectScrollbar();
    });
  }
}

const practiceWorkTeacher = new PracticeWorkTeacher();
practiceWorkTeacher.init();