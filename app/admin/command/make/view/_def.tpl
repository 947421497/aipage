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
              <header class="card-header"><div class="card-title">标题</div></header>
              <div class="card-body">
                内容
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    {include file='public/footer.html'}
</body>
</html>
