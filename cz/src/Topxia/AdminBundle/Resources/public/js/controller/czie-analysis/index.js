define(function(require, exports, module) {

  exports.run = function() {
		console.log('123');
		loadTableData();

		$loadBtn.on('click', loadTableData);

		$dataBox.on('click', '.pagination li', function() {
			var url = $(this).data('url');
			$.get(url, (data) => {
				$dataBox.html(data);
			})
		})

		$teacherType.on('click', () => {
			console.log('input click');
			loadTableData();
		});
	};

  var $dataBox = $('#teacher-table-box');
  var $form = $('#teacher-search-form');
  var $loadBtn = $(".js-search-btn");
  var $teacherType = $(".js-teacherType-change");

	var loadTableData = function() {
		var url = $form.attr('action');
		$.get(url, $form.serialize(), (data) => {
			$dataBox.html(data);
		})
	}
})