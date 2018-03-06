import notify from 'common/notify';
import sortList from 'common/sortable';

//学生表格收缩
let $listHead = $('.js-table-list-head');
let $listDelateBtn = $listHead.find('.js-delate-list');
let $singleDelateBtn = $('.js-delate-single');

$listHead.on('click', (e) => {
  let target = e.currentTarget;
  $(target).nextUntil('.js-table-list-head', 'tr').slideToggle();
  $(target).find('.js-toggle-icon').toggle();
  e.stopPropagation();
})

$listDelateBtn.on('click', (e) => {
  let target = e.currentTarget;
  e.stopPropagation();
  if (!confirm(Translator.trans('是否确定删除该分组？'))) {
    return;
  }
  $.post($(target).data('url'), () => {
    console.log($(target).closest('tr').nextUntil('.js-table-list-head', 'tr'));
    $('.ungroup').after($(target).closest('tr').nextUntil('.js-table-list-head', 'tr'));
    $(target).closest('tr').remove();
    window.location.reload();
  })
})

$singleDelateBtn.on('click', (e) => {
  let target = e.currentTarget;
  if (!confirm(Translator.trans('是否确定移除该学生？'))) {
    return;
  }
  $.post($(target).data('url'), () => {
    $(target).closest('tr').remove();
    let $studentNum = $('.js-course-student-num'),
        studentNum = $studentNum.text();
    $studentNum.text(--studentNum);
  })
})

//学生名单拖拽

$('.cz-course-student-list').find('tr.js-table-list-head').next('tr').addClass('group-leader');

const taskSortable = (list) => {
  if ($(list).length) {
    sortList({
      group: '.cz-course-student-list',
      element: list,
      containerSelector:'table',
      ajax: false,
      itemPath: ' > tbody',
      itemSelector: 'tr.drag',
      placeholder: '<tr class="placeholder"><td></td><td></td><td></td><td></td><td></td></tr>'
    }, (data) => {
      let arr = [];
      $(list).find('tr').each((i,el)=> {
        let data1 = $(el).attr('id');
        arr.push(data1);
        return data1;
      });

      $(list).find('tr').removeClass('group-leader').removeAttr("data-stu");
      $.post($(list).data('sortUrl'), { ids: arr }, (response) => {
        $(list).find('tr.js-table-list-head').next('tr').addClass('group-leader').attr('data-stu', 1);
      });
      if ($('.js-default-group').find('.js-search-content').length === 0) {
        $('.js-default-group').remove();
      }
    });
  }
}

$('#js-search-btn').click(function () {
    let searchText = $('#js-search').val();
    if (searchText.length == 0 || $(".js-search-content:contains('"+searchText+"')").length <= 0) {
      $(".js-search-content").removeClass("bg-sign-color").addClass("bg-source-color");
      return;
    }

    $(".js-search-content:not(:contains('"+searchText+"'))").removeClass("bg-sign-color").addClass("bg-source-color");
    $(".js-search-content:contains('"+searchText+"')").addClass("bg-sign-color");
    $('body').animate({scrollTop: $(".js-search-content:contains("+searchText+"):eq(0)").offset().top}, 5);
});

let sortableList = 'table.cz-course-student-list';
taskSortable(sortableList);
