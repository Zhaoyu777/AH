import BatchSelect from 'app/common/widget/batch-select';
import DeleteAction from 'app/common/widget/delete-action';
import { shortLongText } from 'app/common/widget/short-long-text';
import SelectLinkage from './select-linkage';

new BatchSelect($('#quiz-table-container'));
new DeleteAction($('#quiz-table-container'));
shortLongText($('#quiz-table-container'));

new SelectLinkage($('[name="courseId"]'),$('[name="lessonId"]'));