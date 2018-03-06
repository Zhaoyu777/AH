import blurSelect from '../../../common/blur-select';

class shareSelect extends blurSelect {
  constructor() {
    super();
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

    
    let url = $('#js-create-url').data('url');

     $.ajax({
       url: url,
       type: 'GET',
       data: {toUserId: $targetTr.attr('id')},
       success(share) {
         if (share.length == 0) {
           alert('添加失败');
         } else {
          let $targetTr = $(e.target.parentElement);
          $targetTr.append(`<td><a href="javascript:;"><i data-url="/instant/course/share/${share.id}/delete" class="pull-right es-icon es-icon-close01"></i></a></td>`);
         }
       }
     });

     this.selectedStudents.prepend($targetTr);

     this.arr.push(selectId);
  }

  selectedEleRemove(e) {
    let $target = $(e.target);
    let truename = e.target.innerHTML;
    let selectId = $target.parents('tr')[0].id;

    this.selectContainer.find('td[id = "`${selectId}`"]').closest('tr').show();

    let target = e.currentTarget;
    if (!confirm(Translator.trans('是否确定移除该老师'))) {
      return;
    }
    $.get($(target).data('url'), () => {
      $(target).closest('tr').remove();
    }); 
  }
}

new shareSelect().init();

