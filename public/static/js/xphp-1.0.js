var $btn;
var $form;
var $tip;

function selectAll(checked) {
    $('input[name="ids"]').prop('checked', checked);
}

function ajaxGet(url, refresh) {
    refresh = refresh || 1;
    $.get(url, function(res) {
        toast(res.msg);
        if (res.status === 1 && refresh === 1) {
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
    }, 'json');
}

function toast(msg, timer) {
    timer = timer || 2;
    $.notify({
        message: msg
    }, {
        type: 'info',
        placement: { from: 'top', align: 'right' },
        z_index: 10800,
        delay: timer * 1000,
        animate: {
            enter: 'animate__animated animate__fadeInUp',
            exit: 'animate__animated animate__fadeOutDown'
        }
    });
}

function ajaxConfirm(url, action, refresh) {
    action = action || '删除';
    refresh = refresh || 1;
    var modalHtml = '<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-body">确认要' + action + '吗？</div><div class="modal-footer"><button type="button" class="btn btn-primary" id="confirmBtn">确认</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div></div></div></div>';
    $('body').append(modalHtml);
    var confirmModalEl = document.getElementById('confirmModal');
    var confirmModal = new bootstrap.Modal(confirmModalEl);
    $('#confirmBtn').on('click', function() {
        confirmModal.hide();
        ajaxGet(url, refresh);
    });
    $(confirmModalEl).on('hidden.bs.modal', function() {
        $(this).remove();
    });
    confirmModal.show();
}

function actionConfirm(action, url) {
    var ids = [];
    $('tbody input').each(function(index, el) {
        if ($(this).prop('checked')) {
            ids.push($(this).val());
        }
    });
    if (ids.length === 0) {
        toast('请选择ID');
        return;
    }
    var msg = '确认要' + action + '吗？<br/>' + ids.toString();
    var modalHtml = '<div class="modal fade" id="actionModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-body">' + msg + '</div><div class="modal-footer"><button type="button" class="btn btn-primary" id="actionBtn">确认</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div></div></div></div>';
    $('body').append(modalHtml);
    var actionModalEl = document.getElementById('actionModal');
    var actionModal = new bootstrap.Modal(actionModalEl);
    $('#actionBtn').on('click', function() {
        actionModal.hide();
        $.post(url, {ids: ids.toString()}, function(res) {
            if (res.status === 1) {
                toast(res.msg);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                toast(res.msg);
            }
        }, 'json');
    });
    $(actionModalEl).on('hidden.bs.modal', function() {
        $(this).remove();
    });
    actionModal.show();
}

function remoteUrl(url) {
    $.get(url, function(res) {
        if (res.status === 1) {
            $('#remoteModal .table').html(res.table);
            $('#remoteModal .modal-body').html('<pre>' + res.data + '</pre>');
        }
    }, 'json');
    var remoteModal = new bootstrap.Modal(document.getElementById('remoteModal'));
    remoteModal.show();
}

$(function() {
    $('.submit-ajax').submit(function() {
        var $form = $(this);
        var url = $form.attr('action');
        var data = $form.serialize();
        var $btn = $form.find('[type="submit"]');
        var originalText = $btn.val();
        $btn.prop('disabled', true).val('提交中…');
        $.post(url, data, function(res) {
            toast(res.msg);
            if (res.status === 1) {
                var $ref = $form.find('[name="referer"]');
                if ($ref.length) {
                    var referer = $ref.val();
                    if (referer && referer !== '__HISTORY__' && referer !== '__ROOT__/') {
                        setTimeout(function() {
                            location.href = referer;
                        }, 2000);
                    } else {
                        var $history = $('[name="referer"]');
                        if ($history.length) {
                            history.go(-1);
                        } else {
                            if (res.url) {
                                setTimeout(function() {
                                    location.href = res.url;
                                }, 2000);
                            } else {
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        }
                    }
                } else {
                    if (res.url) {
                        setTimeout(function() {
                            location.href = res.url;
                        }, 2000);
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                }
            } else {
                $btn.prop('disabled', false).val(originalText);
            }
        }, 'json').fail(function() {
            toast('请求失败');
            $btn.prop('disabled', false).val(originalText);
        });
        return false;
    });
});
