<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ $title ?? config('app.name', 'Pixelfed') }}</title>

    <meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name', 'pixelfed') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{$profile['url']}}">
    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <style type="text/css">
      body.embed-card {
          background: #fff !important;
          margin: 0;
          padding-bottom: 0;
      }
      .status-card-embed {
        box-shadow: none;
        border-radius: 4px;
        overflow: hidden;
      }
    </style>
</head>
<body class="bg-white">
  <div class="embed-card">
  <div class="card status-card-embed card-md-rounded-0 border">
    <div class="card-header d-inline-flex align-items-center justify-content-between bg-white">
      <div>
        <img src="{{$profile['avatar']}}" width="32px" height="32px" style="border-radius: 32px;">
        <a class="username font-weight-bold pl-2 text-dark" target="_blank" href="{{$profile['url']}}">
          {{$profile['username']}}
        </a>
      </div>
      <div>
        <a class="small font-weight-bold text-muted pr-1" href="{{config('app.url')}}" target="_blank">{{config('pixelfed.domain.app')}}</a>
        <img src="/img/pixelfed-icon-color.svg" width="26px">
      </div>
    </div>
    <div class="card-body pb-1">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-center">
          <p class="mb-0 font-weight-bold prettyCount" data-count="{{$profile['statuses_count']}}"></p>
          <p class="mb-0 text-muted text-uppercase small font-weight-bold">Posts</p>
        </div>
        <div class="text-center">
          <p class="mb-0 font-weight-bold prettyCount" data-count="{{$profile['followers_count']}}"></p>
          <p class="mb-0 text-muted text-uppercase small font-weight-bold">Followers</p>
        </div>
        <div class="text-center">
          <p class="mb-0"><a href="/i/intent/follow?user={{$profile['username']}}" class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold" target="_blank">Follow</a></p>
        </div>
      </div>
      <div class="row mt-4 mb-1 embed-row"></div>
    </div>
    <div class="card-footer bg-white">
      <p class="text-center mb-0">
        <a href="{{$profile['url']}}" class="font-weight-bold" target="_blank">View More Posts</a>
      </p>
    </div>
  </div>
  </div>
  <script type="text/javascript" src="{{mix('js/manifest.js')}}"></script>
  <script type="text/javascript" src="{{mix('js/vendor.js')}}"></script>
  <script type="text/javascript" src="{{mix('js/app.js')}}"></script>
  <script type="text/javascript">
      window.addEventListener("message", e=>{const t=e.data||{};});
  </script>
  <script type="text/javascript">document.querySelectorAll('.caption-container a').forEach(function(i) {i.setAttribute('target', '_blank');});</script>
  <script type="text/javascript">
    document.querySelectorAll('.prettyCount').forEach(function(i) {
      i.innerText = App.util.format.count(i.getAttribute('data-count'));
    });
  </script>
  <script type="text/javascript">
  	axios.get('/api/pixelfed/v1/accounts/{{$profile['id']}}/statuses', {
  		params: {
  			only_media: true,
  			limit: 20
  		}
  	})
  	.then(res => {
		let parent = $('.embed-row');
  		res.data
  		.filter(res => res.pf_type == 'photo')
  		.slice(0, 9)
  		.forEach(post => {
			let el = `<div class="col-4 mt-2 px-0">
				<a class="card info-overlay card-md-border-0 px-1 shadow-none" href="${post.url}" target="_blank">
					<div class="square">
						<div class="square-content" style="background-image: url('${post.media_attachments[0].url}')">
						</div>
					</div>
				</a>
			</div>`;
			parent.append(el);
  		})
  	})
    .finally(() => {
        window.parent.postMessage({type:"setHeight",id:0,height:document.getElementsByTagName("html")[0].scrollHeight},"*");
        setTimeout(() => {
            window.parent.postMessage({type:"setHeight",id:0,height:document.getElementsByTagName("html")[0].scrollHeight},"*");
        }, 5000);
    })
  </script>
</body>
</html>
