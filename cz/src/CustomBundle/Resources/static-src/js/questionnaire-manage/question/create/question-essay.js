import QuestionFormBase from 'app/js/question-manage/type/form-base';

class Essay extends QuestionFormBase {
  constructor($form) {
    super($form);

    this.initTitleEditor(this.validator);
    this.initAnalysisEditor();
  }
}

export default Essay;