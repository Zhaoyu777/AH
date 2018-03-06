import QuestionFormBase from 'app/js/question-manage/type/form-base';
import Choice from './question-choice';
import SingleChoice from './question-single-choice';
import Essay from './question-essay';

class QuestionCreator {
  constructor() {
  }

  static getCreator(type, $form) {
    switch (type) {
      case 'single_choice':
        QuestionCreator = new SingleChoice($form);
        break;
      case 'choice':
        QuestionCreator = new Choice($form);
        break;
      case 'essay':
        QuestionCreator = new Essay($form);
        break;
      default:
        QuestionCreator = new QuestionFormBase($form);
        QuestionCreator.initTitleEditor();
        QuestionCreator.initAnalysisEditor();
    }

    return QuestionCreator;
  }
}

let $form = $('[data-role="question-form"]');
let type = $('[data-role="question-form"]').find('[name="type"]').val();

QuestionCreator.getCreator(type, $form);
