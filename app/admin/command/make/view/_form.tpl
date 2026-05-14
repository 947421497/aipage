<form class="site-form submit-ajax" action="{:url($is_edit ? 'edit' : 'add')}" method="post">
{if $is_edit:}
<input type="hidden" name="id" value="{$vo.id}" />
{/if}
  <div class="mb-3">
    <label for="title" class="form-label"><span class="text-danger">*</span> 标题</label>
    <input type="text" id="title" name="title" class="form-control" placeholder="请输入标题" value="{$vo.title|default=''}" />
  </div>
  <div class="mb-3">
    <button type="submit" class="btn btn-primary">{if $is_edit:}确定{/if}{if !$is_edit:}添加{/if}</button>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">取消</button>
  </div>
</form>
