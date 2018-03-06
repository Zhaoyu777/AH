define(function(require, exports, module) {

	var Notify = require('common/bootstrap-notify');

    module.exports = function($element) {

        $element.on('click', '[data-role=batch-delete]', function() {
        	var $btn = $(this);
        		name = $btn.data('name');

            var ids = [];
            $element.find('[data-role=batch-item]:checked').each(function(){
                ids.push(this.value);
            });

            if (ids.length == 0) {
                Notify.danger(Translator.trans('未选中任何') + name);
                return ;
            }

            if (!confirm(Translator.trans('确定要删除选中的') + ids.length + Translator.trans('条') + name + Translator.trans('吗？'))) {
                return ;
            }

            $element.find('.btn').addClass('disabled');

            Notify.info(Translator.trans('正在删除') + name + Translator.trans('，请稍等。'), 60);
            
            $.post($btn.data('url'), {ids:ids}, function(response){
                window.location.reload();
            });

        });

    };

});