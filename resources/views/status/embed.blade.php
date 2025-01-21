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
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{$status['url']}}">
    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
    <style type="text/css">hr,p{margin-bottom:1rem}.small,body{font-weight:400}.card,body{display:flex}*,::after,::before{box-sizing:border-box}p{margin-top:0}a{color:#2c78bf;text-decoration:none;background-color:transparent}a:hover{color:#1e5181;text-decoration:underline}img{vertical-align:middle;border-style:none}hr{box-sizing:content-box;height:0;overflow:visible;margin-top:1rem;border:0;border-top:1px solid rgba(0,0,0,.1)}.small{font-size:.875em}.card{position:relative;flex-direction:column;min-width:0;word-wrap:break-word;background-color:#fff;background-clip:border-box;border:1px solid rgba(0,0,0,.125);border-radius:.25rem}.card-body{flex:1 1 auto;min-height:1px;padding:1.25rem}.card-footer,.card-header{padding:.75rem 1.25rem;background-color:#fff}.card-header{margin-bottom:0;border-bottom:1px solid rgba(0,0,0,.125)}.card-header:first-child{border-radius:calc(.25rem - 1px) calc(.25rem - 1px) 0 0}.card-footer{border-top:1px solid rgba(0,0,0,.125)}.card-footer:last-child{border-radius:0 0 calc(.25rem - 1px) calc(.25rem - 1px)}.bg-white{background-color:#fff!important}.border{border:1px solid #dee2e6!important}.d-inline-flex{display:inline-flex!important}.justify-content-between{justify-content:space-between!important}.align-items-center{align-items:center!important}.my-0{margin-top:0!important}.mb-0,.my-0{margin-bottom:0!important}.mb-2{margin-bottom:.5rem!important}.pr-1{padding-right:.25rem!important}.pl-2{padding-left:.5rem!important}.text-uppercase{text-transform:uppercase!important}.font-weight-bold{font-weight:700!important}a.text-dark:focus,a.text-dark:hover{color:#000!important}a.text-muted:focus,a.text-muted:hover{color:#454b50!important}.text-muted{color:#6c757d!important}@media print{*,::after,::before{text-shadow:none!important;box-shadow:none!important}a:not(.btn){text-decoration:underline}img{page-break-inside:avoid}p{orphans:3;widows:3}body{min-width:992px!important}}body{margin:0;font-size:.9rem;line-height:1.6;color:#212529;text-align:left;background-color:rgba(247,251,253,.4705882353);min-height:100vh;flex-flow:column;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}.text-dark{color:#212529!important}@media (max-width:576px){.card-md-rounded-0{border-width:1px 0;border-radius:0!important}}.card{box-shadow:0 2px 6px 0 hsla(0,0%,0%,.2);border:none}.status-card-embed{box-shadow:none;border-radius:4px;overflow:hidden}body.embed-card{background:#fff!important;margin:0;padding-bottom:0}.avatar{border-radius:100%}image-carousel{display:block;position:relative;width:100%;overflow:hidden}image-carousel img{width:100%;display:none}image-carousel img.active{display:block}.carousel-buttons{position:absolute;top:50%;width:100%;display:flex;justify-content:space-between;transform:translateY(-50%);pointer-events:none}.carousel-button{display:flex;justify-content:center;align-items:center;width:40px;height:40px;background:#fff;color:#000;border:1px solid #ccc;border-radius:100%;padding:10px;margin:5px;cursor:none;pointer-events:none;opacity:0;z-index:2;transition:opacity .1s ease-out}.carousel-button.active{cursor:pointer;pointer-events:all}image-carousel:hover .carousel-button.active{opacity:1;transition:opacity .3s ease-out}.carousel-button.prev{align-self:flex-start}.carousel-button.next{align-self:flex-end}.carousel-indicators{position:absolute;bottom:10px;width:100%;display:flex;justify-content:center}.carousel-indicator{background:rgba(0,0,0,.5);border-radius:50%;width:10px;height:10px;margin:0 5px;cursor:pointer;transition:background .1s ease-out}.carousel-indicator.active{background:#fff}
    </style>
</head>
<body class="bg-white">
    <div class="embed-card">
        <div class="card status-card-embed card-md-rounded-0 border">
            <div class="card-header d-inline-flex align-items-center bg-white">
                <img src="{{$status['account']['avatar']}}" width="32" height="32" class="avatar" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
                <a class="username font-weight-bold pl-2 text-dark" target="_blank" rel="ugc" href="{{$status['account']['url']}}">
                    {{$status['account']['username']}}
                </a>
            </div>
            @if($status['pf_type'] === 'photo')
            <a href="{{$status['url']}}" target="_blank" rel="ugc">
                <div>
                    <img src="{{$status['media_attachments'][0]['preview_url'] ?? $status['media_attachments'][0]['url']}}" width="100%">
                </div>
            </a>
            @elseif($status['pf_type'] === 'photo:album')
            <image-carousel>
                @foreach($status['media_attachments'] as $media)
                    <img src="{{$media['url']}}">
                @endforeach
            </image-carousel>
            @endif
            @if($layout != 'compact')
            <div class="card-body">
                <div class="view-more mb-2">
                    <a class="font-weight-bold" href="{{$status['url']}}" target="_blank">View More on Pixelfed</a>
                </div>
                <hr>
                <div class="caption">
                    <p class="my-0">
                        <span class="username font-weight-bold">
                            <bdi><a class="text-dark" href="{{$status['account']['url']}}" target="_blank">{{$status['account']['username']}}</a></bdi>
                        </span>
                        @if($showCaption)
                        <span class="caption-container">{{ $status['content_text'] }}</span>
                        @endif
                    </p>
                </div>
            </div>
            @endif
            <div class="card-footer bg-white d-inline-flex justify-content-between align-items-center">
                <div class="timestamp">
                    <p class="small text-uppercase mb-0">
                        <a href="{{$status['url']}}" class="text-muted" target="_blank" rel="ugc">
                            {{now()->parse($status['created_at'])->diffForHumans()}}
                        </a>
                    </p>
                </div>
                <div>
                    <a class="small font-weight-bold text-muted pr-1" href="{{config('app.url')}}" target="_blank">{{config('pixelfed.domain.app')}}</a>
                    <img src="/img/pixelfed-icon-color.svg" width="26" height="26" />
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener("message",e=>{const t=e.data||{};window.parent&&"setHeight"===t.type&&window.parent.postMessage({type:"setHeight",id:t.id,height:document.getElementsByTagName("html")[0].scrollHeight},"*")});
        document.querySelectorAll('.caption-container a').forEach(function(i) {i.setAttribute('target', '_blank');});
        document.addEventListener("DOMContentLoaded",()=>{class t extends HTMLElement{constructor(){super(),this.currentIndex=0,this.images=null,this.totalImages=0,this.indicators=[]}connectedCallback(){if(this.images=this.querySelectorAll("img"),this.totalImages=this.images.length,0===this.totalImages){console.error("No images found in the carousel");return}this.createButtons(),this.createIndicators(),this.showImage(this.currentIndex)}createButtons(){this.nextButton=document.createElement("button"),this.nextButton.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" height="12" width="6" viewBox="0 0 256 512"><path d="M24.7 38.1L4.9 57.9c-4.7 4.7-4.7 12.3 0 17L185.6 256 4.9 437.1c-4.7 4.7-4.7 12.3 0 17L24.7 473.9c4.7 4.7 12.3 4.7 17 0l209.4-209.4c4.7-4.7 4.7-12.3 0-17L41.7 38.1c-4.7-4.7-12.3-4.7-17 0z"/></svg>',this.nextButton.className="carousel-button next",this.nextButton.addEventListener("click",()=>this.nextImage()),this.prevButton=document.createElement("button"),this.prevButton.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" height="12" width="6" viewBox="0 0 256 512"><path d="M231.3 473.9l19.8-19.8c4.7-4.7 4.7-12.3 0-17L70.4 256 251.1 74.9c4.7-4.7 4.7-12.3 0-17L231.3 38.1c-4.7-4.7-12.3-4.7-17 0L4.9 247.5c-4.7 4.7-4.7 12.3 0 17L214.3 473.9c4.7 4.7 12.3 4.7 17 0z"/></svg>',this.prevButton.className="carousel-button prev",this.prevButton.addEventListener("click",()=>this.prevImage());let t=document.createElement("div");t.className="carousel-buttons",t.appendChild(this.prevButton),t.appendChild(this.nextButton),this.appendChild(t),this.updateButtonVisibility()}createIndicators(){this.indicatorsContainer=document.createElement("div"),this.indicatorsContainer.className="carousel-indicators",this.indicators=Array.from(this.images).map((t,e)=>{let i=document.createElement("div");return i.className="carousel-indicator",i.addEventListener("click",()=>this.showImage(e)),this.indicatorsContainer.appendChild(i),i}),this.appendChild(this.indicatorsContainer),this.updateIndicators()}showImage(t){if(!this.images||!this.indicators){console.error("Images or indicators are not initialized");return}this.images.forEach((e,i)=>{e.classList.toggle("active",i===t)}),this.indicators.forEach((e,i)=>{e.classList.toggle("active",i===t)}),this.currentIndex=t,this.updateButtonVisibility()}nextImage(){this.currentIndex<this.totalImages-1&&this.showImage(this.currentIndex+1)}prevImage(){this.currentIndex>0&&this.showImage(this.currentIndex-1)}updateButtonVisibility(){this.prevButton.classList.toggle("active",0!==this.currentIndex),this.nextButton.classList.toggle("active",this.currentIndex!==this.totalImages-1)}updateIndicators(){if(!this.indicators){console.error("Indicators are not initialized");return}this.indicators.forEach((t,e)=>{t.classList.toggle("active",e===this.currentIndex)})}}customElements.define("image-carousel",t)});
    </script>
</body>
</html>
