{include file='public/_head.html'}
</head>
<body>
<div id="lyear-preloader" class="loading">
  <div class="ctn-preloader">
    <div class="round_spinner">
      <div class="spinner"></div>
      <img src="__STATIC__/images/loading-logo.png" alt="">
    </div>
  </div>
</div>
<div class="lyear-layout-web">
  <div class="lyear-layout-container">
    {include file='public/sidebar.html'}
    {include file='public/_header.html'}

    <main class="lyear-layout-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <header class="card-header"><div class="card-title">[xx]管理</div></header>
              <div class="card-body">
                <div class="card-search mb-2-5">
                  <form class="search-form" method="get" action="{:url('index')}">
                    <div class="row">
                      <div class="col-md-4">
                        <div class="row">
                          <label class="col-sm-4 col-form-label">标题</label>
                          <div class="col-sm-8">
                            <input type="text" name="title" value="{:input('title','','clear_html')}" class="form-control" placeholder="搜索标题"/>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-1">搜索</button>
                        <a href="{:url('index')}" class="btn btn-default">全部</a>
                      </div>
                    </div>
                  </form>
                </div>
                <div class="card-btns mb-2-5">
                  <a href="{:url('add')}" class="btn btn-primary me-1"><i class="mdi mdi-plus"></i> 添加</a>
                  <button type="button" class="btn btn-success me-1" onclick="actionConfirm('启用','{:url('state?params=status-1')}');"><i class="mdi mdi-check"></i> 启用</button>
                  <button type="button" class="btn btn-warning me-1" onclick="actionConfirm('停用','{:url('state?params=status-0')}');"><i class="mdi mdi-block-helper"></i> 停用</button>
                  <button type="button" class="btn btn-danger" onclick="actionConfirm('删除', '{:url('del')}');"><i class="mdi mdi-window-close"></i> 删除</button>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                    <tr>
                      <th><div class="form-check"><input class="form-check-input" type="checkbox" id="check-all"><label class="form-check-label" for="check-all"></label></div></th>
                      <th>ID</th>
                      <th>标题</th>
                      <th>状态</th>
                      <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $list as $vo}
                    <tr>
                      <td><div class="form-check"><input type="checkbox" class="form-check-input ids" name="ids[]" value="{$vo.id}" id="ids-{$vo.id}"><label class="form-check-label" for="ids-{$vo.id}"></label></div></td>
                      <td>{$vo.id}</td>
                      <td><a href="{:url('edit',['id'=>$vo['id']])}">{$vo.title}</a></td>
                      <td>
                        {if $vo['status']==1:}
                        <a href="javascript:ajaxConfirm('{:url('state?params=status-0',['ids'=>$vo['id']])}','停用',2);" class="text-success" data-bs-toggle="tooltip" title="点击停用">已启用</a>
                        {else:}
                        <a href="javascript:ajaxConfirm('{:url('state?params=status-1',['ids'=>$vo['id']])}','启用',2);" class="text-secondary" data-bs-toggle="tooltip" title="点击启用">已停用</a>
                        {/if}
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a href="{:url('edit',['id'=>$vo['id']])}" class="btn btn-default" data-bs-toggle="tooltip" title="编辑"><i class="mdi mdi-pencil"></i></a>
                          <a href="javascript:ajaxConfirm('{:url('del',['ids'=>$vo['id']])}','删除',2);" class="btn btn-default" data-bs-toggle="tooltip" title="删除"><i class="mdi mdi-window-close"></i></a>
                        </div>
                      </td>
                    </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
                {empty $list->toArray():}
                <p class="text-center text-muted py-3">暂无记录</p>
                {else:}
                <ul class="pagination">{$list->links()|raw}</ul>
                {/empty}
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    {include file='public/footer.html'}
</body>
</html>
