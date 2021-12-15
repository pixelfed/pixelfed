(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["/js/stories"],{

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  props: {
    pid: {
      type: String
    },
    selfProfile: {
      type: Object
    },
    redirectUrl: {
      type: String,
      "default": '/'
    }
  },
  data: function data() {
    return {
      loading: true,
      profile: null,
      account: {
        local: false
      },
      owner: false,
      stories: [],
      username: 'loading...',
      avatar: '/storage/avatars/default.jpg',
      storyIndex: 0,
      progress: 0,
      constInterval: 383,
      progressInterval: undefined,
      composeText: null,
      paused: false,
      muted: true,
      reactionEmoji: ["❤️", "🔥", "💯", "😂", "😎", "👀"],
      activeReactionEmoji: false,
      activeReply: false,
      showProgress: false,
      redirectOnEnd: '/',
      viewerSid: false,
      viewerPage: 1,
      loadingViewers: false,
      viewersHasMore: true,
      viewers: [],
      viewWarning: false,
      showingPollResults: false,
      loadingPollResults: false,
      pollResults: [],
      pollTotalVotes: 0
    };
  },
  watch: {
    composeText: function composeText(val) {
      if (val.length == 0) {
        if (this.paused) {
          this.pause();
        }
      } else {
        if (!this.paused) {
          this.pause();
        }
      }

      event.currentTarget.focus();
    }
  },
  beforeMount: function beforeMount() {
    this.redirectOnEnd = this.redirectUrl;
  },
  mounted: function mounted() {
    var _this = this;

    var u = new URLSearchParams(window.location.search);

    if (u.has('t')) {
      switch (u.get('t')) {
        case '1':
          this.redirectOnEnd = '/';
          break;

        case '2':
          this.redirectOnEnd = '/timeline/public';
          break;

        case '3':
          this.redirectOnEnd = '/timeline/network';
          break;

        case '4':
          this.redirectOnEnd = '/' + window.location.pathname.split('/').slice(-1).pop();
          break;
      }
    } else {
      this.viewWarning = true;
    }

    if (!this.selfProfile || !this.selfProfile.hasOwnProperty('avatar')) {
      axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(function (res) {
        _this.profile = res.data;

        _this.fetchStories();
      });
    } else {
      this.profile = this.selfProfile;
    }

    var el = document.querySelector('body');
    el.style.width = '100%';
    el.style.height = '100vh !important';
    el.style.overflow = 'hidden';
    el.style.backgroundColor = '#262626';
  },
  methods: {
    init: function init() {
      var _this2 = this;

      clearInterval(this.progressInterval);
      this.loading = false;
      this.constInterval = Math.ceil(this.stories[this.storyIndex].duration * 38.3);
      this.progressInterval = setInterval(function () {
        _this2["do"]();
      }, this.constInterval);
    },
    "do": function _do() {
      this.loading = false;

      if (this.stories[this.storyIndex].progress != 100) {
        this.stories[this.storyIndex].progress = this.stories[this.storyIndex].progress + 4;
      } else {
        clearInterval(this.progressInterval);
        this.next();
      }
    },
    prev: function prev() {
      if (this.storyIndex == 0) {
        return;
      }

      this.pollResults = [];
      this.progress = 0;
      this.gotoSlide(this.storyIndex - 1);
    },
    next: function next() {
      axios.post('/api/web/stories/v1/viewed', {
        id: this.stories[this.storyIndex].id
      });
      this.stories[this.storyIndex].progress = 100;

      if (this.storyIndex == this.stories.length - 1) {
        if (this.composeText && this.composeText.length) {
          return;
        }

        window.location.href = this.redirectOnEnd;
        return;
      }

      this.pollResults = [];
      this.progress = 0;
      this.muted = true;
      this.storyIndex = this.storyIndex + 1;
      this.init();
    },
    pause: function pause() {
      if (event) {
        event.currentTarget.blur();
      }

      if (this.paused) {
        this.paused = false;

        if (this.stories[this.storyIndex].type == 'video') {
          var el = document.getElementById('playr');
          el.play();
        }

        this.init();
      } else {
        clearInterval(this.progressInterval);

        if (this.stories[this.storyIndex].type == 'video') {
          var _el = document.getElementById('playr');

          _el.pause();
        }

        this.paused = true;
      }
    },
    toggleMute: function toggleMute() {
      if (event) {
        event.currentTarget.blur();
      }

      if (this.stories[this.storyIndex].type == 'video') {
        this.muted = !this.muted;
        var el = document.getElementById('playr');
        el.muted = this.muted;
      }
    },
    gotoSlide: function gotoSlide(index) {
      this.paused = false;
      clearInterval(this.progressInterval);
      this.progressInterval = null;
      this.stories = this.stories.map(function (s, k) {
        if (k < index) {
          s.progress = 100;
        } else {
          s.progress = 0;
        }

        return s;
      });
      this.storyIndex = index;
      this.stories[index].progress = 0;
      this.init();
    },
    showMenu: function showMenu() {
      if (!this.paused) {
        this.pause();
      }

      event.currentTarget.blur();
      this.$refs.ctxMenu.show();
    },
    react: function react(emoji) {
      var _this3 = this;

      this.$refs.ctxMenu.hide();
      this.activeReactionEmoji = true;
      axios.post('/api/web/stories/v1/react', {
        sid: this.stories[this.storyIndex].id,
        reaction: emoji
      }).then(function (res) {
        setTimeout(function () {
          _this3.activeReactionEmoji = false;

          _this3.pause();
        }, 2000);
      })["catch"](function (err) {
        _this3.activeReactionEmoji = false;
        swal('Error', 'An error occured when attempting to react to this story. Please try again later.', 'error');
      });
    },
    comment: function comment() {
      var _this4 = this;

      if (this.composeText.length < 2) {
        return;
      }

      if (!this.paused) {
        this.pause();
      }

      this.activeReply = true;
      axios.post('/api/web/stories/v1/comment', {
        sid: this.stories[this.storyIndex].id,
        caption: this.composeText
      }).then(function (res) {
        _this4.composeText = null;
        setTimeout(function () {
          _this4.activeReply = false;

          _this4.pause();
        }, 2000);
      })["catch"](function (err) {
        _this4.activeReply = false;
        swal('Error', 'An error occured when attempting to reply to this story. Please try again later.', 'error');
      });
    },
    closeCtxMenu: function closeCtxMenu() {
      this.$refs.ctxMenu.hide();
    },
    backToFeed: function backToFeed() {
      var _this5 = this;

      if (this.composeText) {
        swal('Are you sure you want to leave without sending this reply?').then(function (confirm) {
          if (confirm) {
            window.location.href = _this5.redirectOnEnd;
          }
        });
        return;
      } else {
        window.location.href = this.redirectOnEnd;
      }
    },
    timeago: function timeago(ts) {
      return App.util.format.timeAgo(ts);
    },
    timeahead: function timeahead(ts) {
      var d = new Date(ts);
      return App.util.format.timeAhead(d.toUTCString());
    },
    fetchStories: function fetchStories() {
      var _this6 = this;

      var self = this;
      axios.get('/api/web/stories/v1/profile/' + this.pid).then(function (res) {
        if (res.data.length == 0) {
          window.location.href = _this6.redirectOnEnd;
        }

        self.account = res.data[0].account;

        if (self.account.local == false) {
          self.account.domain = self.account.acct.split('@')[1];
        }

        self.stories = res.data[0].nodes.map(function (i, k) {
          var r = {
            id: i.id,
            created_at: i.created_at,
            expires_at: i.expires_at,
            progress: i.progress,
            view_count: i.view_count,
            url: i.src,
            type: i.type,
            duration: i.duration,
            can_reply: i.can_reply,
            can_react: i.can_react
          };

          if (r.type == 'poll') {
            r.question = i.question;
            r.options = i.options;
            r.voted = i.voted;
            r.voted_index = i.voted_index;
          }

          return r;
        });
        self.username = res.data[0].account.username;
        self.avatar = res.data[0].account.avatar;

        if (self.profile.id == res.data[0].account.id) {
          _this6.viewWarning = false;
        }

        if (_this6.viewWarning) {
          _this6.loading = false;
          return;
        }

        var seen = res.data[0].nodes.filter(function (i, k) {
          return i.seen == true;
        }).map(function (i, k) {
          return k;
        });

        if (seen.length && _this6.pid != _this6.profile.id) {
          var n = seen[seen.length - 1] + 1 == self.stories.length ? seen[seen.length - 1] : seen[seen.length - 1] + 1;
          self.gotoSlide(n);
        }

        if (_this6.pid == _this6.profile.id) {
          self.gotoSlide(self.stories.length - 1);
        }

        self.showProgress = true;

        if (self.profile.id == self.account.id) {
          self.owner = true;
        }

        if (res.data.length == 0) {
          window.location.href = _this6.redirectOnEnd;
          return;
        }

        _this6.init();
      })["catch"](function (err) {
        return;
      });
    },
    fetchViewers: function fetchViewers() {
      var _this7 = this;

      this.closeCtxMenu();
      this.$refs.viewersModal.show();

      if (this.stories[this.storyIndex].id == this.viewerSid) {
        return;
      }

      this.loadingViewers = true;
      axios.get('/api/web/stories/v1/viewers', {
        params: {
          sid: this.stories[this.storyIndex].id
        }
      }).then(function (res) {
        _this7.viewerSid = _this7.stories[_this7.storyIndex].id;
        _this7.viewers = res.data;
        _this7.loadingViewers = false;
        _this7.viewerPage = 2;

        if (_this7.viewers.length == 10) {
          _this7.viewersHasMore = true;
        } else {
          _this7.viewersHasMore = false;
        }
      })["catch"](function (err) {
        swal('Cannot load viewers', 'Cannot load viewers of this story, please try again later.', 'error');
      });
    },
    viewersLoadMore: function viewersLoadMore() {
      var _this8 = this;

      axios.get('/api/web/stories/v1/viewers', {
        params: {
          sid: this.stories[this.storyIndex].id,
          page: this.viewerPage
        }
      }).then(function (res) {
        var _this8$viewers;

        if (!res.data || res.data.length == 0) {
          _this8.viewersHasMore = false;
          return;
        }

        if (res.data.length != 10) {
          _this8.viewersHasMore = false;
        }

        (_this8$viewers = _this8.viewers).push.apply(_this8$viewers, _toConsumableArray(res.data));

        _this8.viewerPage++;
      })["catch"](function (err) {
        swal('Cannot load viewers', 'Cannot load viewers of this story, please try again later.', 'error');
      });
    },
    closeViewersModal: function closeViewersModal() {
      this.$refs.viewersModal.hide();
    },
    deleteStory: function deleteStory() {
      var _this9 = this;

      this.closeCtxMenu();

      if (!window.confirm('Are you sure you want to delete this story?')) {
        this.pause();
        return;
      }

      axios["delete"]('/api/web/stories/v1/delete/' + this.stories[this.storyIndex].id).then(function (res) {
        var i = _this9.storyIndex;
        var c = _this9.stories.length;

        if (c == 1) {
          window.location.href = '/';
          return;
        }

        window.location.reload();
      });
    },
    selectPollOption: function selectPollOption(index) {
      var _this10 = this;

      if (!this.paused) {
        this.pause();
      }

      axios.post('/i/stories/viewed', {
        id: this.stories[this.storyIndex].id
      });
      axios.post('/api/web/stories/v1/poll/vote', {
        sid: this.stories[this.storyIndex].id,
        ci: index
      }).then(function (res) {
        _this10.stories[_this10.storyIndex].voted = true;
        _this10.stories[_this10.storyIndex].voted_index = index;

        _this10.next();
      });
    },
    ctxMenuReport: function ctxMenuReport() {
      this.$refs.ctxMenu.hide();
      this.$refs.ctxReport.show();
    },
    openCtxReportOtherMenu: function openCtxReportOtherMenu() {
      this.closeCtxMenu();
      this.$refs.ctxReport.hide();
      this.$refs.ctxReportOther.show();
    },
    ctxReportMenuGoBack: function ctxReportMenuGoBack() {
      this.closeMenus();
    },
    ctxReportOtherMenuGoBack: function ctxReportOtherMenuGoBack() {
      this.closeMenus();
    },
    closeMenus: function closeMenus() {
      this.$refs.ctxReportOther.hide();
      this.$refs.ctxReport.hide();
      this.$refs.ctxMenu.hide();
    },
    sendReport: function sendReport(type) {
      var _this11 = this;

      var id = this.stories[this.storyIndex].id;
      swal({
        'title': 'Confirm Report',
        'text': 'Are you sure you want to report this post?',
        'icon': 'warning',
        'buttons': true,
        'dangerMode': true
      }).then(function (res) {
        if (res) {
          axios.post('/api/web/stories/v1/report', {
            'type': type,
            'id': id
          }).then(function (res) {
            _this11.closeMenus();

            swal('Report Sent!', 'We have successfully received your report', 'success');
          })["catch"](function (err) {
            if (err.response.status === 409) {
              swal('Already reported', 'You have already reported this story', 'info');
            } else {
              swal('Oops!', 'There was an issue reporting this story', 'error');
            }
          });
        } else {
          _this11.closeMenus();
        }
      });
    },
    confirmViewStory: function confirmViewStory() {
      var self = this;
      var seen = this.stories.filter(function (i, k) {
        return i.seen == true;
      }).map(function (i, k) {
        return k;
      });

      if (seen.length && this.pid != this.profile.id) {
        var n = seen[seen.length - 1] + 1 == self.stories.length ? seen[seen.length - 1] : seen[seen.length - 1] + 1;
        self.gotoSlide(n);
      }

      if (this.pid == this.profile.id) {
        self.gotoSlide(self.stories.length - 1);
      }

      self.showProgress = true;

      if (self.profile.username == self.username) {
        self.owner = true;
      }

      this.viewWarning = false;
      this.init();
    },
    showPollResults: function showPollResults() {
      var _this12 = this;

      this.loadingPollResults = true;

      if (!this.paused) {
        this.pause();
      }

      axios.get('/api/web/stories/v1/poll/results', {
        params: {
          sid: this.stories[this.storyIndex].id
        }
      }).then(function (res) {
        _this12.loadingPollResults = false;
        _this12.pollResults = res.data;

        var sum = function sum(a, b) {
          return a + b;
        };

        _this12.pollTotalVotes = _this12.pollResults.reduce(sum);
      });
    },
    addToStory: function addToStory() {
      window.location.href = '/i/stories/new';
    },
    pollPercent: function pollPercent(index) {
      return this.pollTotalVotes == 0 ? 0 : Math.round(this.pollResults[index] / this.pollTotalVotes * 100);
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/index.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--9-2!./node_modules/sass-loader/dist/cjs.js??ref--9-3!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "#content[data-v-be0d9900] {\n  width: 100%;\n  height: 100vh !important;\n  overflow: hidden;\n  background-color: #262626;\n}\n.story-viewer-component-card[data-v-be0d9900] {\n  height: 100vh;\n}\n@media (min-width: 768px) {\n.story-viewer-component-card[data-v-be0d9900] {\n    height: 90vh;\n}\n}\n.story-viewer-component.bg-black[data-v-be0d9900] {\n  background-color: #262626;\n}\n.story-viewer-component .option-green[data-v-be0d9900] {\n  font-size: 20px;\n  font-weight: 600;\n  background: #11998e;\n  /* fallback for old browsers */\n  background: linear-gradient(180deg, #38ef7d, #11998e);\n  -webkit-background-clip: text;\n  -webkit-text-fill-color: transparent;\n}\n.story-viewer-component .option-red[data-v-be0d9900] {\n  font-weight: 600;\n  background: linear-gradient(to right, #F27121, #E94057, #8A2387);\n  -webkit-background-clip: text;\n  -webkit-text-fill-color: transparent;\n}\n.story-viewer-component .bg-black[data-v-be0d9900] {\n  background-color: #262626;\n}\n.story-viewer-component .fade-enter-active[data-v-be0d9900], .story-viewer-component .fade-leave-active[data-v-be0d9900] {\n  transition: opacity 0.5s;\n}\n.story-viewer-component .fade-enter[data-v-be0d9900], .story-viewer-component .fade-leave-to[data-v-be0d9900] {\n  opacity: 0;\n}\n.story-viewer-component .progress[data-v-be0d9900] {\n  background-color: #979a9a;\n}\n.story-viewer-component .media-slot[data-v-be0d9900] {\n  border-radius: 0;\n  width: 100%;\n  height: 100%;\n  position: absolute;\n  left: 0;\n  top: 0;\n  background: #000;\n  background-size: cover !important;\n  z-index: 0;\n}\n.story-viewer-component .card-body .top-overlay[data-v-be0d9900] {\n  height: 100px;\n  margin-left: -35px;\n  margin-right: -35px;\n  margin-top: -20px;\n  padding-bottom: 20px;\n  border-radius: 5px;\n  background: linear-gradient(180deg, rgba(38, 38, 38, 0.8) 0%, rgba(38, 38, 38, 0) 100%);\n}\n.story-viewer-component .card-footer[data-v-be0d9900] ::-moz-placeholder {\n  color: #fff;\n  opacity: 1;\n}\n.story-viewer-component .card-footer[data-v-be0d9900] :-ms-input-placeholder {\n  color: #fff;\n  opacity: 1;\n}\n.story-viewer-component .card-footer[data-v-be0d9900] ::placeholder {\n  color: #fff;\n  opacity: 1;\n}\n.story-viewer-component .card-footer .bottom-overlay[data-v-be0d9900] {\n  margin-left: -35px;\n  margin-right: -35px;\n  margin-bottom: -20px;\n  border-radius: 5px;\n  background: linear-gradient(0deg, rgba(38, 38, 38, 0.8) 0%, rgba(38, 38, 38, 0) 100%);\n}\n.story-viewer-component .card-footer .bottom-overlay .form-group[data-v-be0d9900] {\n  padding-top: 40px;\n  padding-bottom: 20px;\n  margin-bottom: 0;\n}", ""]);

// exports


/***/ }),

