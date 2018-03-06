let lock = false;
load();

$(window).scroll(function() {
  const scrollTop = $(this).scrollTop();
  const scrollHeight = $(document).height();
  const windowHeight = $(this).height();
  if(scrollTop + windowHeight === scrollHeight){
    loadTable();
  }
});

function loadTable() {
  const page = $(".js-lesson-page").val();
  const url = $(`.js-task-detail`).data('url');
  if (!url || lock) {
    return;
  }

  lock = true;
  $.get(url, {page: page}, (data) => {
    if(data) {
      $(".js-table-responsive").append(data);
      $(".js-lesson-page").val(parseInt(page)+1);
    }
    lock = false;
  });
}

function load() {
  loadTable();
}
