class SelectLinkage
{
  constructor($select1,$select2) {
    this.select1 = $select1;
    this.select2 = $select2;

    this._initEvent();
  }

  _initEvent() {
    this.select1.on('change', event => this._selectChange(event));
  }

  _selectChange(event)
  {
    let url = this.select1.data('url');
    let value = this.select1.val();
    let self = this;

    self.select2.text('');

    if (value == 0 || value == 'userId') {
      this.select2.hide();
      return;
    }

    $.post(url,{courseId:value},function(result){
      if (result != '') {
        let option = '<option value="0">'+Translator.trans('site.choose_hint')+'</option>';

        if (self.select1.data('type') == 'instant') {
          $.each(result.courseLessons, function(index, lessonTasks) {
            option +='<optgroup label=课次'+lessonTasks.number+'>';
            if (result.courseTasks[index] != undefined) {
              let $tasks = result.courseTasks[index];
              $.each($tasks, function(index, task) {
                option += '<option value="'+task.id+'">'+task.title+'</option>';
              });
              option +='</optgroup>';
            }
          });
        } else {
          $.each(result.courseTasks,function(index,task){
            option += '<option value="'+task.id+'">'+task.title+'</option>';
          })
        }
        self.select2.append(option);
        self.select2.show();
      } else {
        self.select2.hide();
      }
    });
  }
}

export default SelectLinkage;