/***/ "./node_modules/css-loader/lib/css-base.js":
/*!*************************************************!*\
  !*** ./node_modules/css-loader/lib/css-base.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function(useSourceMap) {
	var list = [];

	// return the list of modules as css string
	list.toString = function toString() {
		return this.map(function (item) {
			var content = cssWithMappingToString(item, useSourceMap);
			if(item[2]) {
				return "@media " + item[2] + "{" + content + "}";
			} else {
				return content;
			}
		}).join("");
	};

	// import a list of modules into the list
	list.i = function(modules, mediaQuery) {
		if(typeof modules === "string")
			modules = [[null, modules, ""]];
		var alreadyImportedModules = {};
		for(var i = 0; i < this.length; i++) {
			var id = this[i][0];
			if(typeof id === "number")
				alreadyImportedModules[id] = true;
		}
		for(i = 0; i < modules.length; i++) {
			var item = modules[i];
			// skip already imported module
			// this implementation is not 100% perfect for weird media query combinations
			//  when a module is imported multiple times with different media queries.
			//  I hope this will never occur (Hey this way we have smaller bundles)
			if(typeof item[0] !== "number" || !alreadyImportedModules[item[0]]) {
				if(mediaQuery && !item[2]) {
					item[2] = mediaQuery;
				} else if(mediaQuery) {
					item[2] = "(" + item[2] + ") and (" + mediaQuery + ")";
				}
				list.push(item);
			}
		}
	};
	return list;
};

function cssWithMappingToString(item, useSourceMap) {
	var content = item[1] || '';
	var cssMapping = item[3];
	if (!cssMapping) {
		return content;
	}

	if (useSourceMap && typeof btoa === 'function') {
		var sourceMapping = toComment(cssMapping);
		var sourceURLs = cssMapping.sources.map(function (source) {
			return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */'
		});

		return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
	}

	return [content].join('\n');
}

