import { initEditor } from "../editor";
import "store";
export default class Text {
  constructor(props) {
    this._init();
  }

  _init() {
    this._inItStep2form();
  }

  _inItStep2form() {
    const $step2_form = $('#step2-form');
    let validator = $step2_form.data('validator');
    validator = $step2_form.validate({
      rules: {
        title: {
          required: true,
          maxlength: 50,
          trim: true,
          course_title: true,
        },
        content: {
          required: true,
          trim: true,
        },
        mediaId: {
          required: true,
          digits:true
        },
      },
      messages: {
        mediaId: {
          required:'请选择%display%',
        },
      }
    });
    const $content = $('[name="content"]');
    this.editor = initEditor($content, validator);
  }
}