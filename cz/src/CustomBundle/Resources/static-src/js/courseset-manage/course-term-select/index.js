let Main = Main || {};

let $termSelect = $('#termCode');
let $form = $('#term-form');

Main.initCourses = function() {
	loadCourses($form.data('url'));
}

Main.termChange = function() {
	$termSelect.change(function(event) {
		loadCourses($form.data('url'));
	});
}

Main.paginationClick = function() {
	$('#courses-table').on('click', '.pagination li', function(event) {
		var url = $(this).data('url');
		if (typeof (url) !== 'undefined') {
			loadCourses(url);
		};
	});
}

function loadCourses(url) {
	$.post(url, $form.serialize(), function(data) {
		$('#courses-table').html(data);
		$("#courses-table").find('.js-course-title-tip').popover();
	});
};

Main.initEvents = function() {
	$('#courses-table').on('click','.js-toogle-btn', function(e) {
		const $parent = $(this).parents('tr');
		const id = $parent.data('id');
		const $currentItems = $('#courses-table').find(`.js-classroom-item__${id}`);

		$parent.toggleClass('choose')
			.siblings('.js-courseSet-tr')
			.removeClass('choose');
		$currentItems.removeClass('js-classroom-item')
		$currentItems.toggleClass('hidden')
			.siblings('.js-classroom-item')
			.addClass('hidden');
		$currentItems.addClass('js-classroom-item');

		$('.js-icon-unfold').removeClass('hidden');
		$('.js-icon-Packup').addClass('hidden');
		if($parent.hasClass('choose')) {
			$(this).find('.js-icon-unfold').addClass('hidden');
			$(this).find('.js-icon-Packup').removeClass('hidden');
		}
	});
}

Main.load = {
	init:function(){
		Main.initCourses();
		Main.termChange();
		Main.paginationClick();
		Main.initEvents();
	}
}

Main.load.init();