// Adapted from convert-source-map (MIT)
function toComment(sourceMap) {
	// eslint-disable-next-line no-undef
	var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
	var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;

	return '/*# ' + data + ' */';
}


/***/ }),

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--9-2!./node_modules/sass-loader/dist/cjs.js??ref--9-3!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../node_modules/css-loader!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--9-2!../../../../node_modules/sass-loader/dist/cjs.js??ref--9-3!../../../../node_modules/vue-loader/lib??vue-loader-options!./StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& */ "./node_modules/css-loader/index.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./node_modules/style-loader/lib/addStyles.js":
/*!****************************************************!*\
  !*** ./node_modules/style-loader/lib/addStyles.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/

var stylesInDom = {};

var	memoize = function (fn) {
	var memo;

	return function () {
		if (typeof memo === "undefined") memo = fn.apply(this, arguments);
		return memo;
	};
};

var isOldIE = memoize(function () {
	// Test for IE <= 9 as proposed by Browserhacks
	// @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
	// Tests for existence of standard globals is to allow style-loader
	// to operate correctly into non-standard environments
	// @see https://github.com/webpack-contrib/style-loader/issues/177
	return window && document && document.all && !window.atob;
});

var getTarget = function (target, parent) {
  if (parent){
    return parent.querySelector(target);
  }
  return document.querySelector(target);
};

var getElement = (function (fn) {
	var memo = {};

	return function(target, parent) {
                // If passing function in options, then use it for resolve "head" element.
                // Useful for Shadow Root style i.e
                // {
                //   insertInto: function () { return document.querySelector("#foo").shadowRoot }
                // }
                if (typeof target === 'function') {
                        return target();
                }
                if (typeof memo[target] === "undefined") {
			var styleTarget = getTarget.call(this, target, parent);
			// Special case to return head of iframe instead of iframe itself
			if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
				try {
					// This will throw an exception if access to iframe is blocked
					// due to cross-origin restrictions
					styleTarget = styleTarget.contentDocument.head;
				} catch(e) {
					styleTarget = null;
				}
			}
			memo[target] = styleTarget;
		}
		return memo[target]
	};
})();

var singleton = null;
var	singletonCounter = 0;
var	stylesInsertedAtTop = [];

var	fixUrls = __webpack_require__(/*! ./urls */ "./node_modules/style-loader/lib/urls.js");

module.exports = function(list, options) {
	if (typeof DEBUG !== "undefined" && DEBUG) {
		if (typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};

	options.attrs = typeof options.attrs === "object" ? options.attrs : {};

	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (!options.singleton && typeof options.singleton !== "boolean") options.singleton = isOldIE();

	// By default, add <style> tags to the <head> element
        if (!options.insertInto) options.insertInto = "head";

	// By default, add <style> tags to the bottom of the target
	if (!options.insertAt) options.insertAt = "bottom";

	var styles = listToStyles(list, options);

	addStylesToDom(styles, options);

	return function update (newList) {
		var mayRemove = [];

		for (var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];

			domStyle.refs--;
			mayRemove.push(domStyle);
		}

		if(newList) {
			var newStyles = listToStyles(newList, options);
			addStylesToDom(newStyles, options);
		}

		for (var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];

			if(domStyle.refs === 0) {
				for (var j = 0; j < domStyle.parts.length; j++) domStyle.parts[j]();

				delete stylesInDom[domStyle.id];
			}
		}
	};
};

function addStylesToDom (styles, options) {
	for (var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];

		if(domStyle) {
			domStyle.refs++;

			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}

			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];

			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}

			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles (list, options) {
	var styles = [];
	var newStyles = {};

	for (var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = options.base ? item[0] + options.base : item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};

		if(!newStyles[id]) styles.push(newStyles[id] = {id: id, parts: [part]});
		else newStyles[id].parts.push(part);
	}

	return styles;
}

