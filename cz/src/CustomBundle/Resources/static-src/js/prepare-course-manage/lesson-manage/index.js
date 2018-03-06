import notify from 'common/notify';
(function() {
  function refreshPage(event) {
    if(event.key === 'editPlan' && event.newValue === 'finish' && event.oldValue !== 'finish') {
      window.removeEventListener('storage', refreshPage);
      window.localStorage.removeItem('editPlan');
      window.location.reload();
    }
  }

  window.addEventListener('storage', refreshPage)
})();

$('.js-start-course').on('click', (event) => {
  let $btn = $(event.currentTarget);
  const newHref = window.open("", "_blank");

  $.get($btn.data('check-url'), (res) => {
    if (res.status === "success") {
      newHref.location = res.data.url;
    } else {
      newHref.close();
      notify('danger', Translator.trans('已经有课次正在上课中'));
    }
  });
});