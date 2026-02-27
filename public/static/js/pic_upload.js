function previewImg(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function (im) {
            $.post(GV.pic_upload_api, {base64: im.target.result, api: 'thumb'}, function (res) {
                if (res.status === 1) {
                    removeImg($('#pic_img'));
                    $('#thumb').val(res.data.path);
                    $('#pic_img').attr('src', res.data.path).show();
                    $('#pic_remove').show();
                }
            }, 'json');
        };
        reader.readAsDataURL(input.files[0]);
        return 1;
    }
}

// 删除图片
function removeImg(e) {
    var p = e.attr('src');
    if (p === '') {
        return;
    }
    $('#thumb').val('');
    $.post(GV.pic_del_api, {pic: p}, function (ret) {}, 'json');
}

$('#pic_remove').on('click', function () {
    removeImg($('#pic_img'));
    $('#pic_img').attr('src', '').hide();
    $('#pic_remove').hide();
});