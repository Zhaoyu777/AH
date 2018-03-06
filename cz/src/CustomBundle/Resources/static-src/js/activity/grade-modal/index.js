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
		$target.addClass('active').parent().siblings().find('.js-evaluate-grade').removeClass('active');
	});
}

$('#grade-submit-btn').on('click', function() {
	let dataScore = $('.js-evaluate-grade.active').find('span').data('score');
	$('input[name="score"]').val(dataScore);
	$(this).button('loading');
  $.post($('#grade-modal-form').attr('action'), $('#grade-modal-form').serialize(), () => {
  	notify('success',Translator.trans('评分成功'));
    $('#modal').modal('hide');
    $('.js-grade-btn').siblings('.js-score-animate').animate({
      bottom: '25px',
      opacity: 0
    },1000);
  });
});

//头脑风暴
$('.js-brain-remark-btn').on('click', (e)=> {
  let $target = $(e.currentTarget);
  $target.next('.js-brain-remark-form').slideToggle();
})




