define(function(require, exports, module) {

    var Validator = require('bootstrap.validator');
    require('es-ckeditor');
    require('common/validator-rules').inject(Validator);
    var Notify = require('common/bootstrap-notify');
    require('/bundles/topxiaadmin/js/controller/system/common');
    exports.run = function() {

        // group: 'default'
        CKEDITOR.replace('user_terms_body', {
            toolbar: 'Simple',
            filebrowserImageUploadUrl: $('#user_terms_body').data('imageUploadUrl')
        });


        $("input[name=register_protective]").change(function() {

            var type = $('input[name=register_protective]:checked').val();

            $('.register-help').hide();

            $('.' + type).show();

        });


        var validator = new Validator({
            element: '#auth-form',
            onFormValidated: function() {
                $('input[name="email_enabled"]').trigger('change')
            }
        });

        if ($('input[name="email_activation_title"]').length > 0) {
            validator.addItem({
                element: '[name="email_activation_title"]',
                required: true
            });
        }

        validator.addItem({
            element: '[name="email_enabled"]',
            required: true,
            rule: 'isEmailVerified'
        });

        Validator.addRule('isEmailVerified', function(options) {
            var checked = false;
            options.element.each(function(i, item) {
                if ($(item).val() == 'opened' && $(item).prop("checked")) {
                    checked = true;
                    return false;
                }
            });

            if (!checked) {
                $(".js-email-send-check, .js-email-status").addClass('hidden');
                return true;
            }
            if (app.arguments.emailVerified == 1) {
                $(".js-email-send-check").removeClass('hidden');
                $('.js-email-send-check').trigger('click');
            } else {
                $(".js-email-send-check").addClass('hidden');
            }
            return app.arguments.emailVerified == 1;
        }, Translator.trans('开启前,请先验证您的邮箱'))

        $('.js-email-send-check').on('click', function() {
            $(".js-email-status").removeClass().addClass('alert alert-info js-email-status').html(Translator.trans('正在检测.....'));

            $.ajax({
                    url: $('.js-email-send-check').data('url'),
                    timeout: 3500 // sets timeout to 3 seconds
                }).done(function(resp) {
                    if (resp.status) {
                        $('.js-email-status').removeClass('alert-info').addClass('alert-success').html('<span class="text-success">' + resp.message + '</span>');
                    } else {
                        $('input[name="email_enabled"][value="closed"]').prop("checked", true);
                        $('.js-email-send-check').addClass("hidden");
                        $('.js-email-status').removeClass('alert-info').addClass('alert-danger').html(Translator.trans('<span class="text-danger">邮件发送异常,请检查<a  target="_blank" href="' + $('.js-email-status').data('url') + '">邮件服务器设置</a>是否正确</span>'));
                    }
                })
                .fail(function(resp) {
                    console.log('fail');
                    $('input[name="email_enabled"][value="closed"]').prop("checked", true);
                    $('.js-email-send-check').addClass("hidden");
                    $('.js-email-status').removeClass('alert-info').addClass('alert-danger').html(Translator.trans('<span class="text-danger">邮件发送异常,请检查<a  target="_blank" href="' + $('.js-email-status').data('url') + '">邮件服务器设置</a>是否正确</span>'));
                })
        })



        $('.model').on('click', function() {

            var old_modle_value = $('.model.btn-primary').data('modle');
            $('.model').removeClass("btn-primary");
            $(this).addClass("btn-primary");
            var modle = $(this).data('modle');



            if (modle != 'email' || modle != 'email_or_mobile') {
                if ($("input[name='email_enabled']").parents('.form-group').hasClass('has-error')) {
                    $("input[name='email_enabled'][value='closed']").prop('checked', true);
                     validator.query('[name="email_enabled"]').execute();
                }
            }
            if (modle == 'mobile' || modle == 'email_or_mobile') {
                if ($('input[name=_cloud_sms]').val() != 1) {
                    $('.model').removeClass("btn-primary");
                    $('[data-modle="' + old_modle_value + '"]').addClass("btn-primary");
                    modle = old_modle_value;

                    Notify.danger(Translator.trans("请先到【管理后台】-【教育云】-【云短信设置】中开启云短信哦~"));
                }
            }

            $('[name="register_mode"]').val(modle);
            if (modle == 'email' || modle == 'email_or_mobile') {
                $('.email-content').removeClass('hidden');
            } else {
                $('.email-content').addClass('hidden');
            }

        });
    };

});