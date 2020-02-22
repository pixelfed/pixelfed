window.pixlfed = {};
window.pixlfed.config = {
    domain: process.env.MIX_APP_DOMAIN,
}

pixlfed.autoSizeIFrame = function (el) {
    console.log(el.contentDocument);
    el.style.height = el.contentDocument.body.scrollHeight +'px';
}

pixlfed.polyfill = function () {
    [].forEach.call(document.querySelectorAll('div.pixelfed-embed'), function (el) {
        pixlfed.loadIFrame(el);
    });
}

pixlfed.loadIFrame = function (el) {
    let permalink = el.getAttribute('data-pixlfed-permalink');
    let parser = document.createElement('a');
    parser.href = permalink;
    if (el.getAttribute('loaded') == 'true') {
        return;
    }
    if (pixlfed.config.domain !== parser.host) {
        el.setAttribute('loaded', 'true');
        console.error('Invalid embed permalink')
        return;
    }
    let css = 'background: white; max-width: 540px; width: calc(100% - 2px); border-radius: 3px; border: 1px solid rgb(219, 219, 219); box-shadow: none; display: block; margin: 0px 0px 12px; min-width: 326px; padding: 0px;';
    let iframe = document.createElement('iframe');
    iframe.onload = function () {
        pixlfed.autoSizeIFrame(iframe);
    }
    iframe.setAttribute('allowtransparency', 'true');
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('scrolling', 'no');
    iframe.setAttribute('src', permalink);
    iframe.setAttribute('style', css);
    iframe.setAttribute('loaded', 'true');
    el.replaceWith(iframe);
}

pixlfed.run = function () {
    var lazyFrames = [].slice.call(document.querySelectorAll("div.pixelfed-embed"));

    if ("IntersectionObserver" in window) {
        let lazyFrameObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    if (entry.target.getAttribute('loaded') !== 'true') {
                        pixlfed.loadIFrame(entry.target);
                    }
                }
            });
        });

        lazyFrames.forEach(function (lazyFrame) {
            lazyFrameObserver.observe(lazyFrame);
        });
    } else {
        pixlfed.polyfill();
    }
}

pixlfed.run();