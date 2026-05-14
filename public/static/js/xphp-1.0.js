var _modal;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
    }
});

function selectAll(checked) {
    $('.ids').prop('checked', checked);
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

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function stripScripts(html) {
    return html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
}

function ajaxGet(url, refresh) {
    if (refresh === undefined) refresh = true;
    $.get(url, function(res) {
        toast(res.msg);
        if (res.status === 1 && refresh) {
            setTimeout(function() {
                location.reload();
            }, 300);
        }
    }, 'json');
}

function ajaxConfirm(url, action, refresh) {
    action = action || '删除';
    if (refresh === undefined) refresh = true;
    var $existing = $('#confirmModal');
    if ($existing.length) {
        var existingModal = bootstrap.Modal.getInstance($existing[0]);
        if (existingModal) existingModal.dispose();
        $existing.remove();
    }
    var modalHtml = '<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-body">确认要' + escapeHtml(action) + '吗？</div><div class="modal-footer"><button type="button" class="btn btn-primary" id="confirmBtn">确认</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div></div></div></div>';
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
    $('.ids:checked').each(function() {
        ids.push($(this).val());
    });
    if (ids.length === 0) {
        toast('请选择ID');
        return;
    }
    var msg = '确认要' + escapeHtml(action) + '吗？<br/>' + escapeHtml(ids.toString());
    var $existing = $('#actionModal');
    if ($existing.length) {
        var existingModal = bootstrap.Modal.getInstance($existing[0]);
        if (existingModal) existingModal.dispose();
        $existing.remove();
    }
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
                }, 300);
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

function openModal(url, title, size) {
    title = title || '';
    size = size || '';
    var sizeClass = size ? 'modal-' + size : 'modal-lg';
    $.get(url, function(html) {
        var $formModal = $('#formModal');
        if (!_modal && $formModal.length) {
            _modal = new bootstrap.Modal($formModal[0]);
        }
        if (!_modal) {
            toast('模态框未初始化');
            return;
        }
        $('#formModalTitle').text(title);
        $('#formModalBody').html(stripScripts(html));
        $formModal.find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl').addClass(sizeClass);
        _modal.show();
    }).fail(function() {
        toast('请求失败，请稍后重试');
    });
}

$(document).on('submit', '.submit-ajax', function() {
    var $form = $(this);
    var url = $form.attr('action');
    var data = $form.serialize();
    var $btn = $form.find('[type="submit"]');
    var originalText = $btn.html();
    var inModal = $form.closest('#formModal').length > 0;
    $btn.prop('disabled', true).html('提交中…');
    $.post(url, data, function(res) {
        toast(res.msg);
        if (res.status === 1) {
            if (inModal) {
                var $formModal = $('#formModal');
                if ($formModal.length) {
                    $formModal.one('hidden.bs.modal', function() {
                        location.reload();
                    });
                }
                if (_modal) {
                    _modal.hide();
                } else if ($formModal.length) {
                    bootstrap.Modal.getInstance($formModal[0]).hide();
                } else {
                    location.reload();
                }
            } else {
                var $ref = $form.find('[name="referer"]');
                if ($ref.length) {
                    var referer = $ref.val();
                    if (referer && referer !== '__HISTORY__' && referer !== '__ROOT__/') {
                        setTimeout(function() { location.href = referer; }, 1500);
                    } else {
                        if (res.url) {
                            setTimeout(function() { location.href = res.url; }, 1500);
                        } else {
                            setTimeout(function() { location.reload(); }, 1500);
                        }
                    }
                } else {
                    if (res.url) {
                        setTimeout(function() { location.href = res.url; }, 1500);
                    } else {
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                }
            }
        } else {
            $btn.prop('disabled', false).html(originalText);
        }
    }, 'json').fail(function() {
        toast('请求失败');
        $btn.prop('disabled', false).html(originalText);
    });
    return false;
});
