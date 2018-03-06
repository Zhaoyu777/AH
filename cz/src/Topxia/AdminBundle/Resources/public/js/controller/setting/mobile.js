define(function(require, exports, module) {

    var Notify = require('common/bootstrap-notify');
    var WebUploader = require('edusoho.webuploader');
    require('es-ckeditor');

    exports.run = function() {

      var $form = $("#mobile-form");
      if ($('#mobile-splash1-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-splash1-upload'
        });

        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-splash1-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-splash1-container").html('<img src="' + response.url + '">');
            $form.find('[name=splash1]').val(response.path);
            $("#mobile-splash1-remove").show();
            Notify.success(Translator.trans('上传网校启动图1成功！'));
          });
        });

        $("#mobile-splash1-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-splash1-container").html('');
            $form.find('[name=splash1]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校启动图1成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校启动图1失败！'));
          });
        });
      }

      if ($('#mobile-splash2-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-splash2-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-splash2-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-splash2-container").html('<img src="' + response.url + '">');
            $form.find('[name=splash2]').val(response.path);
            $("#mobile-splash2-remove").show();
            Notify.success(Translator.trans('上传网校启动图2成功！'));
          });
        });

        $("#mobile-splash2-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-splash2-container").html('');
            $form.find('[name=splash2]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校启动图2成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校启动图2失败！'));
          });
        });
      }

      if ($('#mobile-splash3-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-splash3-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-splash3-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-splash3-container").html('<img src="' + response.url + '">');
            $form.find('[name=splash3]').val(response.path);
            $("#mobile-splash3-remove").show();
            Notify.success(Translator.trans('上传网校启动图3成功！'));
          });
        });

        $("#mobile-splash3-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-splash3-container").html('');
            $form.find('[name=splash3]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校启动图3成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校启动图3失败！'));
          });
        });
      }

      if ($('#mobile-splash4-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-splash4-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-splash4-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-splash4-container").html('<img src="' + response.url + '">');
            $form.find('[name=splash4]').val(response.path);
            $("#mobile-splash4-remove").show();
            Notify.success(Translator.trans('上传网校启动图4成功！'));
          });
        });

        $("#mobile-splash4-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-splash4-container").html('');
            $form.find('[name=splash4]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校启动图4成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校启动图4失败！'));
          });
        });
      }

      if ($('#mobile-splash5-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-splash5-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-splash5-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-splash5-container").html('<img src="' + response.url + '">');
            $form.find('[name=splash5]').val(response.path);
            $("#mobile-splash5-remove").show();
            Notify.success(Translator.trans('上传网校启动图5成功！'));
          });
        });

        $("#mobile-splash5-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-splash5-container").html('');
            $form.find('[name=splash5]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校启动图5成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校启动图5失败！'));
          });
        });
      }

      if ($('#mobile-logo-upload').length) {
        var uploader = new WebUploader({
          element: '#mobile-logo-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#mobile-logo-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#mobile-logo-container").html('<img src="' + response.url + '">');
            $form.find('[name=logo]').val(response.path);
            $("#mobile-logo-remove").show();
            Notify.success(Translator.trans('上传网校LOGO成功！'));
          });
        });

        $("#mobile-logo-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#mobile-logo-container").html('');
            $form.find('[name=logo]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校LOGO成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校LOGO失败！'));
          });
        });

        group: 'default'
        CKEDITOR.replace('mobile_about', {
          toolbar: 'Simple',
          filebrowserImageUploadUrl: $('#mobile_about').data('imageUploadUrl')
        });
      }
      //

      if ($('#site-applogo-upload').length) {
        var uploader = new WebUploader({
          element: '#site-applogo-upload'
        });
        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#site-applogo-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#site-applogo-container").html('<img src="' + response.url + '">');
            $form.find('[name=applogo]').val(response.path);
            $("#mobile-applogo-remove").show();
            Notify.success(Translator.trans('上传app图标成功！'));
          });
        });

        $("#site-applogo-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#site-applogo-container").html('');
            $form.find('[name=applogo]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除网校app图标成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除网校app图标失败！'));
          });
        });
      }
      //

      if ($('#site-appcover-upload').length) {
        var uploader = new WebUploader({
          element: '#site-appcover-upload'
        });

        uploader.on('uploadSuccess', function (file, response) {
          var url = $("#site-appcover-upload").data("gotoUrl");
          $.post(url, response, function (data) {
            response = $.parseJSON(data);
            $("#site-appcover-container").html('<img src="' + response.url + '">');
            $form.find('[name=appcover]').val(response.path);
            $("#mobile-appcover-remove").show();
            Notify.success(Translator.trans('上传app图标成功！'));
          });
        });

        $("#site-appcover-remove").on('click', function () {
          if (!confirm(Translator.trans('确认要删除吗？'))) return false;
          var $btn = $(this);
          $.post($btn.data('url'), function () {
            $("#site-appcover-container").html('');
            $form.find('[name=appcover]').val('');
            $btn.hide();
            Notify.success(Translator.trans('删除app封面成功！'));
          }).error(function () {
            Notify.danger(Translator.trans('删除app封面失败！'));
          });
        });
      }
    };

});