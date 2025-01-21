<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <title>{{ $title ?? config_cache('app.name', 'Pixelfed') }}</title>
    <meta property="og:site_name" content="{{ config_cache('app.name', 'pixelfed') }}">
    <meta property="og:title" content="{{ $title ?? config_cache('app.name', 'pixelfed') }}">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{$profile['url']}}">
    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="{{url('/img/favicon.png?v=2')}}">
    <link rel="apple-touch-icon" type="image/png" href="{{url('/img/favicon.png?v=2')}}">
    <style>.btn,img{vertical-align:middle}.btn,a{background-color:transparent}.btn:hover,a{text-decoration:none}.card,.col-4,.info-overlay,.square{position:relative}*,::after,::before{box-sizing:border-box}p{margin-top:0;margin-bottom:1rem}a{color:#2c78bf}a:hover{color:#1e5181;text-decoration:underline}img{border-style:none}.small{font-size:.875em;font-weight:400}.btn,body{font-size:.9rem;font-weight:400;line-height:1.6;color:#212529}.row{display:flex;flex-wrap:wrap;margin-right:-15px;margin-left:-15px}.col-4{width:100%;padding-right:15px;padding-left:15px;flex:0 0 33.33333333%;max-width:33.33333333%}.btn{display:inline-block;text-align:center;-webkit-user-select:none;-moz-user-select:none;user-select:none;border:1px solid transparent;padding:.375rem .75rem;border-radius:.25rem;transition:color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out}.card,body{display:flex}@media (prefers-reduced-motion:reduce){.btn{transition:none}}.btn:hover{color:#212529}.btn:focus{outline:0;box-shadow:0 0 0 .2rem rgba(44,120,191,.25)}.btn-primary:focus,.btn-primary:not(:disabled):not(.disabled):active:focus{box-shadow:0 0 0 .2rem rgba(76,140,201,.5)}.btn:disabled{opacity:.65}.btn:not(:disabled):not(.disabled){cursor:pointer}.btn-primary,.btn-primary:disabled{color:#fff;background-color:#2c78bf;border-color:#2c78bf}.btn-primary:focus,.btn-primary:hover{background-color:#2564a0;border-color:#225e96;color:#fff}.btn-primary:not(:disabled):not(.disabled):active{color:#fff;background-color:#225e96;border-color:#20578b}.btn-sm{padding:.25rem .5rem;font-size:.7875rem;line-height:1.5;border-radius:.2rem}.card{flex-direction:column;min-width:0;word-wrap:break-word;background-color:#fff;background-clip:border-box;border:1px solid rgba(0,0,0,.125);border-radius:.25rem}.card-body{flex:1 1 auto;min-height:1px;padding:1.25rem}.card-footer,.card-header{padding:.75rem 1.25rem;background-color:#fff}.card-header{margin-bottom:0;border-bottom:1px solid rgba(0,0,0,.125)}.card-header:first-child{border-radius:calc(.25rem - 1px) calc(.25rem - 1px) 0 0}.card-footer{border-top:1px solid rgba(0,0,0,.125)}.card-footer:last-child{border-radius:0 0 calc(.25rem - 1px) calc(.25rem - 1px)}.bg-white{background-color:#fff!important}.border{border:1px solid #dee2e6!important}.d-flex{display:flex!important}.d-inline-flex{display:inline-flex!important}.justify-content-between{justify-content:space-between!important}.align-items-center{align-items:center!important}.shadow-none{box-shadow:none!important}.mb-0{margin-bottom:0!important}.mb-1{margin-bottom:.25rem!important}.mt-2{margin-top:.5rem!important}.mt-4{margin-top:1.5rem!important}.px-0{padding-right:0!important;padding-left:0!important}.py-1{padding-top:.25rem!important}.pr-1,.px-1{padding-right:.25rem!important}.pb-1,.py-1{padding-bottom:.25rem!important}.px-1{padding-left:.25rem!important}.pl-2{padding-left:.5rem!important}.px-4{padding-right:1.5rem!important;padding-left:1.5rem!important}.text-center{text-align:center!important}.text-uppercase{text-transform:uppercase!important}.font-weight-bold{font-weight:700!important}a.text-dark:focus,a.text-dark:hover{color:#000!important}a.text-muted:focus,a.text-muted:hover{color:#454b50!important}.text-muted{color:#6c757d!important}@media print{*,::after,::before{text-shadow:none!important;box-shadow:none!important}a:not(.btn){text-decoration:underline}img{page-break-inside:avoid}p{orphans:3;widows:3}body{min-width:992px!important}}body{margin:0;text-align:left;background-color:rgba(247,251,253,.4705882353);min-height:100vh;flex-flow:column;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}.text-dark{color:#212529!important}.square{width:100%}.square::after{content:"";display:block;padding-bottom:100%}.square-content{position:absolute;width:100%;height:100%;background-repeat:no-repeat;background-size:cover;background-position:50%}@media (max-width:576px){.card-md-border-0{border-width:0!important;border-radius:0!important}.card-md-rounded-0{border-width:1px 0;border-radius:0!important}}.card{box-shadow:0 2px 6px 0 hsla(0,0%,0%,.2);border:none}body.embed-card{background:#fff!important;margin:0;padding-bottom:0}.status-card-embed{box-shadow:none;border-radius:4px;overflow:hidden}.avatar{border-radius:100%}.info-overlay-text-label{display:flex;justify-content:flex-end;position:absolute;width:100%;height:100%;padding:10px;h5{z-index:2;margin:0;svg{fill:#fff;width:25px;height:25px;transition:width100msease-out;@media(min-width:768px){width:30px;height:30px;}@media(min-width:1080px){width:40px;height:40px;}}}}</style>
</head>
<body class="bg-white">
    <div class="embed-card">
        <div class="card status-card-embed card-md-rounded-0 border">
            <div class="card-header d-inline-flex align-items-center justify-content-between bg-white">
                <div>
                    <img src="{{$profile['avatar']}}" width="32" height="32" class="avatar" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
                    <a class="username font-weight-bold pl-2 text-dark" target="_blank" href="{{$profile['url']}}">
                        {{$profile['username']}}
                    </a>
                </div>
                <div>
                    <a class="small font-weight-bold text-muted pr-1" href="{{config('app.url')}}" target="_blank">{{config('pixelfed.domain.app')}}</a>
                    <img src="/img/pixelfed-icon-color.svg" width="26" height="26">
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
                        <p class="mb-0"><a href="{{config('app.url')}}/i/intent/follow?user={{$profile['username']}}" class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold" target="_blank">Follow</a></p>
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
    <script type="text/javascript">
        window.addEventListener("message", e=>{const t=e.data||{};});
        document.querySelectorAll('.caption-container a').forEach(function(i) {i.setAttribute('target', '_blank');});
        function formatCount(count = 0, locale = 'en-GB', notation = 'compact') {
            if(count < 1) {
                return 0;
            }
            return new Intl.NumberFormat(locale, { notation: notation , compactDisplay: "short" }).format(count);
        }
        function generateElements(html) {
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            return template.content.children;
        }
        document.querySelectorAll('.prettyCount').forEach(function(i) {
            i.innerText = formatCount(i.getAttribute('data-count'));
        });
        fetch("{{config('app.url')}}/api/pixelfed/v1/accounts/{{$profile['id']}}/statuses?only_media=true&limit=24")
        .then(res => res.json())
        .then(res => {
            let parent = document.querySelector('.embed-row');
            res.filter(post => ['photo', 'photo:album'].includes(post.pf_type) && !post.sensitive && post.visibility === 'public')
               .slice(0, 9)
               .forEach((post, idx) => {
                   let mediaUrl = post.media_attachments[0].preview_url ? post.media_attachments[0].preview_url : post.media_attachments[0].url;
                   let html = post.pf_type === 'photo:album' ?
                   `<div class="col-4 mt-2 px-0"><a class="card info-overlay card-md-border-0 px-1 shadow-none" href="${post.url}" target="_blank"><div class="square"><div class="square-content" style="background-image: url('${mediaUrl}')"><div class="info-overlay-text-label"><h5><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M160 80H512c8.8 0 16 7.2 16 16V320c0 8.8-7.2 16-16 16H490.8L388.1 178.9c-4.4-6.8-12-10.9-20.1-10.9s-15.7 4.1-20.1 10.9l-52.2 79.8-12.4-16.9c-4.5-6.2-11.7-9.8-19.4-9.8s-14.8 3.6-19.4 9.8L175.6 336H160c-8.8 0-16-7.2-16-16V96c0-8.8 7.2-16 16-16zM96 96V320c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H160c-35.3 0-64 28.7-64 64zM48 120c0-13.3-10.7-24-24-24S0 106.7 0 120V344c0 75.1 60.9 136 136 136H456c13.3 0 24-10.7 24-24s-10.7-24-24-24H136c-48.6 0-88-39.4-88-88V120zm208 24a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg></h5></div></div></div></a></div>` :
                   `<div class="col-4 mt-2 px-0"><a class="card info-overlay card-md-border-0 px-1 shadow-none" href="${post.url}" target="_blank"><div class="square"><div class="square-content" style="background-image: url('${mediaUrl}')"></div></div></a></div>`;
                   let el = document.createElement('div');
                   el.innerHTML = html;
                   parent.appendChild(el.firstChild);
               });
        })
        window.addEventListener("message", e => {
            const t = e.data || {};
            if (window.parent && t.type === 'setHeight') {
                updateHeight(t.id)
            }
        });

        function updateHeight(id) {
            setTimeout(() => {
                window.parent.postMessage({
                    type: 'setHeight',
                    id: id,
                    height: document.documentElement.scrollHeight
                }, "*");
            }, 2500)
        }

    </script>
</body>
</html>
