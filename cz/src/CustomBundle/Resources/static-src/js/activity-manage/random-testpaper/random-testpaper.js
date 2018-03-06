import sortList from 'common/sortable';
import { delHtmlTag } from 'common/utils';
import SelectLinkage from '../../question-manage/select-linkage.js';

export default class RandomTestpaper {
  constructor($form) {
    this.$form = $('#step2-form');
    this.validator = null;
    this.setValidateRule();
    this._initValidate();
    this._initSortList();
  }

  changeRange(event) {
    let $this = $(event.currentTarget);
    ($this.val() == 'course') ? this.$form.find('#testpaper-range-selects').addClass('hidden') : this.$form.find('#testpaper-range-selects').removeClass('hidden');
  }

  changeCount() {
    let num = 0;
    this.$form.find('[data-role="count"]').each(function (index, item) {
      num += parseInt($(item).val());
    });
    this.$form.find('[name="questioncount"]').val(num > 0 ? num : null);
  }

  _initValidate() {
    this.validator = this.$form.validate({
      rules: {
        title: {
          required: true,
          maxlength: 50,
          trim: true,
        },
        content: {
          required: true,
          maxlength: 500,
          trim: true,
        },
        range: {
          required: true
        },
        difficulty: {
          required: true
        },
        passedScore: {
          required: true,
          arithmeticFloat: true,
          min: 0,
          max: function () {
            return parseInt($("#totalScore").text());
          },
        },
        questioncount: {
          required: true
        }
      },
      messages: {
        questioncount: Translator.trans('activity.testpaper_manage.question_required_error_hint'),
        title: {
          required: Translator.trans('activity.testpaper_manage.input_title_hint'),
          maxlength: Translator.trans('site.maxlength_hint',{length: 50})
        },
        content: {
          required: Translator.trans('activity.testpaper_manage.input_description_hint'),
          maxlength: Translator.trans('site.maxlength_hint',{length: 500})
        },
        range: Translator.trans('activity.testpaper_manage.question_scope')
      }
    });
    this.$form.find('.testpaper-question-option-item').each(function () {
      let self = $(this);
      self.find('[data-role="count"]').rules('add', {
        min: 0,
        max: function () {
          return parseInt(self.find('[role="questionNum"]').text());
        },
        digits: true
      })

      self.find('[data-role="score"]').rules('add', {
        min: 0,
        max: 100,
        digits: true
      })

      if (self.find('[data-role="missScore"]').length > 0) {
        self.find('[data-role="missScore"]').rules('add', {
          min: 0,
          max: function () {
            return parseInt(self.find('[data-role="score"]').val());
          },
          digits: true
        })
      }
    })
  }

  setValidateRule() {
    $.validator.addMethod("arithmeticFloat",function(value,element){  
      return this.optional( element ) || /^[0-9]+(\.[0-9]?)?$/.test(value);
    }, $.validator.format(Translator.trans("activity.testpaper_manage.arithmetic_float_error_hint")));
  }

  _initSortList() {
    sortList({
      element: '#testpaper-question-options',
      itemSelector: '.testpaper-question-option-item',
      handle: '.question-type-sort-handler',
      ajax: false
    });
  }

  _submit(event) {
    let $target = $(event.currentTarget);
    let status = this.validator.form();

    if (status) {
      $.post($target.data('checkUrl'),this.$form.serialize(),result => {
        if (result.status == 'no') {
          $('.js-build-check').html(Translator.trans('activity.testpaper_manage.question_num_error'));
        } else {
          $('.js-build-check').html('');

          $target.button('loading').addClass('disabled');
          this.$form.submit();
        }
      })

    }
  }
}

new RandomTestpaper($('#step2-form'));
new SelectLinkage($('[name="range[courseId]"]'),$('[name="range[lessonId]"]'));

