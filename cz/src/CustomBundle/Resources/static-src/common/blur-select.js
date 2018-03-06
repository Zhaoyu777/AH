import lodash from 'lodash';

class blurSelect {
  constructor() {
    this.studentList = [];
    this.arr = [];
    this.inputSearchEl = $('.js-input-search');
    this.studentListEl = $('#student-list');
    this.selectedStudents = $('.js-selected-students');
    this.selectContainer = $('.js-input-container');
    this.url = this.inputSearchEl.data('query-url');
  }

  init() {
    this.initEvent();
  }

  initEvent() {
    this.inputSearchEl.on('input', lodash.debounce((e) => {
      if (e.target.value.length > 0) {
        this.loadStudentList(e);
      } else {
        $('.cz-select-list').hide();
      }
    },800));
    this.studentListEl.on('click', (e) => this.searchEleClick(e));
    this.selectedStudents.on('click', '.es-icon', (e) => this.selectedEleRemove(e));
  }

  getStudentIds() {
    return this.arr;
  }

  getLiHtml(students) {
    let html = '';
    students.map((student) => {
      html += `<tr id=${student.id}><td class="stu-name" style="padding:8px;width:25%">${student.truename}</td><td>${student.role}</td><td>${student.nickname}</td></tr>`;
    })
    return html;
  }

  loadStudentList(e) {
    let self = this;
    $.ajax({
      url: self.url,
      type: 'GET',
      data: {q: e.target.value, excludeIds:this.arr},
      success(students) {
        $('.cz-select-list').show();
        if (students.length == 0) {
          self.studentListEl.html(`<tr style="padding:5px"><td colspan="3">抱歉，未找到<td></tr>`);
        } else {
          self.studentListEl.html(self.getLiHtml(students));
        }
      }
    });
  }

  searchEleClick(e) {
    console.log(e.target.parentElement.outerHTML);
    let $targetTr = $(e.target.parentElement);
    let truename = $targetTr.find('td.stu-name').text();
    let selectId = $targetTr.attr('id');
    let selectHtml = e.target.parentElement;
    if (!selectId) {
      return;
    }
    this.selectedStudents.append($targetTr);
    $targetTr.append('<td><a href="javascript:;"><i class="pull-right es-icon es-icon-close01"></i></a></td>');
    this.arr.push(selectId);
  }

  selectedEleRemove(e) {
    let $target = $(e.target);
    let truename = e.target.innerHTML;
    let selectId = $target.parents('tr')[0].id;
      console.log(selectId);
    this.selectContainer.find('td[id = "`${selectId}`"]').closest('tr').show();
    $target.closest('tr').remove();
    this.arr.map((val,index) => {
      if(val == selectId) {
        this.arr.splice(index,1);
      }
    });
    console.log(this.arr);
  }

}

export default blurSelect;