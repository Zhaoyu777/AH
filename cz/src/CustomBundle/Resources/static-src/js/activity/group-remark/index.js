import notify from 'common/notify';

setEvaluate();
setGrade();


function setEvaluate() {
	$('.js-evaluate-info').on('click', (e) => {
		let $target = $(e.currentTarget);
		if ($target.hasClass('active')) {
      $target.find('input[name="remark[]"]').val('');
			$target.removeClass('active').find('.js-select-icon').hide();
		} else {
      let dataRewark = $target.find('span').text();
      $target.find('input[name="remark[]"]').val(dataRewark);
			$target.addClass('active').find('.js-select-icon').show();
		}
	})
}

function setGrade() {
    $('.js-evaluate-grade').on('click', (e) => {
        let $target = $(e.currentTarget);
        let dataScore = $target.find('span').data('score');
        $target.parent().parent().find('input').val(dataScore);

        $target.addClass('active').parent().siblings().find('.js-evaluate-grade').removeClass('active');
    });
}

$('#group-submit-btn').on('click', function() {
	$(this).button('loading');
  $.post($('#grade-modal-form').attr('action'), $('#grade-modal-form').serialize()).then(() => {
  	notify('success',Translator.trans('评分成功'));
    $('#modal').modal('hide');
  }).fail(error => {
      $('#modal').modal('hide');
    });
});

//头脑风暴
$('.js-brain-remark-btn').on('click', (e)=> {
  let $target = $(e.currentTarget);
  $target.siblings('.remark-group').slideToggle();
})




