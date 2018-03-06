import notify from 'common/notify';

let $coursePlanRadio = $('input[name="coursePlanRadio"]');
let $importCourseBtn = $('.js-btn-import-course');
let $importLessonBtn = $('.js-btn-import-leeson');
let $selectCourses = $("#select-courses");
let $selectLessons = $("#select-lessons");

function lessonLest(url){
  let $str;

  $.get(url, (data) => {
    let lessons = eval(data.lessons);

    $('.js-is-filter').hide();
    if (data.isFilter) {
      $('.js-is-filter').show();
    }

    if (lessons.length == 0) {
      $str += "<option>当前没有可选课次</option>";
    }
    for(let lesson in lessons){
      $str += `
        <option value='${lessons[lesson]['id']}'>
          ${lessons[lesson]['count']}
        </option>`;
    }

    $selectLessons.empty();
    $selectLessons.append($str);
  })
}

function CourseLest(url){
  let $str;

  $.get(url, (data) => {
    let $str;
    let $courses = eval(data);

    for(let course in $courses) {
      $str += `
        <option value='${$courses[course]['id']}' data-url='${$courses[course]['url']}'>
          ${$courses[course]['count']}
        </option>`;
    }

    if ($courses.length == 0) {
      $selectLessons.empty();
      $str += "<option>当前没有可选课程</option>";
      $selectLessons.append("<option>当前没有可选课次</option>");
    } else {
      lessonLest($courses[0]['url']);
    }

    $selectCourses.empty();
    $selectCourses.append($str);
  })
}

$coursePlanRadio.on('click', (e) => {
  let $target = $(e.currentTarget);
  CourseLest($target.data('url'));
});

$selectCourses.on('change', (e) => {
    let url = $selectCourses.find('option:selected').data("url");
    lessonLest(url);
});

$importCourseBtn.on('click', (e) => {
  let courseId = $selectCourses.val();
  let toCourseId = $('.js-course-id').val();
  let $target = $(e.currentTarget);
  let lessonIds = $('.lesson-ids').val();


  $.get(lessonIds, {
    fromCourseId:courseId,
    toCourseId:toCourseId
  }).done((data) => {

  let $str = data;
  console.log($str);

  if (!confirm(Translator.trans('是否导入:'+$str))) {
    return;
  }
  $importCourseBtn.button('loading');
  $.get($target.data('url'), {
    fromCourseId:courseId,
    toCourseId:toCourseId
  }).done((data) => {
    notify('success', Translator.trans('导入成功！'));
    window.location.href = data;
  }).fail((data) => {
    $importCourseBtn.button('reset');
    data = JSON.parse(data.responseText);
    notify('danger', data.error.message);
  });

  }).fail((data) => {
    $importCourseBtn.button('reset');
    data = JSON.parse(data.responseText);
    notify('danger', Translator.trans(data.error.message));
  });
})

$importLessonBtn.on('click', (e) => {
  let fromLessonId = $selectLessons.val();
  let toLessonId = $('.js-lesson-id').val();
  let $target = $(e.currentTarget);

  if (!confirm(Translator.trans('是否导入'))) {
    return;
  }
  $importLessonBtn.button('loading');
  $.get($target.data('url'), {
    fromLessonId:fromLessonId,
    toLessonId:toLessonId
  }).done((data) => {
    notify('success', Translator.trans('导入成功！'));
    window.location.href = data;
  }).fail((data) => {
    $importLessonBtn.button('reset');
    data = JSON.parse(data.responseText);
    notify('danger', data.error.message);
  });
})

$(function(){
   let url = $coursePlanRadio.data("url");
  CourseLest(url);
}()); 