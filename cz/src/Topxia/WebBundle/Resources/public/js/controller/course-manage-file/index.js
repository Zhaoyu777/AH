define(function(require, exports, module) {

    exports.run = function() {

        var $panel = $('#file-manage-panel');
	    require('../../util/batch-select')($panel);
	    //require('../../util/batch-delete')($panel);
        var Notify = require('common/bootstrap-notify'); 

        $panel.on('click', '.convert-file-btn', function(){
          console.log('re');
            $.post($(this).data('url'), function(response) {
                if (response.status == 'error') {
                    alert(response.message);
                } else {
                    window.location.reload();
                }
            }, 'json').fail(function(){
                alert(Translator.trans('文件转换提交失败，请重试！'));
            });
        });

        $('.tip').tooltip();

        $("#modal").modal({
            backdrop: 'static',
            keyboard: false,
            show: false
        });
        
        $("button", ".panel-heading").on('click',function(){
            var url = $(this).data("url");
            $("#modal").html('');
            $("#modal").modal('show');
            $.get(url, function(html){
                $("#modal").html(html);
            });
        })

        $("[rel='tooltip']").tooltip();

        asyncLoadFiles();

        $('[data-role=batch-delete]').click(function(){
            var flag = false;
            var ids = [];
            $('[data-role=batch-item]').each(function(){
                if($(this).is(":checked")) {
                    flag = true;
                    ids.push(this.value);
                }
            })

            if(flag) {
                $('#modal').html('');
                $('#modal').load($(this).data('url'),{ids:ids});
                $('#modal').modal('show');
            } else {
                Notify.danger(Translator.trans('未选中任何文件记录'));
                return ;
            }
        })
    };

    function asyncLoadFiles()
    {
        var fileIds = new Array();
        $('tbody [type=checkbox]').each(function(){
            if(!isNaN($(this).val())){
                fileIds.push($(this).val());
            }
        });

        if(fileIds.length==0){
          return ;
        }

        $.post($("#file-manage-panel").data("fileStatusUrl"),{'ids':fileIds.join(",")},function(data){
            if(!data||data.length==0){
                return ;
            }

            for(var i=0; i<data.length; i++){
                var file=data[i];
                if($.inArray(file.type, ['video','ppt','document'])>-1 &&  file.storage == 'cloud'){
                    if(file.convertStatus=='waiting'||file.convertStatus=='doing'){
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after("<span class='text-warning mr5 text-sm'>"+Translator.trans('正在文件格式转换')+"</span><br/>");
                    }else if(file.convertStatus=='error'){
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after("<span class='text-danger mr5 text-sm'>"+Translator.trans('文件格式转换失败')+"</span><br/>");
                    }else if(file.convertStatus=='none'){
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:last').after("<span class='label label-default mr5 tip'>"+Translator.trans('未转码')+"</span>");
                    }else if(file.convertStatus=='success'){
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:last').after("<span class='label label-success mr5 tip'>"+Translator.trans('已转码')+"</span>");
                    }

                }
                if(file.type == 'video' && file.metas2) {
                    if(file.metas2.shd) {
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after('<span class="label label-info mr5 tip">'+Translator.trans('超清')+'</span>');
                    } else if(file.metas2.hd){
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after('<span class="label label-info mr5 tip">'+Translator.trans('高清')+'</span>');
                    } else if(file.metas2.sd) {
                        $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after('<span class="label label-info mr5 tip">'+Translator.trans('标清')+'</span>');
                    }
                }

                if(file.type == 'video' && file.metas && file.metas.caption ){
                    $("#upload-file-tr-"+file.id).find('a:first ~ br:first').after('<span class="label label-primary tip">'+Translator.trans('字幕')+'</span>');
                }
            }
        });
    }


});