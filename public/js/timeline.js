/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 3);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/assets/js/timeline.js":
/***/ (function(module, exports) {

$(document).ready(function () {
  $('.pagination').hide();
  $('.container.timeline-container').removeClass('d-none');
  var elem = document.querySelector('.timeline-feed');
  var type = elem.getAttribute('data-timeline');
  $('.timeline-sidenav .nav-link[data-type="' + type + '"]').addClass('active');
  pixelfed.readmore();
  pixelfed.fetchLikes();
  $('video').on('play', function () {
    activated = this;
    $('video').each(function () {
      if (this != activated) this.pause();
    });
  });
  var infScroll = new InfiniteScroll(elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    status: '.page-load-status',
    history: false
  });

  infScroll.on('append', function (response, path, items) {
    pixelfed.hydrateLikes();
    $('.status-card > .card-footer').each(function () {
      var el = $(this);
      if (!el.hasClass('d-none') && !el.find('input[name="comment"]').val()) {
        $(this).addClass('d-none');
      }
    });
    $('video').on('play', function () {
      activated = this;
      $('video').each(function () {
        if (this != activated) this.pause();
      });
    });
  });
});

$(document).on("DOMContentLoaded", function () {

  var active = false;
  var lazyLoad = function lazyLoad() {
    pixelfed.readmore();
    if (active === false) {
      active = true;

      var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
      lazyImages.forEach(function (lazyImage) {
        if (lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0 && getComputedStyle(lazyImage).display !== "none") {
          lazyImage.src = lazyImage.dataset.src;
          lazyImage.srcset = lazyImage.dataset.srcset;
          lazyImage.classList.remove("lazy");

          lazyImages = lazyImages.filter(function (image) {
            return image !== lazyImage;
          });
        }
      });

      active = false;
    };
  };
  document.addEventListener("scroll", lazyLoad);
  window.addEventListener("resize", lazyLoad);
  window.addEventListener("orientationchange", lazyLoad);
});

/***/ }),

/***/ 3:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("./resources/assets/js/timeline.js");


/***/ })

/******/ });