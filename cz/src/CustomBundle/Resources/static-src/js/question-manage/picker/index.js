import QuestionPicker from 'app/common/component/question-picker';
import BatchSelect from 'app/common/widget/batch-select';
import SelectLinkage from '../select-linkage.js';

new QuestionPicker($('#question-picker-body'), $('#question-checked-form'));
new BatchSelect($('#question-picker-body'));

new SelectLinkage($('[name="courseId"]'),$('[name="lessonId"]'));
