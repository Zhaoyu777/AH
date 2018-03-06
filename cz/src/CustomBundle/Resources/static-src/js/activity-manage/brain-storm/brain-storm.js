import { initEditor } from "../editor";

export default class BrainStrom {
  constructor(props) {
    this._init();
  }

  _init() {
    this._inItStep2form();
    this._initUI();
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
      },
    });
    const $content = $('[name="content"]');
    this.editor = initEditor($content, validator);
    this._contentCache = $content.val();
  }

  _initUI() {
    if ($('.js-group-btn')[0].checked) {
      $('.js-random-number').hide();
    }
  }
}