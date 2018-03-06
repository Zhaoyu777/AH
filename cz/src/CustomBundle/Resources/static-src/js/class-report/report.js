export default class Report {
  constructor() {
    this.init();
  }

  init() {
    this.loadBaseInfo();
    this.loadSignInInfo();
    this.loadTaskInfo();
    this.loadScoreInfo();
    this.loadEvaluationInfo();
  }

  loadBaseInfo() {
    this.loadHtml('#js-report-base');
  }

  loadSignInInfo() {
    this.loadHtml('#js-report-sign-in');
  }

  loadTaskInfo() {
    this.loadHtml('#js-report-task');
  }

  loadScoreInfo() {
    this.loadHtml('#js-report-score');
  }

  loadEvaluationInfo() {
    this.loadHtml('#js-report-evaluation');
  }

  loadHtml(element) {
    $.get($(element).data('url'), (html) => {
      console.log($(element).data('url'))
      $(element).html(html);
    })
  }
}