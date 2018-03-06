 export default class TeachingAdd {
  constructor() {
    this.$modalContent = $(this._getTopName('js-add-modal-content'));
    this.$modalCheckbox = $(this._getTopName('teaching-checkbox'));
    this.types = {
      'ability': '能力目标' ,
      'knownledge': '知识目标' ,
      'quality': '素质目标'
    };
    this.$checkboxTextDom = '';
    this.checkboxTextType = '';
    this.addText = '';
    this.ids = [];
    this.url = $('.js-add-modal-btn').data('url');
    this.initEvent();
    this.initIds();
  }

  initEvent() {
    $(".js-add-modal-btn").on('click', () => {this.modalFadeIn()});
    $(this._getTopName('close-modal')).on('click',() => {this.modalFadeOut()});
    $(this._getTopName('js-teaching-save-btn')).on('click', () => {this.saveTeachingModal()});
    $('body').on('click', '.selected-delete-icon', (e) => {this.selectedDelete(e)});
  }

  initIds() {
    this.$modalCheckbox.find('[type="checkbox"]').map((index, item) => {
      this.ids.push(item.value);
    });
  }

  modalFadeIn() {
    this.$modalContent.css('display', 'block');
    $(this._getTopName('modal-open')).find('.modal').css('overflow-y', 'hidden');
    $(this._getTopName('modal-open')).find('.modal-dialog').css({'transform': 'none', '-webkit-transform': 'none'});
  }

  modalFadeOut() {
    this.$modalContent.css('display', 'none');
    $(window.top.document.getElementsByTagName('body')).find('.modal').css('overflow-y', 'auto');
  }

  saveTeachingModal() {
    $('.js-teaching-added-content').html('').css('padding-top', '6px');
    this.$modalCheckbox.find('[type="checkbox"]').map((index, item) => {
      if (item.checked) {
        let checkedId = item.value;
        this.$checkboxTextDom = $(item).parents('.checkbox').find('.checkbox-text');
        this.checkboxTextType = this.$checkboxTextDom.data('type');
        this.addText = this.$checkboxTextDom.text();
        $('.js-teaching-added-content').append(`
          <p class="mrl" data-id="${checkedId}">
            <span class="gray-darker">${this.types[this.checkboxTextType]}:</span>
            ${this.addText}
            <i class="es-icon es-icon-close01 selected-delete-icon pull-right cursor-pointer" style="margin-right:-20px"></i>
          </p>`);
      }
    });
    this.modalFadeOut();
  }

  selectedDelete(e) {
    $(e.target).parent('p').remove();
    this.ids.map((id, index) => {
      if ($(e.target).parent('p').data('id') == id) {
        this.$modalCheckbox.find(`[type="checkbox"][value=${id}]`).prop('checked', false);
      }
    })
  }

  _getTopName(dom) {
    return window.top.document.getElementsByClassName(dom);
  }
}