import BaseEditor from 'app/js/task-manage/create/editor';

class Editor extends BaseEditor {
  _rendButton(step) {
    if (step === 1) {
      this._renderPrev(false);
      this._rendSubmit(false);
      this._renderNext(true);
    } else if (step === 2) {
      this._renderPrev(true);
      if (this.mode === 'edit') {
        this._renderPrev(false);
      }
      this._rendSubmit(false);
      if (!this.loaded) {
        this._renderNext(false);
        return;
      }
      this._renderNext(true);
    } else if (step === 3) {
      this._renderNext(false);
      this._renderPrev(true);
      this._rendSubmit(true);
    }
  }
}

export default Editor;