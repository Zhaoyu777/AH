import question from './question';

let _question = new question();
console.log(_question);
_question.bindEvents();

function toggleBtn () {
  $('.activity-content').on('click', '.js-rend-call', (e) => {
    let $target = $(e.target);
    $.post($target.data('url'), () => {
    })
  })
}
toggleBtn();

$('#question-activity').perfectScrollbar();
$('#question-activity').perfectScrollbar('update');

// socket.on('questionnaire finished', (response) => questionnaireFinished(response));

window.setInterval('questionnaireFinished()', 5000);

window.questionnaireFinished = () => {
  let fetchUrl = $("input[name='fetchQuestionaireResultsUrl']").val();

  $.get(fetchUrl, (response) => {
    let questionResults = response.questionResults,
    actualNum = response.actualNum;

    $('.actual-num').text(actualNum);

    $.each(questionResults, function(key, questionResult){
      if (questionResult.type == 'essay') {
        var html = ``;
        $.each(questionResult.answers, function(key, answer){
          if (answer == []) {
            return true;
          }
          html+= `<div class="ptl clearfix">
                    ${ answer.content }
                  </div>
                  <div class="text-right">
                    <span class="color-gray">——${ answer.user }</span>
                  </div>`;
        })
        $(`#essay-result-${questionResult.id} .modal-body`).html(html);

      } else {
        $.each(questionResult.items, function(key, item){
          $(`.question-part-${questionResult.id}-${key}-result`).text(item.part);
          $(`.question-num-${questionResult.id}-${key}-result`).text(item.num);
        })
      }
    })
  });
}

$(".js-cz-activity-content").on('click', 'img', function() {
  const src = $(this).attr('src');
  const $html = $(`
    <div class="js-img-full" style="width: 100%;height: 100%;padding: 20px;overflow: auto;">
      <img src="${src}" style="position: relative; top: 50%; transform: translateY(-50%);max-width: 100%; height: auto;" />
    </div>
  `);
  $('#modal').html($html);
  $('#modal').modal('show');
  $('body').on('click', '.js-img-full', function() {
    $('#modal').modal('hide');
  });
});

// setInterval(() => {
//   if ($('.choice-options-content').find('.in').length == 0) {
//     $.get($('#js-activity-content').data('url'), (data) => {
//       $('#js-activity-content').html(data);
//     })
//     if ($('.es-nav-default').find('.modal-backdrop').length != 0) {
//       $("div").removeClass('modal-backdrop');
//     }
//   }
// }, 5000);