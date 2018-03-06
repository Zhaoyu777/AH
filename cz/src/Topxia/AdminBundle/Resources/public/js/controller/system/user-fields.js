define(function(require, exports, module) {
    var Validator = require('bootstrap.validator');
    require('common/validator-rules').inject(Validator);
    var Notify = require('common/bootstrap-notify');
    require('jquery.sortable');
    require('/bundles/topxiaadmin/js/controller/system/common');

    exports.run = function() {

    	var $form = $('#field-form');
        var validator = new Validator({
            element: $form,
            onFormValidated: function(error, results, $form) {
                if (error) {
                    return false;
                }

                $('#add-btn').button('submiting').addClass('disabled');
            }
		});

        var titleArr = [Translator.trans('真实姓名'),Translator.trans('手机号码'),Translator.trans('QQ'),Translator.trans('所在公司'),Translator.trans('身份证号码'),Translator.trans('性别'),Translator.trans('职业'),Translator.trans('微博'),Translator.trans('微信')];
        $('#add-btn').on('click', function() {
            var field_title = $('input[name="field_title"]').val();
            
            if($.inArray(field_title, titleArr) >= 0 )
            {
                Notify.danger(Translator.trans('请勿添加与默认字段相同的自定义字段！'))
                return false;
            }
        });

        validator.addItem({
            element: '[name="field_title"]',
            required: true,
            rule:'minlength{min:2} maxlength{max:20}'
        });

        validator.addItem({
            element: '[name="field_seq"]',
            required: true,
            rule:'positive_integer'
        });

        validator.addItem({
            element: '[name="field_type"]',
            required: true,
            errormessageRequired: Translator.trans('请选择字段类型')
        });


        $(".fill-userinfo-list").sortable({
          'distance': 20
        });

        $('#field_type').on('change',function(){
            
            $('#type_num').html($(this).children('option:selected').attr('num'));
        });

        $('#show-fields-list-btn').on('click',function(){
            $('#show-fields-list').show();
            $('#show-checked-fields-list').hide();
        })

        $("#hide-fields-list-btn").on("click", function() {
            $("#show-fields-list").hide();

            var fieldNameHtml = '';
            $('.fill-userinfo-list input:checkbox:checked').each(function(){
                var fieldName = $(this).closest('li').text();
                fieldNameHtml += '<button type="button" class="btn btn-default btn-xs">'+$.trim(fieldName)+'</button>&nbsp;';
            })

            $('#show-checked-fields-list .pull-left').html(fieldNameHtml);
            $("#show-checked-fields-list").show();
        });

        $('*[data-sms-validate]').on('click',function(){
            
            var isSmsCodeValidate = $(this).data('smsValidate');
            var smsEnabled = $('input[name="_cloud_sms"]').val();
            
            if ($(this).is(':checked')) {
                $(this).closest('li').siblings().find('*[data-sms-validate]').attr('checked',false);
                
                if (isSmsCodeValidate && smsEnabled == '0') {
                    Notify.danger(Translator.trans('admin.site.cloude_sms_enable_hint'));
                    return false;
                }
                $(this).attr('checked',true);

                $('input[name="mobileSmsValidate"]').val(isSmsCodeValidate);
            } else {
                $('input[name="mobileSmsValidate"]').val(0);
            }
        })

    };

});