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
              <header class="card-header"><div class="card-title">添加[xx]</div></header>
              <div class="card-body">
                <form class="site-form submit-ajax" action="{:url('add')}" method="post">
                  <div class="mb-3">
                    <label for="title" class="form-label"><span class="text-danger">*</span> 标题</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="请输入标题" value="" />
                  </div>

                  <button type="submit" class="btn btn-primary">添加</button>
                  <button type="button" class="btn btn-default" onclick="javascript:history.back(-1);return false;">返回</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    {include file='public/footer.html'}
</body>
</html>