function insertStyleElement (options, style) {
	var target = getElement(options.insertInto)

	if (!target) {
		throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
	}

	var lastStyleElementInsertedAtTop = stylesInsertedAtTop[stylesInsertedAtTop.length - 1];

	if (options.insertAt === "top") {
		if (!lastStyleElementInsertedAtTop) {
			target.insertBefore(style, target.firstChild);
		} else if (lastStyleElementInsertedAtTop.nextSibling) {
			target.insertBefore(style, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			target.appendChild(style);
		}
		stylesInsertedAtTop.push(style);
	} else if (options.insertAt === "bottom") {
		target.appendChild(style);
	} else if (typeof options.insertAt === "object" && options.insertAt.before) {
		var nextSibling = getElement(options.insertAt.before, target);
		target.insertBefore(style, nextSibling);
	} else {
		throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
	}
}

function removeStyleElement (style) {
	if (style.parentNode === null) return false;
	style.parentNode.removeChild(style);

	var idx = stylesInsertedAtTop.indexOf(style);
	if(idx >= 0) {
		stylesInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement (options) {
	var style = document.createElement("style");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}

	if(options.attrs.nonce === undefined) {
		var nonce = getNonce();
		if (nonce) {
			options.attrs.nonce = nonce;
		}
	}

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}
	options.attrs.rel = "stylesheet";

	addAttrs(link, options.attrs);
	insertStyleElement(options, link);

	return link;
}

function addAttrs (el, attrs) {
	Object.keys(attrs).forEach(function (key) {
		el.setAttribute(key, attrs[key]);
	});
}

function getNonce() {
	if (false) {}

	return __webpack_require__.nc;
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = typeof options.transform === 'function'
		 ? options.transform(obj.css) 
		 : options.transform.default(obj.css);

	    if (result) {
	    	// If transform returns a value, use that instead of the original css.
	    	// This allows running runtime transformations on the css.
	    	obj.css = result;
	    } else {
	    	// If the transform function returns a falsy value, don't add this css.
	    	// This allows conditional loading of css
	    	return function() {
	    		// noop
	    	};
	    }
	}

	if (options.singleton) {
		var styleIndex = singletonCounter++;

		style = singleton || (singleton = createStyleElement(options));

		update = applyToSingletonTag.bind(null, style, styleIndex, false);
		remove = applyToSingletonTag.bind(null, style, styleIndex, true);

	} else if (
		obj.sourceMap &&
		typeof URL === "function" &&
		typeof URL.createObjectURL === "function" &&
		typeof URL.revokeObjectURL === "function" &&
		typeof Blob === "function" &&
		typeof btoa === "function"
	) {
		style = createLinkElement(options);
		update = updateLink.bind(null, style, options);
		remove = function () {
			removeStyleElement(style);

			if(style.href) URL.revokeObjectURL(style.href);
		};
	} else {
		style = createStyleElement(options);
		update = applyToTag.bind(null, style);
		remove = function () {
			removeStyleElement(style);
		};
	}

	update(obj);

	return function updateStyle (newObj) {
		if (newObj) {
			if (
				newObj.css === obj.css &&
				newObj.media === obj.media &&
				newObj.sourceMap === obj.sourceMap
			) {
				return;
			}

			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;

		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag (style, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (style.styleSheet) {
		style.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = style.childNodes;

		if (childNodes[index]) style.removeChild(childNodes[index]);

		if (childNodes.length) {
			style.insertBefore(cssNode, childNodes[index]);
		} else {
			style.appendChild(cssNode);
		}
	}
}

function applyToTag (style, obj) {
	var css = obj.css;
	var media = obj.media;

	if(media) {
		style.setAttribute("media", media)
	}

	if(style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		while(style.firstChild) {
			style.removeChild(style.firstChild);
		}

		style.appendChild(document.createTextNode(css));
	}
}

function updateLink (link, options, obj) {
	var css = obj.css;
	var sourceMap = obj.sourceMap;

	/*
		If convertToAbsoluteUrls isn't defined, but sourcemaps are enabled
		and there is no publicPath defined then lets turn convertToAbsoluteUrls
		on by default.  Otherwise default to the convertToAbsoluteUrls option
		directly
	*/
	var autoFixUrls = options.convertToAbsoluteUrls === undefined && sourceMap;

	if (options.convertToAbsoluteUrls || autoFixUrls) {
		css = fixUrls(css);
	}

	if (sourceMap) {
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	var blob = new Blob([css], { type: "text/css" });

	var oldSrc = link.href;

	link.href = URL.createObjectURL(blob);

	if(oldSrc) URL.revokeObjectURL(oldSrc);
}


/***/ }),

/***/ "./node_modules/style-loader/lib/urls.js":
/*!***********************************************!*\
  !*** ./node_modules/style-loader/lib/urls.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {


/**
 * When source maps are enabled, `style-loader` uses a link element with a data-uri to
 * embed the css on the page. This breaks all relative urls because now they are relative to a
 * bundle instead of the current page.
 *
 * One solution is to only use full urls, but that may be impossible.
 *
 * Instead, this function "fixes" the relative urls to be absolute according to the current page location.
 *
 * A rudimentary test suite is located at `test/fixUrls.js` and can be run via the `npm test` command.
 *
 */

module.exports = function (css) {
  // get current location
  var location = typeof window !== "undefined" && window.location;

  if (!location) {
    throw new Error("fixUrls requires window.location");
  }

	// blank or null?
	if (!css || typeof css !== "string") {
	  return css;
  }

  var baseUrl = location.protocol + "//" + location.host;
  var currentDir = baseUrl + location.pathname.replace(/\/[^\/]*$/, "/");

	// convert each url(...)
	/*
	This regular expression is just a way to recursively match brackets within
	a string.

	 /url\s*\(  = Match on the word "url" with any whitespace after it and then a parens
	   (  = Start a capturing group
	     (?:  = Start a non-capturing group
	         [^)(]  = Match anything that isn't a parentheses
	         |  = OR
	         \(  = Match a start parentheses
	             (?:  = Start another non-capturing groups
	                 [^)(]+  = Match anything that isn't a parentheses
	                 |  = OR
	                 \(  = Match a start parentheses
	                     [^)(]*  = Match anything that isn't a parentheses
	                 \)  = Match a end parentheses
	             )  = End Group
              *\) = Match anything and then a close parens
          )  = Close non-capturing group
          *  = Match anything
       )  = Close capturing group
	 \)  = Match a close parens

	 /gi  = Get all matches, not the first.  Be case insensitive.
	 */
	var fixedCss = css.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(fullMatch, origUrl) {
		// strip quotes (if they exist)
		var unquotedOrigUrl = origUrl
			.trim()
			.replace(/^"(.*)"$/, function(o, $1){ return $1; })
			.replace(/^'(.*)'$/, function(o, $1){ return $1; });

		// already a full url? no change
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(unquotedOrigUrl)) {
		  return fullMatch;
		}

		// convert the url to a full url
		var newUrl;

		if (unquotedOrigUrl.indexOf("//") === 0) {
		  	//TODO: should we add protocol?
			newUrl = unquotedOrigUrl;
		} else if (unquotedOrigUrl.indexOf("/") === 0) {
			// path should be relative to the base url
			newUrl = baseUrl + unquotedOrigUrl; // already starts with '/'
		} else {
			// path should be relative to current directory
			newUrl = currentDir + unquotedOrigUrl.replace(/^\.\//, ""); // Strip leading './'
		}

		// send back the fixed url(...)
		return "url(" + JSON.stringify(newUrl) + ")";
	});

	// send back the fixed css
	return fixedCss;
};


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "story-viewer-component container mt-0 mt-md-5 bg-black" },
    [
      _c(
        "button",
        {
          staticClass: "d-none d-md-block btn btn-link fixed-top",
          staticStyle: { left: "auto", right: "0" },
          attrs: { type: "button" },
          on: { click: _vm.backToFeed },
        },
        [_c("i", { staticClass: "fal fa-times-circle fa-2x text-lighter" })]
      ),
      _vm._v(" "),
      !_vm.viewWarning
        ? _c(
            "div",
            {
              staticClass:
                "row d-flex justify-content-center align-items-center",
            },
            [
              _c(
                "div",
                {
                  staticClass:
                    "d-none d-md-block col-md-1 cursor-pointer text-center",
                  on: { click: _vm.prev },
                },
                [
                  _vm.storyIndex > 0
                    ? _c("div", [
                        _c("i", {
                          staticClass:
                            "fas fa-chevron-circle-left text-muted fa-2x",
                        }),
                      ])
                    : _vm._e(),
                ]
              ),
              _vm._v(" "),
              !_vm.loading
                ? _c(
                    "div",
                    { staticClass: "col-12 col-md-6 rounded-lg" },
                    [
                      _vm.activeReactionEmoji
                        ? _c(
                            "div",
                            {
                              staticClass:
                                "w-100 h-100 d-flex justify-content-center align-items-center",
                              staticStyle: {
                                position: "absolute",
                                "z-index": "999",
                              },
                            },
                            [_vm._m(0)]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _vm.activeReply
                        ? _c(
                            "div",
                            {
                              staticClass:
                                "w-100 h-100 d-flex justify-content-center align-items-center",
                              staticStyle: {
                                position: "absolute",
                                "z-index": "999",
                              },
                            },
                            [_vm._m(1)]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _c("transition", { attrs: { name: "fade" } }, [
                        _vm.stories[_vm.storyIndex].type == "photo"
                          ? _c("div", {
                              key: "msl:" + _vm.storyIndex,
                              staticClass: "media-slot rounded-lg",
                              style: {
                                background:
                                  "url(" +
                                  _vm.stories[_vm.storyIndex].url +
                                  ")",
                              },
                            })
                          : _vm._e(),
                        _vm._v(" "),
                        _vm.stories[_vm.storyIndex].type == "poll"
                          ? _c("div", {
                              key: "msl:" + _vm.storyIndex,
                              staticClass: "media-slot rounded-lg",
                              style: {
                                background:
                                  "linear-gradient(to right, #F27121, #E94057, #8A2387)",
                              },
                            })
                          : _vm._e(),
                        _vm._v(" "),
                        _vm.stories[_vm.storyIndex].type == "video"
                          ? _c(
                              "video",
                              {
                                key: "plyr" + _vm.stories[_vm.storyIndex].id,
                                staticClass: "media-slot rounded-lg",
                                staticStyle: { "object-fit": "contain" },
                                attrs: {
                                  id: "playr",
                                  loop: "",
                                  autoplay: "",
                                  "no-controls": "",
                                },
                                domProps: { muted: _vm.muted },
                              },
                              [
                                _c("source", {
                                  attrs: {
                                    src: _vm.stories[_vm.storyIndex].url,
                                    type: "video/mp4",
                                  },
                                }),
                              ]
                            )
                          : _vm._e(),
                      ]),
                      _vm._v(" "),
                      _c(
                        "div",
                        {
                          staticClass:
                            "story-viewer-component-card card bg-transparent border-0 shadow-none d-flex justify-content-center",
                        },
                        [
                          _c("div", { staticClass: "card-body" }, [
                            _c("div", { staticClass: "px-0 top-overlay" }, [
                              _c(
                                "div",
                                { staticClass: "pt-4 pt-md-3 px-4 d-flex" },
                                [
                                  _c("div", {
                                    staticClass: "d-none bg-muted",
                                    staticStyle: {
                                      width: "100%",
                                      height: "5px",
                                    },
                                  }),
                                  _vm._v(" "),
                                  _vm._l(_vm.stories, function (story, index) {
                                    return _c(
                                      "div",
                                      {
                                        key: "sp:s" + index,
                                        staticClass: "w-100 cursor-pointer",
                                        class: {
                                          "mr-2":
                                            index != _vm.stories.length - 1,
                                        },
                                        on: {
                                          click: function ($event) {
                                            return _vm.gotoSlide(index)
                                          },
                                        },
                                      },
                                      [
                                        _c(
                                          "div",
                                          {
                                            staticClass: "progress w-100",
                                            staticStyle: {
                                              "z-index": "3",
                                              height: "4px",
                                            },
                                            style: {
                                              opacity:
                                                story.progress == 0 ? 0.7 : 0.8,
                                            },
                                          },
                                          [
                                            _c("div", {
                                              key: "sp:si" + index,
                                              staticClass:
                                                "progress-bar bg-light",
                                              style: {
                                                width: story.progress + "%",
                                                transition: "none !important",
                                              },
                                              attrs: {
                                                role: "progressbar",
                                                "aria-valuenow": story.progress,
                                                "aria-valuemin": "0",
                                                "aria-valuemax": "100",
                                              },
                                            }),
                                          ]
                                        ),
                                      ]
                                    )
                                  }),
                                ],
                                2
                              ),
                              _vm._v(" "),
                              _c(
                                "div",
                                {
                                  staticClass:
                                    "pt-4 px-4 media align-items-center",
                                },
                                [
                                  _c("img", {
                                    staticClass: "rounded-circle mr-2",
                                    attrs: {
                                      src: _vm.avatar,
                                      width: "32",
                                      height: "32",
                                      onerror:
                                        "this.onerror=null;this.src='/storage/avatars/default.png?v=2'",
                                    },
                                  }),
                                  _vm._v(" "),
                                  _c(
                                    "div",
                                    {
                                      staticClass:
                                        "media-body d-flex justify-content-between align-items-center",
                                    },
                                    [
                                      _c(
                                        "div",
                                        {
                                          staticClass:
                                            "user-select-none d-flex align-items-center",
                                        },
                                        [
                                          _vm.account.local
                                            ? _c(
                                                "span",
                                                {
                                                  staticClass:
                                                    "text-white font-weight-bold mr-2",
                                                },
                                                [
                                                  _vm._v(
                                                    "\n\t\t\t\t\t\t\t\t\t\t" +
                                                      _vm._s(_vm.username) +
                                                      "\n\t\t\t\t\t\t\t\t\t"
                                                  ),
                                                ]
                                              )
                                            : _c(
                                                "span",
                                                {
                                                  staticClass:
                                                    "text-white font-weight-bold mr-3 text-truncate",
                                                  staticStyle: {
                                                    "max-width": "200px",
                                                  },
                                                },
                                                [
                                                  _c(
                                                    "span",
                                                    {
                                                      staticClass:
                                                        "d-block mb-n2",
                                                    },
                                                    [
                                                      _vm._v(
                                                        _vm._s(
                                                          _vm.account.username
                                                        )
                                                      ),
                                                    ]
                                                  ),
                                                  _vm._v(" "),
                                                  _c(
                                                    "span",
                                                    { staticClass: "small" },
                                                    [
                                                      _vm._v(
                                                        _vm._s(
                                                          _vm.account.domain
                                                        )
                                                      ),
                                                    ]
                                                  ),
                                                ]
                                              ),
                                          _vm._v(" "),
                                          _c(
                                            "span",
                                            {
                                              staticClass:
                                                "text-white font-weight-light",
                                              staticStyle: {
                                                "font-size": "14px",
                                              },
                                            },
                                            [
                                              _vm._v(
                                                _vm._s(
                                                  _vm.timeago(
                                                    _vm.stories[_vm.storyIndex]
                                                      .created_at
                                                  )
                                                )
                                              ),
                                            ]
                                          ),
                                          _vm._v(" "),
                                          _vm.stories[_vm.storyIndex].type ==
                                          "poll"
                                            ? _c("span", [
                                                _c(
                                                  "span",
                                                  {
                                                    staticClass:
                                                      "btn btn-outline-light font-weight-light btn-sm px-1 rounded py-0 ml-2",
                                                  },
                                                  [_vm._v("POLL")]
                                                ),
                                              ])
                                            : _vm._e(),
                                        ]
                                      ),
                                      _vm._v(" "),
                                      _c("div", [
                                        _c(
                                          "button",
                                          {
                                            staticClass:
                                              "btn btn-link btn-sm text-white mr-0 px-1",
                                            on: {
                                              click: function ($event) {
                                                $event.preventDefault()
                                                return _vm.pause.apply(
                                                  null,
                                                  arguments
                                                )
                                              },
                                            },
                                          },
                                          [
                                            _c("i", {
                                              staticClass: "fas fa-lg",
                                              class: [
                                                _vm.paused
                                                  ? "fa-play"
                                                  : "fa-pause",
                                              ],
                                            }),
                                          ]
                                        ),
                                        _vm._v(" "),
                                        _vm.stories[_vm.storyIndex].type ==
                                        "video"
                                          ? _c(
                                              "button",
                                              {
                                                staticClass:
                                                  "btn btn-link text-white px-2",
                                                on: { click: _vm.toggleMute },
                                              },
                                              [
                                                _c("i", {
                                                  staticClass: "fas fa-lg",
                                                  class: [
                                                    _vm.muted
                                                      ? "fa-volume-mute"
                                                      : "fa-volume-up",
                                                  ],
                                                }),
                                              ]
                                            )
                                          : _vm._e(),
                                        _vm._v(" "),
                                        _c(
                                          "button",
                                          {
                                            staticClass:
                                              "btn btn-link text-white px-1",
                                            on: { click: _vm.showMenu },
                                          },
                                          [
                                            _c("i", {
                                              staticClass:
                                                "fas fa-ellipsis-h fa-lg",
                                            }),
                                          ]
                                        ),
                                        _vm._v(" "),
                                        _c(
                                          "button",
                                          {
                                            staticClass:
                                              "d-inline-block d-md-none btn btn-link text-white pl-1 pr-0",
                                            on: { click: _vm.backToFeed },
                                          },
                                          [
                                            _c("i", {
                                              staticClass:
                                                "far fa-times-circle fa-lg",
                                            }),
                                          ]
                                        ),
                                      ]),
                                    ]
                                  ),
                                ]
                              ),
                            ]),
                            _vm._v(" "),
                            _c(
                              "div",
                              {
                                staticStyle: { height: "70vh" },
                                on: { click: _vm.pause },
                              },
                              [
                                _vm.stories[_vm.storyIndex].type == "poll"
                                  ? _c(
                                      "div",
                                      {
                                        staticClass:
                                          "w-100 h-100 d-flex justify-content-center align-items-center",
                                      },
                                      [
                                        _c("div", [
                                          _c(
                                            "p",
                                            {
                                              staticClass:
                                                "text-white pb-5 text-break font-weight-lighter",
                                              class: [
                                                _vm.stories[_vm.storyIndex]
                                                  .question.length < 60
                                                  ? "h1"
                                                  : "h3",
                                              ],
                                            },
                                            [
                                              _vm._v(
                                                "\n\t\t\t\t\t\t\t\t\t" +
                                                  _vm._s(
                                                    _vm.stories[_vm.storyIndex]
                                                      .question
                                                  ) +
                                                  "\n\t\t\t\t\t\t\t\t"
                                              ),
                                            ]
                                          ),
                                          _vm._v(" "),
                                          _c(
                                            "div",
                                            { staticClass: "text-center mt-3" },
                                            _vm._l(
                                              _vm.stories[_vm.storyIndex]
                                                .options,
                                              function (option, index) {
                                                return _c(
                                                  "div",
                                                  { staticClass: "mb-3" },
                                                  [
                                                    _c(
                                                      "button",
                                                      {
                                                        staticClass:
                                                          "btn border px-4 py-3 text-uppercase btn-block",
                                                        class: [
                                                          option.length < 14
                                                            ? "btn-lg"
                                                            : "",
                                                          index ==
                                                          _vm.stories[
                                                            _vm.storyIndex
                                                          ].voted_index
                                                            ? "btn-light"
                                                            : "btn-outline-light",
                                                        ],
                                                        staticStyle: {
                                                          "min-width": "300px",
                                                        },
                                                        attrs: {
                                                          disabled:
                                                            _vm.stories[
                                                              _vm.storyIndex
                                                            ].voted ||
                                                            _vm.owner,
                                                        },
                                                        on: {
                                                          click: function (
                                                            $event
                                                          ) {
                                                            return _vm.selectPollOption(
                                                              index
                                                            )
                                                          },
                                                        },
                                                      },
                                                      [
                                                        _c(
                                                          "span",
                                                          {
                                                            staticClass:
                                                              "text-break",
                                                            class: [
                                                              index ==
                                                              _vm.stories[
                                                                _vm.storyIndex
                                                              ].voted_index
                                                                ? "option-red"
                                                                : "",
                                                            ],
                                                          },
                                                          [
                                                            _vm._v(
                                                              "\n\t\t\t\t\t\t\t\t\t\t\t\t" +
                                                                _vm._s(option) +
                                                                "\n\t\t\t\t\t\t\t\t\t\t\t"
                                                            ),
                                                          ]
                                                        ),
                                                      ]
                                                    ),
                                                    _vm._v(" "),
                                                    _vm.owner &&
                                                    _vm.pollResults.length
                                                      ? _c(
                                                          "p",
                                                          {
                                                            staticClass:
                                                              "small text-left mt-1 text-light",
                                                          },
                                                          [
                                                            _vm._v(
                                                              "\n\t\t\t\t\t\t\t\t\t\t\t\t" +
                                                                _vm._s(
                                                                  _vm.pollPercent(
                                                                    index
                                                                  )
                                                                ) +
                                                                "% - " +
                                                                _vm._s(
                                                                  _vm
                                                                    .pollResults[
                                                                    index
                                                                  ]
                                                                ) +
                                                                " " +
                                                                _vm._s(
                                                                  _vm
                                                                    .pollResults[
                                                                    index
                                                                  ] == 1
                                                                    ? "vote"
                                                                    : "votes"
                                                                ) +
                                                                "\n\t\t\t\t\t\t\t\t\t\t"
                                                            ),
                                                          ]
                                                        )
                                                      : _vm._e(),
                                                  ]
                                                )
                                              }
                                            ),
                                            0
                                          ),
                                          _vm._v(" "),
                                          _vm.owner &&
                                          !_vm.showingPollResults &&
                                          _vm.pollResults.length == 0
                                            ? _c(
                                                "div",
                                                {
                                                  staticClass:
                                                    "mt-3 text-center",
                                                },
                                                [
                                                  _c(
                                                    "button",
                                                    {
                                                      staticClass:
                                                        "btn btn-light font-weight-bold",
                                                      attrs: {
                                                        disabled:
                                                          _vm.loadingPollResults,
                                                      },
                                                      on: {
                                                        click:
                                                          _vm.showPollResults,
                                                      },
                                                    },
                                                    [
                                                      _vm._v(
                                                        "\n\t\t\t\t\t\t\t\t\t\t" +
                                                          _vm._s(
                                                            _vm.loadingPollResults
                                                              ? "Loading..."
                                                              : "View Results"
                                                          ) +
                                                          "\n\t\t\t\t\t\t\t\t\t"
                                                      ),
                                                    ]
                                                  ),
                                                ]
                                              )
                                            : _vm._e(),
                                        ]),
                                      ]
                                    )
                                  : _vm._e(),
                              ]
                            ),
                          ]),
                          _vm._v(" "),
                          !_vm.owner &&
                          _vm.stories[_vm.storyIndex] &&
                          _vm.stories[_vm.storyIndex].can_reply
                            ? _c(
                                "div",
                                {
                                  staticClass:
                                    "card-footer bg-transparent border-0",
                                },
                                [
                                  _c(
                                    "div",
                                    { staticClass: "px-0 bottom-overlay" },
                                    [
                                      _c(
                                        "div",
                                        {
                                          staticClass: "px-3 form-group d-flex",
                                        },
                                        [
                                          _c("input", {
                                            directives: [
                                              {
                                                name: "model",
                                                rawName: "v-model",
                                                value: _vm.composeText,
                                                expression: "composeText",
                                              },
                                            ],
                                            staticClass:
                                              "form-control bg-transparent border border-white rounded-pill text-white",
                                            attrs: {
                                              placeholder:
                                                "Reply to " +
                                                _vm.username +
                                                "...",
                                            },
                                            domProps: {
                                              value: _vm.composeText,
                                            },
                                            on: {
                                              input: function ($event) {
                                                if ($event.target.composing) {
                                                  return
                                                }
                                                _vm.composeText =
                                                  $event.target.value
                                              },
                                            },
                                          }),
                                          _vm._v(" "),
                                          _c(
                                            "button",
                                            {
                                              staticClass:
                                                "btn btn-outline-light rounded-pill ml-2",
                                              on: { click: _vm.comment },
                                            },
                                            [
                                              _vm._v(
                                                "\n\t\t\t\t\t\t\t\tSEND\n\t\t\t\t\t\t\t"
                                              ),
                                            ]
                                          ),
                                        ]
                                      ),
                                    ]
                                  ),
                                ]
                              )
                            : _vm._e(),
                        ]
                      ),
                    ],
                    1
                  )
                : _vm._e(),
              _vm._v(" "),
              _c(
                "div",
                {
                  staticClass:
                    "d-none d-md-block col-md-1 cursor-pointer text-center",
                },
                [
                  _vm.storyIndex + 1 < _vm.stories.length
                    ? _c("div", { on: { click: _vm.next } }, [
                        _c("i", {
                          staticClass:
                            "fas fa-chevron-circle-right text-muted fa-2x",
                        }),
                      ])
                    : _vm._e(),
                  _vm._v(" "),
                  _vm.storyIndex + 1 == _vm.stories.length && _vm.owner
                    ? _c("div", { on: { click: _vm.addToStory } }, [
                        _c("i", {
                          staticClass: "fal fa-plus-circle text-muted fa-2x",
                        }),
                      ])
                    : _vm._e(),
                ]
              ),
              _vm._v(" "),
              _vm.loading
                ? _c("div", { staticClass: "col-12 col-md-6 rounded-lg" }, [
                    _vm._m(2),
                  ])
                : _vm._e(),
            ]
          )
        : _c(
            "div",
            {
              staticClass:
                "row d-flex justify-content-center align-items-center",
            },
            [
              !_vm.loading
                ? _c("div", { staticClass: "col-12 col-md-6 rounded-lg p-0" }, [
                    _vm.stories[_vm.storyIndex].type == "photo"
                      ? _c("div", {
                          key: "msl:" + _vm.storyIndex,
                          staticClass: "media-slot rounded-lg",
                          style: {
                            backgroundImage:
                              "url(" + _vm.stories[_vm.storyIndex].url + ")",
                          },
                        })
                      : _vm._e(),
                    _vm._v(" "),
                    _c(
                      "div",
                      {
                        staticClass:
                          "story-viewer-component-card card bg-transparent border-0 shadow-none d-flex justify-content-center",
                        staticStyle: {
                          "backdrop-filter": "blur(40px) brightness(0.3)",
                          "-webkit-backdrop-filter": "blur(10px)",
                        },
                      },
                      [
                        _c("div", { staticClass: "card-body" }, [
                          _c(
                            "div",
                            {
                              staticClass:
                                "w-100 h-100 d-flex justify-content-center align-items-center",
                            },
                            [
                              _c("div", { staticClass: "text-center" }, [
                                _c("img", {
                                  staticClass:
                                    "rounded-circle border mb-3 shadow",
                                  attrs: {
                                    src: _vm.profile.avatar,
                                    width: "120",
                                    height: "120",
                                  },
                                }),
                                _vm._v(" "),
                                _c(
                                  "p",
                                  { staticClass: "lead text-lighter mb-1" },
                                  [
                                    _vm._v("View as "),
                                    _c("span", { staticClass: "text-white" }, [
                                      _vm._v(_vm._s(_vm.profile.username)),
                                    ]),
                                  ]
                                ),
                                _vm._v(" "),
                                _c(
                                  "p",
                                  {
                                    staticClass:
                                      "text-lighter font-weight-lighter px-md-5 py-3",
                                  },
                                  [
                                    _c(
                                      "span",
                                      {
                                        staticClass:
                                          "text-white font-weight-bold",
                                      },
                                      [_vm._v(_vm._s(_vm.account.acct))]
                                    ),
                                    _vm._v(
                                      " will be able to see that you viewed their story.\n\t\t\t\t\t\t\t"
                                    ),
                                  ]
                                ),
                                _vm._v(" "),
                                _c(
                                  "button",
                                  {
                                    staticClass:
                                      "btn btn-outline-lighter rounded-pill py-1 font-weight-bold",
                                    on: { click: _vm.confirmViewStory },
                                  },
                                  [_vm._v("View Story")]
                                ),
                              ]),
                            ]
                          ),
                        ]),
                      ]
                    ),
                  ])
                : _vm._e(),
            ]
          ),
      _vm._v(" "),
      _c(
        "div",
        { staticClass: "modal-stack" },
        [
          _c(
            "b-modal",
            {
              ref: "ctxMenu",
              attrs: {
                id: "ctx-modal",
                "hide-header": "",
                "hide-footer": "",
                centered: "",
                rounded: "",
                size: "sm",
                "body-class": "list-group-flush p-0 rounded",
              },
            },
            [
              _c("div", { staticClass: "list-group text-center" }, [
                _vm.owner
                  ? _c("div", { staticClass: "list-group-item rounded py-3" }, [
                      _c(
                        "div",
                        {
                          staticClass:
                            "d-flex justify-content-between align-items-center font-weight-light",
                        },
                        [
                          _c("span", [
                            _vm._v(
                              "Expires in " +
                                _vm._s(
                                  _vm.timeahead(
                                    _vm.stories[_vm.storyIndex].expires_at
                                  )
                                )
                            ),
                          ]),
                          _vm._v(" "),
                          _c("span", [
                            _c(
                              "span",
                              {
                                staticClass:
                                  "btn btn-light btn-sm font-weight-bold",
                              },
                              [
                                _c("i", { staticClass: "fas fa-eye" }),
                                _vm._v(
                                  "\n\t\t\t\t\t\t\t\t" +
                                    _vm._s(
                                      _vm.stories[_vm.storyIndex].view_count
                                    ) +
                                    "\n\t\t\t\t\t\t\t"
                                ),
                              ]
                            ),
                          ]),
                        ]
                      ),
                    ])
                  : _vm._e(),
                _vm._v(" "),
                !_vm.owner &&
                _vm.stories[_vm.storyIndex] &&
                _vm.stories[_vm.storyIndex].can_react
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item rounded d-flex justify-content-between",
                      },
                      _vm._l(_vm.reactionEmoji, function (e) {
                        return _c(
                          "button",
                          {
                            staticClass: "btn btn-light rounded-pill py-1 px-2",
                            staticStyle: { "font-size": "20px" },
                            on: {
                              click: function ($event) {
                                return _vm.react(e)
                              },
                            },
                          },
                          [
                            _vm._v(
                              "\n\t\t\t\t\t\t" + _vm._s(e) + "\n\t\t\t\t\t"
                            ),
                          ]
                        )
                      }),
                      0
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.owner
                  ? _c(
                      "div",
                      {
                        staticClass: "list-group-item rounded cursor-pointer",
                        on: { click: _vm.fetchViewers },
                      },
                      [_vm._v("Viewers")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                !_vm.owner
                  ? _c(
                      "div",
                      {
                        staticClass: "list-group-item rounded cursor-pointer",
                        on: { click: _vm.ctxMenuReport },
                      },
                      [_vm._v("Report")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.owner
                  ? _c(
                      "div",
                      {
                        staticClass: "list-group-item rounded cursor-pointer",
                        on: { click: _vm.deleteStory },
                      },
                      [_vm._v("Delete")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer text-muted",
                    on: { click: _vm.closeCtxMenu },
                  },
                  [_vm._v("Close")]
                ),
              ]),
            ]
          ),
          _vm._v(" "),
          _c(
            "b-modal",
            {
              ref: "viewersModal",
              attrs: {
                id: "viewers",
                title: "Viewers",
                "header-class": "border-0",
                "hide-footer": "",
                centered: "",
                rounded: "",
                scrollable: "",
                lazy: "",
                size: "sm",
                "body-class": "list-group-flush p-0 rounded",
              },
            },
            [
              _c(
                "div",
                {
                  staticClass: "list-group",
                  staticStyle: { "max-height": "40vh" },
                },
                [
                  _vm._l(_vm.viewers, function (profile, index) {
                    return _c("div", { staticClass: "list-group-item" }, [
                      _c("div", { staticClass: "media align-items-center" }, [
                        _c("img", {
                          staticClass: "rounded-circle border mr-2",
                          attrs: {
                            src: profile.avatar,
                            width: "32",
                            height: "32",
                          },
                        }),
                        _vm._v(" "),
                        profile.local
                          ? _c(
                              "div",
                              { staticClass: "media-body user-select-none" },
                              [
                                _c(
                                  "p",
                                  { staticClass: "font-weight-bold mb-0" },
                                  [_vm._v(_vm._s(profile.username))]
                                ),
                              ]
                            )
                          : _c(
                              "div",
                              { staticClass: "media-body user-select-none" },
                              [
                                _c(
                                  "p",
                                  { staticClass: "font-weight-bold mb-0" },
                                  [_vm._v(_vm._s(profile.username))]
                                ),
                                _vm._v(" "),
                                _c(
                                  "p",
                                  {
                                    staticClass: "mb-0 small mt-n1 text-muted",
                                  },
                                  [_vm._v(_vm._s(profile.acct.split("@")[1]))]
                                ),
                              ]
                            ),
                      ]),
                    ])
                  }),
                  _vm._v(" "),
                  _vm.viewers.length == 0
                    ? _c(
                        "div",
                        {
                          staticClass:
                            "list-group-item text-center text-dark font-weight-light py-5",
                        },
                        [_vm._v("\n\t\t\t\t\tNo viewers yet\n\t\t\t\t")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  _vm.viewersHasMore
                    ? _c(
                        "div",
                        {
                          staticClass:
                            "list-group-item text-center border-bottom-0",
                        },
                        [
                          _c(
                            "button",
                            {
                              staticClass:
                                "btn btn-light font-weight-bold border rounded-pill",
                              on: { click: _vm.viewersLoadMore },
                            },
                            [_vm._v("Load More")]
                          ),
                        ]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  _c(
                    "div",
                    {
                      staticClass:
                        "list-group-item text-center rounded cursor-pointer text-muted",
                      on: { click: _vm.closeViewersModal },
                    },
                    [_vm._v("Close")]
                  ),
                ],
                2
              ),
            ]
          ),
          _vm._v(" "),
          _c(
            "b-modal",
            {
              ref: "ctxReport",
              attrs: {
                id: "ctx-report",
                "hide-header": "",
                "hide-footer": "",
                centered: "",
                rounded: "",
                size: "sm",
                "body-class": "list-group-flush p-0 rounded",
              },
            },
            [
              _c("p", { staticClass: "py-2 px-3 mb-0" }),
              _c(
                "div",
                { staticClass: "text-center font-weight-bold text-danger" },
                [_vm._v("Report")]
              ),
              _vm._v(" "),
              _c("div", { staticClass: "small text-center text-muted" }, [
                _vm._v("Select one of the following options"),
              ]),
              _vm._v(" "),
              _c("p"),
              _vm._v(" "),
              _c("div", { staticClass: "list-group text-center" }, [
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("spam")
                      },
                    },
                  },
                  [_vm._v("Spam")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("sensitive")
                      },
                    },
                  },
                  [_vm._v("Sensitive Content")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("abusive")
                      },
                    },
                  },
                  [_vm._v("Abusive or Harmful")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.openCtxReportOtherMenu()
                      },
                    },
                  },
                  [_vm._v("Other")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer text-lighter",
                    on: {
                      click: function ($event) {
                        return _vm.ctxReportMenuGoBack()
                      },
                    },
                  },
                  [_vm._v("Cancel")]
                ),
              ]),
            ]
          ),
          _vm._v(" "),
          _c(
            "b-modal",
            {
              ref: "ctxReportOther",
              attrs: {
                id: "ctx-report-other",
                "hide-header": "",
                "hide-footer": "",
                centered: "",
                rounded: "",
                size: "sm",
                "body-class": "list-group-flush p-0 rounded",
              },
            },
            [
              _c("p", { staticClass: "py-2 px-3 mb-0" }),
              _c(
                "div",
                { staticClass: "text-center font-weight-bold text-danger" },
                [_vm._v("Report")]
              ),
              _vm._v(" "),
              _c("div", { staticClass: "small text-center text-muted" }, [
                _vm._v("Select one of the following options"),
              ]),
              _vm._v(" "),
              _c("p"),
              _vm._v(" "),
              _c("div", { staticClass: "list-group text-center" }, [
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("underage")
                      },
                    },
                  },
                  [_vm._v("Underage Account")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("copyright")
                      },
                    },
                  },
                  [_vm._v("Copyright Infringement")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("impersonation")
                      },
                    },
                  },
                  [_vm._v("Impersonation")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.sendReport("scam")
                      },
                    },
                  },
                  [_vm._v("Scam or Fraud")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item rounded cursor-pointer text-lighter",
                    on: {
                      click: function ($event) {
                        return _vm.ctxReportOtherMenuGoBack()
                      },
                    },
                  },
                  [_vm._v("Cancel")]
                ),
              ]),
            ]
          ),
        ],
        1
      ),
    ]
  )
}
var staticRenderFns = [
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c(
      "div",
      {
        staticClass:
          "d-flex justify-content-center align-items-center rounded-pill shadow-lg",
        staticStyle: {
          width: "120px",
          height: "30px",
          "font-size": "13px",
          "background-color": "rgba(0, 0, 0, 0.6)",
        },
      },
      [_c("span", { staticClass: "text-lighter" }, [_vm._v("Reaction sent")])]
    )
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c(
      "div",
      {
        staticClass:
          "d-flex justify-content-center align-items-center rounded-pill shadow-lg",
        staticStyle: {
          width: "120px",
          height: "30px",
          "font-size": "13px",
          "background-color": "rgba(0, 0, 0, 0.6)",
        },
      },
      [_c("span", { staticClass: "text-lighter" }, [_vm._v("Reply sent")])]
    )
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c(
      "div",
      {
        staticClass: "card border-0 shadow-none d-flex justify-content-center",
        staticStyle: { background: "#000", height: "90vh" },
      },
      [
        _c(
          "div",
          {
            staticClass:
              "card-body d-flex justify-content-center align-items-center",
          },
          [
            _c(
              "div",
              {
                staticClass: "spinner-border text-lighter",
                attrs: { role: "status" },
              },
              [_c("span", { staticClass: "sr-only" }, [_vm._v("Loading...")])]
            ),
          ]
        ),
      ]
    )
  },
]
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return normalizeComponent; });
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () {
        injectStyles.call(
          this,
          (options.functional ? this.parent : this).$root.$options.shadowRoot
        )
      }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functional component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}


