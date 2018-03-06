import QuestionFormBase from 'app/js/question-manage/type/form-base';
import Choice from 'app/js/question-manage/type/question-choice';
import SingleChoice from 'app/js/question-manage/type/question-single-choice';
import UncertainChoice from 'app/js/question-manage/type/question-uncertain-choice';
import Determine from 'app/js/question-manage/type/question-determine';
import Fill from 'app/js/question-manage/type/question-fill';
import Essay from 'app/js/question-manage/type/question-essay';
import Material from 'app/js/question-manage/type/question-material';
import SelectLinkage from '../select-linkage.js';


class QuestionCreator {
  constructor() {
  }

  static getCreator(type, $form) {
    switch (type) {
      case 'single_choice':
        QuestionCreator = new SingleChoice($form);
        break;
      case 'uncertain_choice':
        QuestionCreator = new UncertainChoice($form);
        break;
      case 'choice':
        QuestionCreator = new Choice($form);
        break;
      case 'determine':
        QuestionCreator = new Determine($form);
        break;
      case 'essay':
        QuestionCreator = new Essay($form);
        break;
      case 'fill':
        QuestionCreator = new Fill($form);
        break;
      case 'material':
        QuestionCreator = new Material($form);
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

new SelectLinkage($('[name="courseId"]'),$('[name="lessonId"]'));