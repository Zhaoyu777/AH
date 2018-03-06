class homeworkCheck {
  init() {
    this.initTopicData();
    this.topicClick();
    this.practiceClick();
  }
  topicClick() {
    $('#js-topic-homework').on('click', () => {
      this.initTopicData();
    })
  }
  practiceClick() {
    $('#js-practice-homework').on('click', () => {
      this.initPracticeData();
    })
  }
  initTopicData() {
    $.get($('#js-topic-homework').data('url'), function(html) {
      $('#js-practice-homework').closest('li').removeClass('active');
      $('#js-topic-homework').closest('li').addClass('active');
      $('#js-homework-list').html(html);
    })
  }
  initPracticeData() {
    $.get($('#js-practice-homework').data('url'), function(html) {
      $('#js-practice-homework').closest('li').addClass('active');
      $('#js-topic-homework').closest('li').removeClass('active');
      $('#js-homework-list').html(html);
    })
  }
}

new homeworkCheck().init();