/***/ }),

/***/ "./resources/assets/js/components/StoryViewer.vue":
/*!********************************************************!*\
  !*** ./resources/assets/js/components/StoryViewer.vue ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true& */ "./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true&");
/* harmony import */ var _StoryViewer_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StoryViewer.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& */ "./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _StoryViewer_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "be0d9900",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/StoryViewer.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js&":
/*!*********************************************************************************!*\
  !*** ./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib??ref--4-0!../../../../node_modules/vue-loader/lib??vue-loader-options!./StoryViewer.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&":
/*!******************************************************************************************************************!*\
  !*** ./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& ***!
  \******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_9_2_node_modules_sass_loader_dist_cjs_js_ref_9_3_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader!../../../../node_modules/css-loader!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--9-2!../../../../node_modules/sass-loader/dist/cjs.js??ref--9-3!../../../../node_modules/vue-loader/lib??vue-loader-options!./StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=style&index=0&id=be0d9900&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_9_2_node_modules_sass_loader_dist_cjs_js_ref_9_3_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_9_2_node_modules_sass_loader_dist_cjs_js_ref_9_3_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_9_2_node_modules_sass_loader_dist_cjs_js_ref_9_3_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_9_2_node_modules_sass_loader_dist_cjs_js_ref_9_3_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_style_index_0_id_be0d9900_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));


/***/ }),

/***/ "./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true&":
/*!***************************************************************************************************!*\
  !*** ./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true& ***!
  \***************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/StoryViewer.vue?vue&type=template&id=be0d9900&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_StoryViewer_vue_vue_type_template_id_be0d9900_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/stories.js":
/*!****************************************!*\
  !*** ./resources/assets/js/stories.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

Vue.component('story-viewer', __webpack_require__(/*! ./components/StoryViewer.vue */ "./resources/assets/js/components/StoryViewer.vue")["default"]);

/***/ }),

/***/ 26:
/*!**********************************************!*\
  !*** multi ./resources/assets/js/stories.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Users/dansup/Github/pixelfed/resources/assets/js/stories.js */"./resources/assets/js/stories.js");


/***/ })

},[[26,"/js/manifest"]]]);