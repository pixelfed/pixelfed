(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["/js/memoryprofile"],{

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_masonry_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-masonry-css */ "./node_modules/vue-masonry-css/dist/vue-masonry.es2015.js");
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
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  props: ['profile-id', 'profile-layout', 'profile-settings', 'profile-username'],
  data: function data() {
    return {
      ids: [],
      profile: {},
      user: false,
      timeline: [],
      timelinePage: 1,
      min_id: 0,
      max_id: 0,
      loading: true,
      owner: false,
      layout: this.profileLayout,
      mode: 'grid',
      modes: ['grid', 'collections', 'bookmarks'],
      modalStatus: false,
      relationship: {},
      followers: [],
      followerCursor: 1,
      followerMore: true,
      following: [],
      followingCursor: 1,
      followingMore: true,
      warning: false,
      sponsorList: [],
      bookmarks: [],
      bookmarksPage: 2,
      collections: [],
      collectionsPage: 2,
      isMobile: false,
      ctxEmbedPayload: null,
      copiedEmbed: false,
      hasStory: null,
      followingModalSearch: null,
      followingModalSearchCache: null,
      followingModalTab: 'following',
      bookmarksLoading: true,
      page: 'home',
      pages: ['home', 'collections', 'places', 'saved', 'likes', '404'],
      likesCursor: 1,
      likesTimeline: []
    };
  },
  beforeMount: function beforeMount() {
    this.fetchRelationships();
    this.fetchProfile();
    var u = new URLSearchParams(window.location.search);
    var forceMetro = localStorage.getItem('pf_metro_ui.exp.forceMetro') == 'true';

    if (forceMetro == true || u.has('ui') && u.get('ui') == 'metro' && this.layout != 'metro') {
      this.layout = 'metro';
    }

    if (u.has('ui') && u.get('ui') == 'moment' && this.layout != 'moment') {
      Vue.use(vue_masonry_css__WEBPACK_IMPORTED_MODULE_0__["default"]);
      this.layout = 'moment';
    }

    if (this.layout == 'metro' && u.has('t')) {
      if (this.modes.indexOf(u.get('t')) != -1) {
        if (u.get('t') == 'bookmarks') {
          return;
        }

        this.mode = u.get('t');
      }
    }
  },
  mounted: function mounted() {
    var _this = this;

    var u = new URLSearchParams(window.location.search);

    if (u.has('md') && u.get('md') == 'followers') {
      this.followersModal();
    }

    if (u.has('md') && u.get('md') == 'following') {
      this.followingModal();
    }

    if (document.querySelectorAll('body')[0].classList.contains('loggedIn') == true) {
      axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(function (res) {
        _this.user = res.data;
        window._sharedData.curUser = res.data;
        window.App.util.navatar();

        if (res.data.id == _this.profileId || _this.relationship.following == true) {
          axios.get('/api/web/stories/v1/exists/' + _this.profileId).then(function (res) {
            _this.hasStory = res.data == true;
          });
        }
      });
    }

    if (window.outerWidth < 576) {
      $('nav.navbar').hide();
      this.isMobile = true;
    }

    $('body').css('background', '#d4d4d4');
  },
  updated: function updated() {
    $('[data-toggle="tooltip"]').tooltip();
  },
  methods: {
    fetchProfile: function fetchProfile() {
      var _this2 = this;

      axios.get('/api/pixelfed/v1/accounts/' + this.profileId).then(function (res) {
        _this2.profile = res.data;
      }).then(function (res) {
        _this2.fetchPosts();
      });
    },
    fetchPosts: function fetchPosts() {
      var _this3 = this;

      var apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
      axios.get(apiUrl, {
        params: {
          limit: 12,
          only_media: true,
          min_id: 1
        }
      }).then(function (res) {
        var data = res.data.filter(function (status) {
          return status.media_attachments.length > 0;
        });
        var ids = data.map(function (status) {
          return status.id;
        });
        _this3.ids = ids;
        _this3.min_id = Math.max.apply(Math, _toConsumableArray(ids));
        _this3.max_id = Math.min.apply(Math, _toConsumableArray(ids));
        _this3.modalStatus = _.first(res.data);
        _this3.timelineCache = data;
        _this3.timeline = data;

        _this3.ownerCheck();

        if (document.querySelectorAll('body')[0].classList.contains('loggedIn')) {
          _this3.fetchLikes();
        }

        _this3.loading = false; //this.loadSponsor();
      })["catch"](function (err) {
        swal('Oops, something went wrong', 'Please release the page.', 'error');
      });
    },
    ownerCheck: function ownerCheck() {
      if ($('body').hasClass('loggedIn') == false) {
        this.owner = false;
        return;
      }

      this.owner = this.profile.id === this.user.id;
    },
    infiniteTimeline: function infiniteTimeline($state) {
      var _this4 = this;

      if (this.loading || this.timeline.length < 9) {
        $state.complete();
        return;
      }

      var apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
      axios.get(apiUrl, {
        params: {
          only_media: true,
          max_id: this.max_id
        }
      }).then(function (res) {
        if (res.data.length && _this4.loading == false) {
          var data = res.data;
          var self = _this4;
          data.forEach(function (d) {
            if (self.ids.indexOf(d.id) == -1) {
              self.timeline.push(d);
              self.ids.push(d.id);
            }
          });
          var max = Math.min.apply(Math, _toConsumableArray(_this4.ids));

          if (max == _this4.max_id) {
            $state.complete();
            return;
          }

          _this4.min_id = Math.max.apply(Math, _toConsumableArray(_this4.ids));
          _this4.max_id = max;
          $state.loaded();
          _this4.loading = false;
        } else {
          $state.complete();
        }
      });
    },
    previewUrl: function previewUrl(status) {
      return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
    },
    previewBackground: function previewBackground(status) {
      var preview = this.previewUrl(status);
      return 'background-image: url(' + preview + ');';
    },
    switchMode: function switchMode(mode) {
      var _this5 = this;

      this.page = _.indexOf(this.pages, mode) ? mode : 'home';

      if (this.page == 'saved' && this.bookmarks.length == 0) {
        axios.get('/api/local/bookmarks').then(function (res) {
          _this5.bookmarks = res.data;
          _this5.bookmarksLoading = false;
        });
      }

      if (this.page == 'collections' && this.collections.length == 0) {
        axios.get('/api/local/profile/collections/' + this.profileId).then(function (res) {
          _this5.collections = res.data;
        });
      }
    },
    reportProfile: function reportProfile() {
      var id = this.profile.id;
      window.location.href = '/i/report?type=user&id=' + id;
    },
    reportUrl: function reportUrl(status) {
      var type = status.in_reply_to ? 'comment' : 'post';
      var id = status.id;
      return '/i/report?type=' + type + '&id=' + id;
    },
    commentFocus: function commentFocus(status, $event) {
      var el = event.target;
      var card = el.parentElement.parentElement.parentElement;
      var comments = card.getElementsByClassName('comments')[0];

      if (comments.children.length == 0) {
        comments.classList.add('mb-2');
        this.fetchStatusComments(status, card);
      }

      var footer = card.querySelectorAll('.card-footer')[0];
      var input = card.querySelectorAll('.status-reply-input')[0];

      if (footer.classList.contains('d-none') == true) {
        footer.classList.remove('d-none');
        input.focus();
      } else {
        footer.classList.add('d-none');
        input.blur();
      }
    },
    likeStatus: function likeStatus(status, $event) {
      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      axios.post('/i/like', {
        item: status.id
      }).then(function (res) {
        status.favourites_count = res.data.count;

        if (status.favourited == true) {
          status.favourited = false;
        } else {
          status.favourited = true;
        }
      })["catch"](function (err) {
        swal('Error', 'Something went wrong, please try again later.', 'error');
      });
    },
    shareStatus: function shareStatus(status, $event) {
      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      axios.post('/i/share', {
        item: status.id
      }).then(function (res) {
        status.reblogs_count = res.data.count;

        if (status.reblogged == true) {
          status.reblogged = false;
        } else {
          status.reblogged = true;
        }
      })["catch"](function (err) {
        swal('Error', 'Something went wrong, please try again later.', 'error');
      });
    },
    timestampFormat: function timestampFormat(timestamp) {
      var ts = new Date(timestamp);
      return ts.toDateString() + ' ' + ts.toLocaleTimeString();
    },
    editUrl: function editUrl(status) {
      return status.url + '/edit';
    },
    redirect: function redirect(url) {
      window.location.href = url;
      return;
    },
    remoteRedirect: function remoteRedirect(url) {
      window.location.href = window.App.config.site.url + '/i/redirect?url=' + encodeURIComponent(url);
      return;
    },
    replyUrl: function replyUrl(status) {
      var username = this.profile.username;
      var id = status.account.id == this.profile.id ? status.id : status.in_reply_to_id;
      return '/p/' + username + '/' + id;
    },
    mentionUrl: function mentionUrl(status) {
      var username = status.account.username;
      var id = status.id;
      return '/p/' + username + '/' + id;
    },
    statusOwner: function statusOwner(status) {
      var sid = status.account.id;
      var uid = this.profile.id;

      if (sid == uid) {
        return true;
      } else {
        return false;
      }
    },
    fetchRelationships: function fetchRelationships() {
      var _this6 = this;

      if (document.querySelectorAll('body')[0].classList.contains('loggedIn') == false) {
        return;
      }

      axios.get('/api/pixelfed/v1/accounts/relationships', {
        params: {
          'id[]': this.profileId
        }
      }).then(function (res) {
        if (res.data.length) {
          _this6.relationship = res.data[0];

          if (res.data[0].blocking == true) {
            _this6.warning = true;
          }
        }
      });
    },
    muteProfile: function muteProfile() {
      var _this7 = this;

      var status = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      var id = this.profileId;
      axios.post('/i/mute', {
        type: 'user',
        item: id
      }).then(function (res) {
        _this7.fetchRelationships();

        _this7.$refs.visitorContextMenu.hide();

        swal('Success', 'You have successfully muted ' + _this7.profile.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    unmuteProfile: function unmuteProfile() {
      var _this8 = this;

      var status = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      var id = this.profileId;
      axios.post('/i/unmute', {
        type: 'user',
        item: id
      }).then(function (res) {
        _this8.fetchRelationships();

        _this8.$refs.visitorContextMenu.hide();

        swal('Success', 'You have successfully unmuted ' + _this8.profile.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    blockProfile: function blockProfile() {
      var _this9 = this;

      var status = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      var id = this.profileId;
      axios.post('/i/block', {
        type: 'user',
        item: id
      }).then(function (res) {
        _this9.warning = true;

        _this9.fetchRelationships();

        _this9.$refs.visitorContextMenu.hide();

        swal('Success', 'You have successfully blocked ' + _this9.profile.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    unblockProfile: function unblockProfile() {
      var _this10 = this;

      var status = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      var id = this.profileId;
      axios.post('/i/unblock', {
        type: 'user',
        item: id
      }).then(function (res) {
        _this10.fetchRelationships();

        _this10.$refs.visitorContextMenu.hide();

        swal('Success', 'You have successfully unblocked ' + _this10.profile.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    deletePost: function deletePost(status, index) {
      var _this11 = this;

      if ($('body').hasClass('loggedIn') == false || status.account.id !== this.profile.id) {
        return;
      }

      axios.post('/i/delete', {
        type: 'status',
        item: status.id
      }).then(function (res) {
        _this11.timeline.splice(index, 1);

        swal('Success', 'You have successfully deleted this post', 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    followProfile: function followProfile() {
      var _this12 = this;

      if ($('body').hasClass('loggedIn') == false) {
        window.location.href = encodeURI('/login?next=/' + this.profileUsername + '/');
        return;
      }

      axios.post('/i/follow', {
        item: this.profileId
      }).then(function (res) {
        _this12.$refs.visitorContextMenu.hide();

        if (_this12.relationship.following) {
          _this12.profile.followers_count--;

          if (_this12.profile.locked == true) {
            window.location.href = '/';
          }
        } else {
          _this12.profile.followers_count++;
        }

        _this12.relationship.following = !_this12.relationship.following;
      })["catch"](function (err) {
        if (err.response.data.message) {
          swal('Error', err.response.data.message, 'error');
        }
      });
    },
    followingModal: function followingModal() {
      var _this13 = this;

      if ($('body').hasClass('loggedIn') == false) {
        window.location.href = encodeURI('/login?next=/' + this.profileUsername + '/');
        return;
      }

      if (this.profileSettings.following.list == false) {
        return;
      }

      if (this.followingCursor > 1) {
        this.$refs.followingModal.show();
        return;
      } else {
        axios.get('/api/pixelfed/v1/accounts/' + this.profileId + '/following', {
          params: {
            page: this.followingCursor
          }
        }).then(function (res) {
          _this13.following = res.data;
          _this13.followingModalSearchCache = res.data;
          _this13.followingCursor++;

          if (res.data.length < 10) {
            _this13.followingMore = false;
          }
        });
        this.$refs.followingModal.show();
        return;
      }
    },
    followersModal: function followersModal() {
      var _this14 = this;

      if ($('body').hasClass('loggedIn') == false) {
        window.location.href = encodeURI('/login?next=/' + this.profileUsername + '/');
        return;
      }

      if (this.profileSettings.followers.list == false) {
        return;
      }

      if (this.followerCursor > 1) {
        this.$refs.followerModal.show();
        return;
      } else {
        axios.get('/api/pixelfed/v1/accounts/' + this.profileId + '/followers', {
          params: {
            page: this.followerCursor
          }
        }).then(function (res) {
          var _this14$followers;

          (_this14$followers = _this14.followers).push.apply(_this14$followers, _toConsumableArray(res.data));

          _this14.followerCursor++;

          if (res.data.length < 10) {
            _this14.followerMore = false;
          }
        });
        this.$refs.followerModal.show();
        return;
      }
    },
    followingLoadMore: function followingLoadMore() {
      var _this15 = this;

      if ($('body').hasClass('loggedIn') == false) {
        window.location.href = encodeURI('/login?next=/' + this.profile.username + '/');
        return;
      }

      axios.get('/api/pixelfed/v1/accounts/' + this.profile.id + '/following', {
        params: {
          page: this.followingCursor,
          fbu: this.followingModalSearch
        }
      }).then(function (res) {
        if (res.data.length > 0) {
          var _this15$following;

          (_this15$following = _this15.following).push.apply(_this15$following, _toConsumableArray(res.data));

          _this15.followingCursor++;
          _this15.followingModalSearchCache = _this15.following;
        }

        if (res.data.length < 10) {
          _this15.followingModalSearchCache = _this15.following;
          _this15.followingMore = false;
        }
      });
    },
    followersLoadMore: function followersLoadMore() {
      var _this16 = this;

      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      axios.get('/api/pixelfed/v1/accounts/' + this.profile.id + '/followers', {
        params: {
          page: this.followerCursor
        }
      }).then(function (res) {
        if (res.data.length > 0) {
          var _this16$followers;

          (_this16$followers = _this16.followers).push.apply(_this16$followers, _toConsumableArray(res.data));

          _this16.followerCursor++;
        }

        if (res.data.length < 10) {
          _this16.followerMore = false;
        }
      });
    },
    visitorMenu: function visitorMenu() {
      this.$refs.visitorContextMenu.show();
    },
    followModalAction: function followModalAction(id, index) {
      var _this17 = this;

      var type = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'following';
      axios.post('/i/follow', {
        item: id
      }).then(function (res) {
        if (type == 'following') {
          _this17.following.splice(index, 1);

          _this17.profile.following_count--;
        }
      })["catch"](function (err) {
        if (err.response.data.message) {
          swal('Error', err.response.data.message, 'error');
        }
      });
    },
    momentBackground: function momentBackground() {
      var c = 'w-100 h-100 mt-n3 ';

      if (this.profile.header_bg) {
        c += this.profile.header_bg == 'default' ? 'bg-pixelfed' : 'bg-moment-' + this.profile.header_bg;
      } else {
        c += 'bg-pixelfed';
      }

      return c;
    },
    loadSponsor: function loadSponsor() {
      var _this18 = this;

      axios.get('/api/local/profile/sponsor/' + this.profileId).then(function (res) {
        _this18.sponsorList = res.data;
      });
    },
    showSponsorModal: function showSponsorModal() {
      this.$refs.sponsorModal.show();
    },
    goBack: function goBack() {
      if (window.history.length > 2) {
        window.history.back();
        return;
      } else {
        window.location.href = '/';
        return;
      }
    },
    copyProfileLink: function copyProfileLink() {
      var url = window.App.config.site.url + '/' + this.profileUsername + '/';
      navigator.clipboard.writeText(url);
      this.$refs.visitorContextMenu.hide();
    },
    formatCount: function formatCount(count) {
      return App.util.format.count(count);
    },
    statusUrl: function statusUrl(status) {
      return status.url;

      if (status.local == true) {
        return status.url;
      }

      return '/i/web/post/_/' + status.account.id + '/' + status.id;
    },
    profileUrl: function profileUrl(status) {
      return status.url;

      if (status.local == true) {
        return status.account.url;
      }

      return '/i/web/profile/_/' + status.account.id;
    },
    showEmbedProfileModal: function showEmbedProfileModal() {
      this.ctxEmbedPayload = window.App.util.embed.profile(this.profile.url);
      this.$refs.visitorContextMenu.hide();
      this.$refs.embedModal.show();
    },
    ctxCopyEmbed: function ctxCopyEmbed() {
      navigator.clipboard.writeText(this.ctxEmbedPayload);
      this.$refs.embedModal.hide();
      this.$refs.visitorContextMenu.hide();
    },
    storyRedirect: function storyRedirect() {
      window.location.href = '/stories/' + this.profileUsername;
    },
    followingModalSearchHandler: function followingModalSearchHandler() {
      var _this19 = this;

      var self = this;
      var q = this.followingModalSearch;

      if (q.length == 0) {
        this.following = this.followingModalSearchCache;
        this.followingModalSearch = null;
      }

      if (q.length > 0) {
        var url = '/api/pixelfed/v1/accounts/' + self.profileId + '/following?page=1&fbu=' + q;
        axios.get(url).then(function (res) {
          _this19.following = res.data;
        })["catch"](function (err) {
          self.following = self.followingModalSearchCache;
          self.followingModalSearch = null;
        });
      }
    },
    pageUrl: function pageUrl() {
      var page = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'home';

      switch (page) {
        case 'home':
          return this.profile.url;
          break;

        case 'collections':
          return this.profile.url + '?p=collections';
          break;

        case 'places':
          return this.profile.url + '?p=places';
          break;

        case 'saved':
          return this.profile.url + '?p=saved';
          break;

        case 'likes':
          return this.profile.url + '?p=likes';
          break;

        default:
          return this.profile.url;
          break;
      }
    },
    navigateToPage: function navigateToPage() {
      var page = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'home';
      this.page = page;

      if (page == 'collections') {
        this.switchMode('collections');
      }

      if (page == 'likes') {
        this.switchMode('likes');
      }

      if (page == 'saved') {
        this.switchMode('saved');
      }
    },
    truncate: function truncate(str) {
      var len = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 24;
      return _.truncate(str, {
        length: len
      });
    },
    timeago: function timeago(time) {
      return window.App.util.format.timeAgo(time);
    },
    fetchLikes: function fetchLikes() {
      var self = this;
      var apiUrl = '/api/pixelfed/v1/favourites';
      axios.get(apiUrl, {
        params: {
          page: this.likesCursor
        }
      }).then(function (res) {
        if (res.data.length) {
          var data = res.data.filter(function (r) {
            return r.media_attachments && r.media_attachments.length;
          });
          self.likesTimeline = data;
          self.likesCursor = 2;
        }
      });
    },
    infiniteLikes: function infiniteLikes($state) {
      var self = this;
      var apiUrl = '/api/pixelfed/v1/favourites';
      axios.get(apiUrl, {
        params: {
          page: this.likesCursor
        }
      }).then(function (res) {
        if (res.data.length) {
          var data = res.data;
          data.forEach(function (d) {
            if (d.media_attachments && d.media_attachments.length) {
              self.likesTimeline.push(d);
            }
          });
          self.likesCursor++;
          $state.loaded();
        } else {
          $state.complete();
        }
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  props: ['feed', 'status', 'profile', 'size', 'modal'],
  data: function data() {
    return {
      activeSession: false
    };
  },
  mounted: function mounted() {
    var el = document.querySelector('body');
    this.activeSession = el.classList.contains('loggedIn') ? true : false;
  },
  methods: {
    reportUrl: function reportUrl(status) {
      var type = status.in_reply_to ? 'comment' : 'post';
      var id = status.id;
      return '/i/report?type=' + type + '&id=' + id;
    },
    timestampFormat: function timestampFormat(timestamp) {
      var ts = new Date(timestamp);
      return ts.toDateString() + ' ' + ts.toLocaleTimeString();
    },
    editUrl: function editUrl(status) {
      return status.url + '/edit';
    },
    redirect: function redirect(url) {
      window.location.href = url;
      return;
    },
    replyUrl: function replyUrl(status) {
      var username = this.profile.username;
      var id = status.account.id == this.profile.id ? status.id : status.in_reply_to_id;
      return '/p/' + username + '/' + id;
    },
    mentionUrl: function mentionUrl(status) {
      var username = status.account.username;
      var id = status.id;
      return '/p/' + username + '/' + id;
    },
    statusOwner: function statusOwner(status) {
      var sid = parseInt(status.account.id);
      var uid = parseInt(this.profile.id);

      if (sid == uid) {
        return true;
      } else {
        return false;
      }
    },
    deletePost: function deletePost() {
      this.$emit('deletePost');
      $('#mt_pid_' + this.status.id).modal('hide');
    },
    hidePost: function hidePost(status) {
      status.sensitive = true;
      $('#mt_pid_' + status.id).modal('hide');
    },
    moderatePost: function moderatePost(status, action, $event) {
      var username = status.account.username;

      switch (action) {
        case 'autocw':
          var msg = 'Are you sure you want to enforce CW for ' + username + ' ?';
          swal({
            title: 'Confirm',
            text: msg,
            icon: 'warning',
            buttons: true,
            dangerMode: true
          });
          break;

        case 'suspend':
          msg = 'Are you sure you want to suspend the account of ' + username + ' ?';
          swal({
            title: 'Confirm',
            text: msg,
            icon: 'warning',
            buttons: true,
            dangerMode: true
          });
          break;
      }
    },
    muteProfile: function muteProfile(status) {
      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      axios.post('/i/mute', {
        type: 'user',
        item: status.account.id
      }).then(function (res) {
        swal('Success', 'You have successfully muted ' + status.account.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    blockProfile: function blockProfile(status) {
      if ($('body').hasClass('loggedIn') == false) {
        return;
      }

      axios.post('/i/block', {
        type: 'user',
        item: status.account.id
      }).then(function (res) {
        swal('Success', 'You have successfully blocked ' + status.account.acct, 'success');
      })["catch"](function (err) {
        swal('Error', 'Something went wrong. Please try again later.', 'error');
      });
    },
    statusUrl: function statusUrl(status) {
      if (status.local == true) {
        return status.url;
      }

      return '/i/web/post/_/' + status.account.id + '/' + status.id;
    },
    profileUrl: function profileUrl(status) {
      if (status.local == true) {
        return status.account.url;
      }

      return '/i/web/profile/_/' + status.account.id;
    },
    closeModal: function closeModal() {
      $('#mt_pid_' + this.status.id).modal('hide');
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader??ref--8-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--8-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "\n.o-square[data-v-ebc1967e] {\n\tmax-width: 320px;\n}\n.o-portrait[data-v-ebc1967e] {\n\tmax-width: 320px;\n}\n.o-landscape[data-v-ebc1967e] {\n\tmax-width: 320px;\n}\n.post-icon[data-v-ebc1967e] {\n\tcolor: #fff;\n\tposition:relative;\n\tmargin-top: 10px;\n\tz-index: 9;\n\topacity: 0.6;\n\ttext-shadow: 3px 3px 16px #272634;\n}\n.font-size-16px[data-v-ebc1967e] {\n\tfont-size: 16px;\n}\n.profile-website[data-v-ebc1967e] {\n\tcolor: #003569;\n\ttext-decoration: none;\n\tfont-weight: 600;\n}\n.nav-topbar .nav-link[data-v-ebc1967e] {\n\tcolor: #999;\n}\n.nav-topbar .nav-link .small[data-v-ebc1967e] {\n\tfont-weight: 600;\n}\n.has-story[data-v-ebc1967e] {\n\twidth: 84px;\n\theight: 84px;\n\tborder-radius: 50%;\n\tpadding: 4px;\n\tbackground: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);\n}\n.has-story img[data-v-ebc1967e] {\n\twidth: 76px;\n\theight: 76px;\n\tborder-radius: 50%;\n\tpadding: 6px;\n\tbackground: #fff;\n}\n.has-story-lg[data-v-ebc1967e] {\n\twidth: 159px;\n\theight: 159px;\n\tborder-radius: 50%;\n\tpadding: 4px;\n\tbackground: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);\n}\n.has-story-lg img[data-v-ebc1967e] {\n\twidth: 150px;\n\theight: 150px;\n\tborder-radius: 50%;\n\tpadding: 6px;\n\tbackground:#fff;\n}\n.no-focus[data-v-ebc1967e] {\n\tborder-color: none;\n\toutline: 0;\n\tbox-shadow: none;\n}\n.modal-tab-active[data-v-ebc1967e] {\n\tborder-bottom: 1px solid #08d;\n}\n.bg-primary[data-v-ebc1967e] {\n\tbackground: rgb(91,128,160);\n\tbackground: linear-gradient(180deg, rgba(91,128,160,1) 0%, rgba(62,100,135,1) 100%);\n}\n.bg-dark[data-v-ebc1967e] {\n\tbackground: #191919;\n}\n.font-logo[data-v-ebc1967e] {\n\tfont-family: 'Coming Soon';\n}\n.text-white[data-v-ebc1967e] {\n\tcolor: #999999;\n}\n.navbar[data-v-ebc1967e] {\n\tpadding: 0;\n}\n.nav-card[data-v-ebc1967e] {\n\theight: 53px;\n\tborder-left: 1px solid #4a6c8a;\n\tborder-right: 1px solid #4a6c8a;\n}\n.nav-card[data-v-ebc1967e]::after {\n\theight: 53px;\n\tborder-left: 1px solid #55748f;\n\tborder-right: 1px solid #55748f;\n}\n@media (min-width: 1200px) {\n.container[data-v-ebc1967e] {\n\t\tmax-width: 1030px;\n}\n}\n.btn-primary[data-v-ebc1967e] {\n\tfont-size: 13px;\n\tfont-weight: 500 !important;\n\tbackground: rgb(91,128,160);\n\tbackground: linear-gradient(180deg, rgba(91,128,160,1) 0%, rgba(62,100,135,1) 100%);\n\tborder-color: #456a8b;\n}\n.btn-lighter[data-v-ebc1967e] {\n\tfont-size: 13px;\n\tfont-weight: 500 !important;\n\tbackground: rgb(250,250,250);\n\tbackground: linear-gradient(180deg, rgba(250,250,250,1) 0%, rgba(236,236,236,1) 100%);\n\tborder-color: #cccccc;\n\tcolor: #999999;\n}\n.btn-lighter .lead[data-v-ebc1967e] {\n\tcolor: #272727;\n}\n.img-cover[data-v-ebc1967e] {\n\t-o-object-fit: cover;\n\t   object-fit: cover;\n}\n.dropdown-toggle[data-v-ebc1967e]::after {\n\tdisplay:none;\n}\n", ""]);

// exports


/***/ }),

/***/ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader??ref--8-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--8-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "\n.text-lighter[data-v-bb77b854] {\n\tcolor:#B8C2CC !important;\n}\n.modal-body[data-v-bb77b854] {\n\tpadding: 0;\n}\n", ""]);

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

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader??ref--8-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--8-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../node_modules/css-loader??ref--8-1!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--8-2!../../../../node_modules/vue-loader/lib??vue-loader-options!./MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& */ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&");

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

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader??ref--8-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--8-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../node_modules/css-loader??ref--8-1!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--8-2!../../../../node_modules/vue-loader/lib??vue-loader-options!./PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& */ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&");

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

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************/
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
    { staticClass: "w-100 h-100" },
    [
      _vm.isMobile
        ? _c("div", { staticClass: "bg-white p-3 border-bottom" }, [
            _c(
              "div",
              {
                staticClass:
                  "d-flex justify-content-between align-items-center",
              },
              [
                _c(
                  "div",
                  { staticClass: "cursor-pointer", on: { click: _vm.goBack } },
                  [_c("i", { staticClass: "fas fa-chevron-left fa-lg" })]
                ),
                _vm._v(" "),
                _c("div", { staticClass: "font-weight-bold" }, [
                  _vm._v(
                    "\n\t\t\t\t" +
                      _vm._s(this.profileUsername) +
                      "\t\t\t\t\t\t\t\t\n\n\t\t\t"
                  ),
                ]),
                _vm._v(" "),
                _c("div", [
                  _c("a", {
                    staticClass:
                      "fas fa-ellipsis-v fa-lg text-muted text-decoration-none",
                    attrs: { href: "#" },
                    on: {
                      click: function ($event) {
                        $event.preventDefault()
                        return _vm.visitorMenu.apply(null, arguments)
                      },
                    },
                  }),
                ]),
              ]
            ),
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.relationship && _vm.relationship.blocking && _vm.warning
        ? _c("div", { staticClass: "bg-white pt-3 border-bottom" }, [
            _c("div", { staticClass: "container" }, [
              _c("p", { staticClass: "text-center font-weight-bold" }, [
                _vm._v("You are blocking this account"),
              ]),
              _vm._v(" "),
              _c("p", { staticClass: "text-center font-weight-bold" }, [
                _vm._v("Click "),
                _c(
                  "a",
                  {
                    staticClass: "cursor-pointer",
                    attrs: { href: "#" },
                    on: {
                      click: function ($event) {
                        $event.preventDefault()
                        _vm.warning = false
                      },
                    },
                  },
                  [_vm._v("here")]
                ),
                _vm._v(" to view profile"),
              ]),
            ]),
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.loading
        ? _c(
            "div",
            {
              staticClass: "d-flex justify-content-center align-items-center",
              staticStyle: { height: "80vh" },
            },
            [_c("img", { attrs: { src: "/img/pixelfed-icon-grey.svg" } })]
          )
        : _vm._e(),
      _vm._v(" "),
      !_vm.loading && !_vm.warning
        ? _c("div", [
            _vm.timeline.length == 0 || _vm.timeline.length < 8
              ? _c("div", {
                  staticClass: "bg-dark",
                  staticStyle: { height: "100px" },
                })
              : _c(
                  "div",
                  { staticClass: "bg-dark", staticStyle: { height: "400px" } },
                  [
                    _c("div", { staticClass: "container" }, [
                      _c("div", { staticClass: "d-flex overflow-hidden" }, [
                        _c("div", {}, [
                          _c("img", {
                            staticClass: "border border-dark img-cover",
                            attrs: {
                              src: _vm.timelineCache[0].media_attachments[0]
                                .preview_url,
                              width: "200px",
                              height: "200px",
                            },
                          }),
                          _vm._v(" "),
                          _c("img", {
                            staticClass: "border border-dark img-cover",
                            attrs: {
                              src: _vm.timelineCache[1].media_attachments[0]
                                .preview_url,
                              width: "200px",
                              height: "200px",
                            },
                          }),
                        ]),
                        _vm._v(" "),
                        _vm.timelineCache.length > 6
                          ? _c("div", {}, [
                              _c("img", {
                                staticClass: "border border-dark img-cover",
                                attrs: {
                                  src: _vm.timelineCache[2].media_attachments[0]
                                    .preview_url,
                                  width: "400px",
                                  height: "400px",
                                },
                              }),
                            ])
                          : _vm._e(),
                        _vm._v(" "),
                        _vm.timelineCache.length > 7
                          ? _c("div", {}, [
                              _c("img", {
                                staticClass: "border border-dark img-cover",
                                attrs: {
                                  src: _vm.timelineCache[3].media_attachments[0]
                                    .preview_url,
                                  width: "200px",
                                  height: "200px",
                                },
                              }),
                              _vm._v(" "),
                              _c("img", {
                                staticClass: "border border-dark img-cover",
                                attrs: {
                                  src: _vm.timelineCache[4].media_attachments[0]
                                    .preview_url,
                                  width: "200px",
                                  height: "200px",
                                },
                              }),
                            ])
                          : _vm._e(),
                        _vm._v(" "),
                        _vm.timelineCache.length > 7
                          ? _c("div", {}, [
                              _c("img", {
                                staticClass: "border border-dark img-cover",
                                attrs: {
                                  src: _vm.timelineCache[5].media_attachments[0]
                                    .preview_url,
                                  width: "200px",
                                  height: "200px",
                                },
                              }),
                              _vm._v(" "),
                              _c("img", {
                                staticClass: "border border-dark img-cover",
                                attrs: {
                                  src: _vm.timelineCache[6].media_attachments[0]
                                    .preview_url,
                                  width: "200px",
                                  height: "200px",
                                },
                              }),
                            ])
                          : _vm._e(),
                      ]),
                    ]),
                  ]
                ),
            _vm._v(" "),
            _c("div", { staticClass: "container" }, [
              _c(
                "div",
                {
                  staticClass:
                    "d-flex justify-content-between align-items-center pt-2 px-2",
                  staticStyle: {
                    width: "100%",
                    "min-height": "153px",
                    background: "#f6f6f6",
                    border: "1px solid #d9d9d9",
                    "z-index": "99999",
                  },
                },
                [
                  _c("div", {}, [
                    _c(
                      "div",
                      {
                        staticClass: "d-inline-block",
                        staticStyle: { width: "8rem", "margin-top": "-70px" },
                      },
                      [
                        _c(
                          "div",
                          { staticClass: "border rounded mb-3 bg-white p-1" },
                          [
                            _c("img", {
                              staticClass: "img-fluid rounded",
                              attrs: { src: _vm.profile.avatar },
                            }),
                          ]
                        ),
                        _vm._v(" "),
                        _vm.owner == true
                          ? _c(
                              "a",
                              {
                                staticClass:
                                  "btn btn-primary btn-block font-weight-bold p-1",
                                attrs: { href: "/settings/home" },
                              },
                              [_vm._v("Edit Profile")]
                            )
                          : _vm.relationship.following == true
                          ? _c(
                              "button",
                              {
                                staticClass:
                                  "btn btn-primary btn-block font-weight-bold p-1",
                                attrs: { type: "button" },
                                on: {
                                  click: function ($event) {
                                    $event.preventDefault()
                                    return _vm.followProfile()
                                  },
                                },
                              },
                              [_vm._v("Unfollow")]
                            )
                          : _c(
                              "button",
                              {
                                staticClass:
                                  "btn btn-primary btn-block font-weight-bold p-1",
                                attrs: { type: "button" },
                                on: {
                                  click: function ($event) {
                                    $event.preventDefault()
                                    return _vm.followProfile()
                                  },
                                },
                              },
                              [_vm._v("Follow")]
                            ),
                      ]
                    ),
                    _vm._v(" "),
                    _vm.profile.local
                      ? _c(
                          "div",
                          {
                            staticClass: "d-inline-block ml-3",
                            staticStyle: { "max-width": "400px" },
                          },
                          [
                            _c(
                              "p",
                              { staticClass: "h3 font-weight-bold mb-1" },
                              [_vm._v("@" + _vm._s(_vm.profile.username))]
                            ),
                            _vm._v(" "),
                            _c("p", { staticClass: "mb-0" }, [
                              _c("span", { staticClass: "font-weight-bold" }, [
                                _vm._v(_vm._s(_vm.profile.display_name)),
                              ]),
                            ]),
                            _vm._v(" "),
                            _c("p", { staticClass: "text-muted small mb-0" }, [
                              _vm._v(_vm._s(_vm.profile.note)),
                            ]),
                            _vm._v(" "),
                            _c("p", { staticClass: "mb-0" }, [
                              _c(
                                "a",
                                {
                                  staticClass: "font-weight-bold small",
                                  staticStyle: { color: "rgba(62,100,135,1)" },
                                  attrs: { href: _vm.profile.website },
                                  on: {
                                    click: function ($event) {
                                      $event.preventDefault()
                                      return _vm.remoteRedirect(
                                        _vm.profile.website
                                      )
                                    },
                                  },
                                },
                                [
                                  _vm._v(
                                    _vm._s(
                                      _vm.truncate(_vm.profile.website, 40)
                                    )
                                  ),
                                ]
                              ),
                            ]),
                          ]
                        )
                      : _c(
                          "div",
                          {
                            staticClass: "d-inline-block ml-3",
                            staticStyle: { "max-width": "400px" },
                          },
                          [
                            _c(
                              "p",
                              { staticClass: "h3 font-weight-bold mb-1" },
                              [_vm._v(_vm._s(_vm.profile.username))]
                            ),
                            _vm._v(" "),
                            _c("p", { staticClass: "mb-2" }, [
                              _c("span", { staticClass: "font-weight-bold" }, [
                                _vm._v(_vm._s(_vm.profile.display_name)),
                              ]),
                            ]),
                            _vm._v(" "),
                            _vm._m(0),
                          ]
                        ),
                  ]),
                  _vm._v(" "),
                  _c("div", { staticClass: "d-md-none" }, [
                    _c(
                      "div",
                      {
                        staticClass:
                          "d-block btn btn-lighter btn-sm px-3 py-1 border mb-1",
                        on: {
                          click: function ($event) {
                            return _vm.followingModal()
                          },
                        },
                      },
                      [
                        _c("p", { staticClass: "mb-0 lead font-weight-bold" }, [
                          _vm._v(_vm._s(_vm.profile.following_count)),
                        ]),
                        _vm._v(" "),
                        _c(
                          "p",
                          {
                            staticClass:
                              "mb-0 mt-n1 text-muted font-weight-bold text-uppercase small",
                          },
                          [_vm._v("following")]
                        ),
                      ]
                    ),
                    _vm._v(" "),
                    _c(
                      "div",
                      {
                        staticClass:
                          "d-block btn btn-lighter btn-sm px-3 py-1 border",
                        on: {
                          click: function ($event) {
                            return _vm.followersModal()
                          },
                        },
                      },
                      [
                        _c("p", { staticClass: "mb-0 lead font-weight-bold" }, [
                          _vm._v(_vm._s(_vm.profile.followers_count)),
                        ]),
                        _vm._v(" "),
                        _c(
                          "p",
                          {
                            staticClass:
                              "mb-0 mt-n1 text-muted font-weight-bold text-uppercase small",
                          },
                          [_vm._v("followers")]
                        ),
                      ]
                    ),
                  ]),
                  _vm._v(" "),
                  _c("div", { staticClass: "d-none d-md-block" }, [
                    _c("div", { staticClass: "btn-group" }, [
                      _c(
                        "div",
                        { staticClass: "btn btn-lighter px-4 py-2 border" },
                        [
                          _c(
                            "p",
                            { staticClass: "mb-0 lead font-weight-bold" },
                            [_vm._v(_vm._s(_vm.profile.statuses_count))]
                          ),
                          _vm._v(" "),
                          _c(
                            "p",
                            {
                              staticClass:
                                "mb-0 mt-n1 text-muted font-weight-bold text-uppercase small",
                            },
                            [_vm._v("posts")]
                          ),
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "div",
                        {
                          staticClass: "btn btn-lighter px-4 py-2 border",
                          on: {
                            click: function ($event) {
                              return _vm.followingModal()
                            },
                          },
                        },
                        [
                          _c(
                            "p",
                            { staticClass: "mb-0 lead font-weight-bold" },
                            [_vm._v(_vm._s(_vm.profile.following_count))]
                          ),
                          _vm._v(" "),
                          _c(
                            "p",
                            {
                              staticClass:
                                "mb-0 mt-n1 text-muted font-weight-bold text-uppercase small",
                            },
                            [_vm._v("following")]
                          ),
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "div",
                        {
                          staticClass: "btn btn-lighter px-4 py-2 border",
                          on: {
                            click: function ($event) {
                              return _vm.followersModal()
                            },
                          },
                        },
                        [
                          _c(
                            "p",
                            { staticClass: "mb-0 lead font-weight-bold" },
                            [_vm._v(_vm._s(_vm.profile.followers_count))]
                          ),
                          _vm._v(" "),
                          _c(
                            "p",
                            {
                              staticClass:
                                "mb-0 mt-n1 text-muted font-weight-bold text-uppercase small",
                            },
                            [_vm._v("followers")]
                          ),
                        ]
                      ),
                    ]),
                  ]),
                ]
              ),
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "container" }, [
              _c(
                "nav",
                {
                  staticClass: "navbar navbar-dark bg-primary",
                  staticStyle: { "border-color": "#456a8b" },
                },
                [
                  _c(
                    "div",
                    {
                      staticClass:
                        "container d-flex justify-content-center align-items-center",
                    },
                    [
                      _c(
                        "div",
                        {
                          staticClass: "nav-card px-3",
                          staticStyle: { "padding-top": "14px" },
                        },
                        [
                          _c(
                            "a",
                            {
                              class: [
                                _vm.page == "home"
                                  ? "d-md-none font-weight-bold text-white mx-2 small"
                                  : "d-md-none font-weight-bold text-lighter mx-2 small",
                              ],
                              attrs: { href: _vm.pageUrl() },
                              on: {
                                click: function ($event) {
                                  $event.preventDefault()
                                  return _vm.navigateToPage("home")
                                },
                              },
                            },
                            [_c("i", { staticClass: "fas fa-th" })]
                          ),
                          _vm._v(" "),
                          _c(
                            "a",
                            {
                              class: [
                                _vm.page == "home"
                                  ? "d-none d-md-inline font-weight-bold text-white mx-2 small"
                                  : "d-none d-md-inline font-weight-bold text-lighter mx-2 small",
                              ],
                              attrs: { href: _vm.pageUrl() },
                              on: {
                                click: function ($event) {
                                  $event.preventDefault()
                                  return _vm.navigateToPage("home")
                                },
                              },
                            },
                            [_vm._v("\n\t\t\t\t\t\t\tPOSTS\n\t\t\t\t\t\t")]
                          ),
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "div",
                        {
                          staticClass: "nav-card px-3",
                          staticStyle: { "padding-top": "14px" },
                        },
                        [
                          _c(
                            "a",
                            {
                              class: [
                                _vm.page == "collections"
                                  ? "d-md-none font-weight-bold text-white mx-2 small"
                                  : "d-md-none font-weight-bold text-lighter mx-2 small",
                              ],
                              attrs: { href: _vm.pageUrl("collections") },
                              on: {
                                click: function ($event) {
                                  $event.preventDefault()
                                  return _vm.navigateToPage("collections")
                                },
                              },
                            },
                            [_c("i", { staticClass: "fas fa-images" })]
                          ),
                          _vm._v(" "),
                          _c(
                            "a",
                            {
                              class: [
                                _vm.page == "collections"
                                  ? "d-none d-md-inline font-weight-bold text-white mx-2 small"
                                  : "d-none d-md-inline font-weight-bold text-lighter mx-2 small",
                              ],
                              attrs: { href: _vm.pageUrl("collections") },
                              on: {
                                click: function ($event) {
                                  $event.preventDefault()
                                  return _vm.navigateToPage("collections")
                                },
                              },
                            },
                            [
                              _vm._v(
                                "\n\t\t\t\t\t\t\tCOLLECTIONS\n\t\t\t\t\t\t"
                              ),
                            ]
                          ),
                        ]
                      ),
                      _vm._v(" "),
                      _vm.owner == true
                        ? _c(
                            "div",
                            {
                              staticClass: "nav-card px-3",
                              staticStyle: { "padding-top": "14px" },
                            },
                            [
                              _c(
                                "a",
                                {
                                  class: [
                                    _vm.page == "saved"
                                      ? "d-md-none font-weight-bold text-white mx-2 small"
                                      : "d-md-none font-weight-bold text-lighter mx-2 small",
                                  ],
                                  attrs: { href: _vm.pageUrl("saved") },
                                  on: {
                                    click: function ($event) {
                                      $event.preventDefault()
                                      return _vm.navigateToPage("saved")
                                    },
                                  },
                                },
                                [_c("i", { staticClass: "fas fa-bookmark" })]
                              ),
                              _vm._v(" "),
                              _c(
                                "a",
                                {
                                  class: [
                                    _vm.page == "saved"
                                      ? "d-none d-md-inline font-weight-bold text-white mx-2 small"
                                      : "d-none d-md-inline font-weight-bold text-lighter mx-2 small",
                                  ],
                                  attrs: { href: _vm.pageUrl("saved") },
                                  on: {
                                    click: function ($event) {
                                      $event.preventDefault()
                                      return _vm.navigateToPage("saved")
                                    },
                                  },
                                },
                                [_vm._v("\n\t\t\t\t\t\t\tSAVED\n\t\t\t\t\t\t")]
                              ),
                            ]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _vm.owner == true
                        ? _c(
                            "div",
                            {
                              staticClass: "nav-card px-3",
                              staticStyle: { "padding-top": "14px" },
                            },
                            [
                              _c(
                                "a",
                                {
                                  class: [
                                    _vm.page == "likes"
                                      ? "d-md-none font-weight-bold text-white mx-2 small"
                                      : "d-md-none font-weight-bold text-lighter mx-2 small",
                                  ],
                                  attrs: { href: _vm.pageUrl("likes") },
                                  on: {
                                    click: function ($event) {
                                      $event.preventDefault()
                                      return _vm.navigateToPage("likes")
                                    },
                                  },
                                },
                                [_c("i", { staticClass: "fas fa-heart" })]
                              ),
                              _vm._v(" "),
                              _c(
                                "a",
                                {
                                  class: [
                                    _vm.page == "likes"
                                      ? "d-none d-md-inline font-weight-bold text-white mx-2 small"
                                      : "d-none d-md-inline font-weight-bold text-lighter mx-2 small",
                                  ],
                                  attrs: { href: _vm.pageUrl("likes") },
                                  on: {
                                    click: function ($event) {
                                      $event.preventDefault()
                                      return _vm.navigateToPage("likes")
                                    },
                                  },
                                },
                                [_vm._v("\n\t\t\t\t\t\t\tLIKES\n\t\t\t\t\t\t")]
                              ),
                            ]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _c(
                        "div",
                        {
                          staticClass: "nav-card px-3",
                          staticStyle: { "padding-top": "14px" },
                        },
                        [
                          _c(
                            "span",
                            {
                              staticClass: "mx-2 text-lighter cursor-pointer",
                              on: {
                                click: function ($event) {
                                  return _vm.visitorMenu()
                                },
                              },
                            },
                            [_c("i", { staticClass: "fas fa-cog" })]
                          ),
                        ]
                      ),
                    ]
                  ),
                ]
              ),
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "container" }, [
              _c(
                "div",
                {
                  staticClass: "border overflow-hidden",
                  staticStyle: { background: "#eaeaea", "min-height": "40vh" },
                },
                [
                  _vm.page == "collections"
                    ? _c(
                        "div",
                        { staticClass: "row pl-2 pr-3 mt-3" },
                        [
                          _vm._m(1),
                          _vm._v(" "),
                          _vm._l(_vm.collections, function (post, index) {
                            return _vm.collections.length
                              ? _c(
                                  "div",
                                  {
                                    key: "cmtl-" + post.id,
                                    staticClass: "col-12 col-md-3 mb-3 mx-0",
                                  },
                                  [
                                    _c(
                                      "a",
                                      {
                                        staticClass: "text-decoration-none",
                                        attrs: { href: post.url },
                                      },
                                      [
                                        _c("img", {
                                          staticClass:
                                            "bg-white p-2 rounded border img-cover",
                                          attrs: {
                                            src: post.thumb,
                                            width: "100%",
                                            height: "230px",
                                            onerror:
                                              "this.onerror=null;this.src='/storage/no-preview.png';",
                                          },
                                        }),
                                        _vm._v(" "),
                                        _c(
                                          "div",
                                          {
                                            staticClass:
                                              "d-flex justify-content-between px-1 small text-muted font-weight-bold pt-1",
                                          },
                                          [
                                            _c(
                                              "span",
                                              {
                                                staticClass:
                                                  "text-truncate mr-3",
                                              },
                                              [_vm._v(_vm._s(post.title))]
                                            ),
                                            _vm._v(" "),
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(
                                                  _vm.timeago(post.published_at)
                                                )
                                              ),
                                            ]),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ]
                                )
                              : _vm._e()
                          }),
                        ],
                        2
                      )
                    : _vm.owner && _vm.page == "saved"
                    ? _c(
                        "div",
                        { staticClass: "row pl-2 pr-3 mt-3" },
                        [
                          _vm._m(2),
                          _vm._v(" "),
                          _vm._l(_vm.bookmarks, function (post, index) {
                            return _vm.bookmarks.length
                              ? _c(
                                  "div",
                                  {
                                    key: "cmtl-" + post.id,
                                    staticClass: "col-12 col-md-3 mb-3 mx-0",
                                  },
                                  [
                                    _c(
                                      "a",
                                      {
                                        staticClass: "text-decoration-none",
                                        attrs: { href: post.url },
                                      },
                                      [
                                        _c("img", {
                                          staticClass:
                                            "bg-white p-2 rounded border img-cover",
                                          attrs: {
                                            src: post.media_attachments[0]
                                              .preview_url,
                                            width: "100%",
                                            height: "230px",
                                            onerror:
                                              "this.onerror=null;this.src='/storage/no-preview.png';",
                                          },
                                        }),
                                        _vm._v(" "),
                                        _c(
                                          "div",
                                          {
                                            staticClass:
                                              "d-flex justify-content-between px-1 small text-muted font-weight-bold pt-1",
                                          },
                                          [
                                            _c("span", {
                                              staticClass: "text-truncate mr-3",
                                            }),
                                            _vm._v(" "),
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(
                                                  _vm.timeago(post.created_at)
                                                )
                                              ),
                                            ]),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ]
                                )
                              : _vm._e()
                          }),
                        ],
                        2
                      )
                    : _vm.page == "places"
                    ? _c("div", { staticClass: "row pl-2 pr-3 mt-3" }, [
                        _vm._m(3),
                      ])
                    : _vm.page == "likes"
                    ? _c(
                        "div",
                        { staticClass: "row pl-2 pr-3 mt-3" },
                        [
                          _vm._l(_vm.likesTimeline, function (lpost, index) {
                            return _vm.likesTimeline.length
                              ? _c(
                                  "div",
                                  {
                                    key: "pmltl-" + lpost.id,
                                    staticClass: "col-12 col-md-3 mb-3 mx-0",
                                  },
                                  [
                                    _c(
                                      "a",
                                      {
                                        staticClass: "text-decoration-none",
                                        attrs: { href: lpost.url },
                                      },
                                      [
                                        _c("img", {
                                          staticClass:
                                            "bg-white p-2 rounded border img-cover",
                                          attrs: {
                                            src: lpost.media_attachments[0]
                                              .preview_url,
                                            width: "100%",
                                            height: "230px",
                                            onerror:
                                              "this.onerror=null;this.src='/storage/no-preview.png';",
                                          },
                                        }),
                                        _vm._v(" "),
                                        _c(
                                          "div",
                                          {
                                            staticClass:
                                              "d-flex justify-content-between px-1 small text-muted font-weight-bold pt-1",
                                          },
                                          [
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(lpost.favourites_count) +
                                                  " " +
                                                  _vm._s(
                                                    lpost.favourites_count == 1
                                                      ? "like"
                                                      : "likes"
                                                  )
                                              ),
                                            ]),
                                            _vm._v(" "),
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(
                                                  _vm.timeago(lpost.created_at)
                                                )
                                              ),
                                            ]),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ]
                                )
                              : _vm._e()
                          }),
                          _vm._v(" "),
                          _vm.likesTimeline.length
                            ? _c(
                                "div",
                                { staticClass: "col-12" },
                                [
                                  _c(
                                    "infinite-loading",
                                    { on: { infinite: _vm.infiniteLikes } },
                                    [
                                      _c("div", {
                                        attrs: { slot: "no-more" },
                                        slot: "no-more",
                                      }),
                                      _vm._v(" "),
                                      _c("div", {
                                        attrs: { slot: "no-results" },
                                        slot: "no-results",
                                      }),
                                    ]
                                  ),
                                ],
                                1
                              )
                            : _c(
                                "div",
                                { staticClass: "col-12 mt-5 pt-5 mx-0" },
                                [
                                  _vm._m(4),
                                  _vm._v(" "),
                                  _c(
                                    "p",
                                    {
                                      staticClass:
                                        "lead text-center mb-0 font-weight-bold text-muted",
                                    },
                                    [_vm._v("No liked posts found")]
                                  ),
                                ]
                              ),
                        ],
                        2
                      )
                    : _c(
                        "div",
                        { staticClass: "row pl-2 pr-3 mt-3" },
                        [
                          _vm._l(_vm.timeline, function (post, index) {
                            return _vm.timeline.length
                              ? _c(
                                  "div",
                                  {
                                    key: "pmtl-" + post.id,
                                    staticClass: "col-12 col-md-3 mb-3 mx-0",
                                  },
                                  [
                                    _c(
                                      "a",
                                      {
                                        staticClass: "text-decoration-none",
                                        attrs: { href: post.url },
                                      },
                                      [
                                        _c("img", {
                                          staticClass:
                                            "bg-white p-2 rounded border img-cover",
                                          attrs: {
                                            src: post.media_attachments[0]
                                              .preview_url,
                                            width: "100%",
                                            height: "230px",
                                            onerror:
                                              "this.onerror=null;this.src='/storage/no-preview.png';",
                                          },
                                        }),
                                        _vm._v(" "),
                                        _c(
                                          "div",
                                          {
                                            staticClass:
                                              "d-flex justify-content-between px-1 small text-muted font-weight-bold pt-1",
                                          },
                                          [
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(post.favourites_count) +
                                                  " " +
                                                  _vm._s(
                                                    post.favourites_count == 1
                                                      ? "like"
                                                      : "likes"
                                                  )
                                              ),
                                            ]),
                                            _vm._v(" "),
                                            _c("span", [
                                              _vm._v(
                                                _vm._s(
                                                  _vm.timeago(post.created_at)
                                                )
                                              ),
                                            ]),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ]
                                )
                              : _vm._e()
                          }),
                          _vm._v(" "),
                          _vm.timeline.length
                            ? _c(
                                "div",
                                { staticClass: "col-12" },
                                [
                                  _c(
                                    "infinite-loading",
                                    { on: { infinite: _vm.infiniteTimeline } },
                                    [
                                      _c("div", {
                                        attrs: { slot: "no-more" },
                                        slot: "no-more",
                                      }),
                                      _vm._v(" "),
                                      _c("div", {
                                        attrs: { slot: "no-results" },
                                        slot: "no-results",
                                      }),
                                    ]
                                  ),
                                ],
                                1
                              )
                            : _c(
                                "div",
                                { staticClass: "col-12 mt-5 pt-5 mx-0" },
                                [
                                  _vm._m(5),
                                  _vm._v(" "),
                                  _c(
                                    "p",
                                    {
                                      staticClass:
                                        "lead text-center mb-0 font-weight-bold text-muted",
                                    },
                                    [
                                      _vm._v(
                                        "\n\t\t\t\t\t\t\t\tNo posts found\n\t\t\t\t\t\t\t"
                                      ),
                                    ]
                                  ),
                                ]
                              ),
                        ],
                        2
                      ),
                ]
              ),
            ]),
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.profile && _vm.following
        ? _c(
            "b-modal",
            {
              ref: "followingModal",
              attrs: {
                id: "following-modal",
                "hide-footer": "",
                centered: "",
                scrollable: "",
                title: "Following",
                "body-class": "list-group-flush py-3 px-0",
                "dialog-class": "follow-modal",
              },
            },
            [
              !_vm.loading
                ? _c(
                    "div",
                    {
                      staticClass: "list-group",
                      staticStyle: { "min-height": "60vh" },
                    },
                    [
                      _vm.owner == true
                        ? _c(
                            "div",
                            {
                              staticClass:
                                "list-group-item border-0 pt-0 px-0 mt-n2 mb-3",
                            },
                            [
                              _c(
                                "span",
                                {
                                  staticClass:
                                    "d-flex px-4 pb-0 align-items-center",
                                },
                                [
                                  _c("i", {
                                    staticClass: "fas fa-search text-lighter",
                                  }),
                                  _vm._v(" "),
                                  _c("input", {
                                    directives: [
                                      {
                                        name: "model",
                                        rawName: "v-model",
                                        value: _vm.followingModalSearch,
                                        expression: "followingModalSearch",
                                      },
                                    ],
                                    staticClass:
                                      "form-control border-0 shadow-0 no-focus",
                                    attrs: {
                                      type: "text",
                                      placeholder: "Search Following...",
                                    },
                                    domProps: {
                                      value: _vm.followingModalSearch,
                                    },
                                    on: {
                                      keyup: _vm.followingModalSearchHandler,
                                      input: function ($event) {
                                        if ($event.target.composing) {
                                          return
                                        }
                                        _vm.followingModalSearch =
                                          $event.target.value
                                      },
                                    },
                                  }),
                                ]
                              ),
                            ]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _vm.owner == true
                        ? _c("div", {
                            staticClass:
                              "btn-group rounded-0 mt-n3 mb-3 border-top",
                            attrs: { role: "group", "aria-label": "Following" },
                          })
                        : _c("div", {
                            staticClass: "btn-group rounded-0 mt-n3 mb-3",
                            attrs: { role: "group", "aria-label": "Following" },
                          }),
                      _vm._v(" "),
                      _vm._l(_vm.following, function (user, index) {
                        return _c(
                          "div",
                          {
                            key: "following_" + index,
                            staticClass: "list-group-item border-0 py-1",
                          },
                          [
                            _c("div", { staticClass: "media" }, [
                              _c("a", { attrs: { href: user.url } }, [
                                _c("img", {
                                  staticClass: "mr-3 rounded-circle box-shadow",
                                  attrs: {
                                    src: user.avatar,
                                    alt: user.username + "’s avatar",
                                    width: "30px",
                                    loading: "lazy",
                                  },
                                }),
                              ]),
                              _vm._v(" "),
                              _c(
                                "div",
                                { staticClass: "media-body text-truncate" },
                                [
                                  _c(
                                    "p",
                                    {
                                      staticClass: "mb-0",
                                      staticStyle: { "font-size": "14px" },
                                    },
                                    [
                                      _c(
                                        "a",
                                        {
                                          staticClass:
                                            "font-weight-bold text-dark",
                                          attrs: { href: user.url },
                                        },
                                        [
                                          _vm._v(
                                            "\n\t\t\t\t\t\t\t\t" +
                                              _vm._s(user.username) +
                                              "\n\t\t\t\t\t\t\t"
                                          ),
                                        ]
                                      ),
                                    ]
                                  ),
                                  _vm._v(" "),
                                  !user.local
                                    ? _c(
                                        "p",
                                        {
                                          staticClass:
                                            "text-muted mb-0 text-truncate mr-3",
                                          staticStyle: { "font-size": "14px" },
                                          attrs: {
                                            title: user.acct,
                                            "data-toggle": "dropdown",
                                            "data-placement": "bottom",
                                          },
                                        },
                                        [
                                          _c(
                                            "span",
                                            { staticClass: "font-weight-bold" },
                                            [
                                              _vm._v(
                                                _vm._s(user.acct.split("@")[0])
                                              ),
                                            ]
                                          ),
                                          _c(
                                            "span",
                                            { staticClass: "text-lighter" },
                                            [
                                              _vm._v(
                                                "@" +
                                                  _vm._s(
                                                    user.acct.split("@")[1]
                                                  )
                                              ),
                                            ]
                                          ),
                                        ]
                                      )
                                    : _c(
                                        "p",
                                        {
                                          staticClass:
                                            "text-muted mb-0 text-truncate",
                                          staticStyle: { "font-size": "14px" },
                                        },
                                        [
                                          _vm._v(
                                            "\n\t\t\t\t\t\t\t" +
                                              _vm._s(user.display_name) +
                                              "\n\t\t\t\t\t\t"
                                          ),
                                        ]
                                      ),
                                ]
                              ),
                              _vm._v(" "),
                              _vm.owner
                                ? _c("div", [
                                    _c(
                                      "a",
                                      {
                                        staticClass:
                                          "btn btn-outline-dark btn-sm font-weight-bold",
                                        attrs: { href: "#" },
                                        on: {
                                          click: function ($event) {
                                            $event.preventDefault()
                                            return _vm.followModalAction(
                                              user.id,
                                              index,
                                              "following"
                                            )
                                          },
                                        },
                                      },
                                      [_vm._v("Following")]
                                    ),
                                  ])
                                : _vm._e(),
                            ]),
                          ]
                        )
                      }),
                      _vm._v(" "),
                      _vm.followingModalSearch && _vm.following.length == 0
                        ? _c(
                            "div",
                            { staticClass: "list-group-item border-0" },
                            [
                              _c(
                                "div",
                                {
                                  staticClass: "list-group-item border-0 pt-5",
                                },
                                [
                                  _c(
                                    "p",
                                    {
                                      staticClass: "p-3 text-center mb-0 lead",
                                    },
                                    [_vm._v("No Results Found")]
                                  ),
                                ]
                              ),
                            ]
                          )
                        : _vm._e(),
                      _vm._v(" "),
                      _vm.following.length > 0 && _vm.followingMore
                        ? _c(
                            "div",
                            {
                              staticClass: "list-group-item text-center",
                              on: {
                                click: function ($event) {
                                  return _vm.followingLoadMore()
                                },
                              },
                            },
                            [
                              _c(
                                "p",
                                {
                                  staticClass:
                                    "mb-0 small text-muted font-weight-light cursor-pointer",
                                },
                                [_vm._v("Load more")]
                              ),
                            ]
                          )
                        : _vm._e(),
                    ],
                    2
                  )
                : _vm._e(),
            ]
          )
        : _vm._e(),
      _vm._v(" "),
      _c(
        "b-modal",
        {
          ref: "followerModal",
          attrs: {
            id: "follower-modal",
            "hide-footer": "",
            centered: "",
            scrollable: "",
            title: "Followers",
            "body-class": "list-group-flush py-3 px-0",
            "dialog-class": "follow-modal",
          },
        },
        [
          _c(
            "div",
            { staticClass: "list-group" },
            [
              _vm.followers.length == 0
                ? _c("div", { staticClass: "list-group-item border-0" }, [
                    _c(
                      "p",
                      {
                        staticClass:
                          "text-center mb-0 font-weight-bold text-muted py-5",
                      },
                      [
                        _c("span", { staticClass: "text-dark" }, [
                          _vm._v(_vm._s(_vm.profileUsername)),
                        ]),
                        _vm._v(" has no followers yet"),
                      ]
                    ),
                  ])
                : _vm._e(),
              _vm._v(" "),
              _vm._l(_vm.followers, function (user, index) {
                return _c(
                  "div",
                  {
                    key: "follower_" + index,
                    staticClass: "list-group-item border-0 py-1",
                  },
                  [
                    _c("div", { staticClass: "media mb-0" }, [
                      _c("a", { attrs: { href: user.url } }, [
                        _c("img", {
                          staticClass: "mr-3 rounded-circle box-shadow",
                          attrs: {
                            src: user.avatar,
                            alt: user.username + "’s avatar",
                            width: "30px",
                            height: "30px",
                            loading: "lazy",
                          },
                        }),
                      ]),
                      _vm._v(" "),
                      _c("div", { staticClass: "media-body mb-0" }, [
                        _c(
                          "p",
                          {
                            staticClass: "mb-0",
                            staticStyle: { "font-size": "14px" },
                          },
                          [
                            _c(
                              "a",
                              {
                                staticClass: "font-weight-bold text-dark",
                                attrs: { href: user.url },
                              },
                              [
                                _vm._v(
                                  "\n\t\t\t\t\t\t\t\t" +
                                    _vm._s(user.username) +
                                    "\n\t\t\t\t\t\t\t"
                                ),
                              ]
                            ),
                          ]
                        ),
                        _vm._v(" "),
                        _c(
                          "p",
                          {
                            staticClass: "text-secondary mb-0",
                            staticStyle: { "font-size": "13px" },
                          },
                          [
                            _vm._v(
                              "\n\t\t\t\t\t\t\t" +
                                _vm._s(user.display_name) +
                                "\n\t\t\t\t\t\t"
                            ),
                          ]
                        ),
                      ]),
                    ]),
                  ]
                )
              }),
              _vm._v(" "),
              _vm.followers.length && _vm.followerMore
                ? _c(
                    "div",
                    {
                      staticClass: "list-group-item text-center",
                      on: {
                        click: function ($event) {
                          return _vm.followersLoadMore()
                        },
                      },
                    },
                    [
                      _c(
                        "p",
                        {
                          staticClass:
                            "mb-0 small text-muted font-weight-light cursor-pointer",
                        },
                        [_vm._v("Load more")]
                      ),
                    ]
                  )
                : _vm._e(),
            ],
            2
          ),
        ]
      ),
      _vm._v(" "),
      _c(
        "b-modal",
        {
          ref: "visitorContextMenu",
          attrs: {
            id: "visitor-context-menu",
            "hide-footer": "",
            "hide-header": "",
            centered: "",
            size: "sm",
            "body-class": "list-group-flush p-0",
          },
        },
        [
          _vm.relationship
            ? _c("div", { staticClass: "list-group" }, [
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item cursor-pointer text-center rounded text-dark",
                    on: { click: _vm.copyProfileLink },
                  },
                  [_vm._v("\n\t\t\t\tCopy Link\n\t\t\t")]
                ),
                _vm._v(" "),
                _vm.profile.locked == false
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: { click: _vm.showEmbedProfileModal },
                      },
                      [_vm._v("\n\t\t\t\tEmbed\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && !_vm.relationship.following
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: { click: _vm.followProfile },
                      },
                      [_vm._v("\n\t\t\t\tFollow\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && _vm.relationship.following
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded",
                        on: { click: _vm.followProfile },
                      },
                      [_vm._v("\n\t\t\t\tUnfollow\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && !_vm.relationship.muting
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded",
                        on: { click: _vm.muteProfile },
                      },
                      [_vm._v("\n\t\t\t\tMute\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && _vm.relationship.muting
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded",
                        on: { click: _vm.unmuteProfile },
                      },
                      [_vm._v("\n\t\t\t\tUnmute\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: { click: _vm.reportProfile },
                      },
                      [_vm._v("\n\t\t\t\tReport User\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && !_vm.relationship.blocking
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: { click: _vm.blockProfile },
                      },
                      [_vm._v("\n\t\t\t\tBlock\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && !_vm.owner && _vm.relationship.blocking
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: { click: _vm.unblockProfile },
                      },
                      [_vm._v("\n\t\t\t\tUnblock\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.user && _vm.owner
                  ? _c(
                      "div",
                      {
                        staticClass:
                          "list-group-item cursor-pointer text-center rounded text-dark",
                        on: {
                          click: function ($event) {
                            return _vm.redirect("/settings/home")
                          },
                        },
                      },
                      [_vm._v("\n\t\t\t\tSettings\n\t\t\t")]
                    )
                  : _vm._e(),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item cursor-pointer text-center rounded text-dark",
                    on: {
                      click: function ($event) {
                        return _vm.redirect(
                          "/users/" + _vm.profileUsername + ".atom"
                        )
                      },
                    },
                  },
                  [_vm._v("\n\t\t\t\tAtom Feed\n\t\t\t")]
                ),
                _vm._v(" "),
                _c(
                  "div",
                  {
                    staticClass:
                      "list-group-item cursor-pointer text-center rounded text-muted font-weight-bold",
                    on: {
                      click: function ($event) {
                        return _vm.$refs.visitorContextMenu.hide()
                      },
                    },
                  },
                  [_vm._v("\n\t\t\t\tClose\n\t\t\t")]
                ),
              ])
            : _vm._e(),
        ]
      ),
      _vm._v(" "),
      _c(
        "b-modal",
        {
          ref: "sponsorModal",
          attrs: {
            id: "sponsor-modal",
            "hide-footer": "",
            title: "Sponsor " + _vm.profileUsername,
            centered: "",
            size: "md",
            "body-class": "px-5",
          },
        },
        [
          _c("div", [
            _c("p", { staticClass: "font-weight-bold" }, [
              _vm._v("External Links"),
            ]),
            _vm._v(" "),
            _vm.sponsorList.patreon
              ? _c("p", { staticClass: "pt-2" }, [
                  _c(
                    "a",
                    {
                      staticClass: "font-weight-bold",
                      attrs: {
                        href: "https://" + _vm.sponsorList.patreon,
                        rel: "nofollow",
                      },
                    },
                    [_vm._v(_vm._s(_vm.sponsorList.patreon))]
                  ),
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.sponsorList.liberapay
              ? _c("p", { staticClass: "pt-2" }, [
                  _c(
                    "a",
                    {
                      staticClass: "font-weight-bold",
                      attrs: {
                        href: "https://" + _vm.sponsorList.liberapay,
                        rel: "nofollow",
                      },
                    },
                    [_vm._v(_vm._s(_vm.sponsorList.liberapay))]
                  ),
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.sponsorList.opencollective
              ? _c("p", { staticClass: "pt-2" }, [
                  _c(
                    "a",
                    {
                      staticClass: "font-weight-bold",
                      attrs: {
                        href: "https://" + _vm.sponsorList.opencollective,
                        rel: "nofollow",
                      },
                    },
                    [_vm._v(_vm._s(_vm.sponsorList.opencollective))]
                  ),
                ])
              : _vm._e(),
          ]),
        ]
      ),
      _vm._v(" "),
      _c(
        "b-modal",
        {
          ref: "embedModal",
          attrs: {
            id: "ctx-embed-modal",
            "hide-header": "",
            "hide-footer": "",
            centered: "",
            rounded: "",
            size: "md",
            "body-class": "p-2 rounded",
          },
        },
        [
          _c("div", [
            _c("textarea", {
              directives: [
                {
                  name: "model",
                  rawName: "v-model",
                  value: _vm.ctxEmbedPayload,
                  expression: "ctxEmbedPayload",
                },
              ],
              staticClass: "form-control disabled text-monospace",
              staticStyle: {
                "overflow-y": "hidden",
                border: "1px solid #efefef",
                "font-size": "12px",
                "line-height": "18px",
                margin: "0 0 7px",
                resize: "none",
              },
              attrs: { rows: "6", disabled: "" },
              domProps: { value: _vm.ctxEmbedPayload },
              on: {
                input: function ($event) {
                  if ($event.target.composing) {
                    return
                  }
                  _vm.ctxEmbedPayload = $event.target.value
                },
              },
            }),
            _vm._v(" "),
            _c("hr"),
            _vm._v(" "),
            _c(
              "button",
              {
                class: _vm.copiedEmbed
                  ? "btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed"
                  : "btn btn-primary btn-block btn-sm py-1 font-weight-bold",
                attrs: { disabled: _vm.copiedEmbed },
                on: { click: _vm.ctxCopyEmbed },
              },
              [
                _vm._v(
                  _vm._s(
                    _vm.copiedEmbed ? "Embed Code Copied!" : "Copy Embed Code"
                  )
                ),
              ]
            ),
            _vm._v(" "),
            _c("p", { staticClass: "mb-0 px-2 small text-muted" }, [
              _vm._v("By using this embed, you agree to our "),
              _c("a", { attrs: { href: "/site/terms" } }, [
                _vm._v("Terms of Use"),
              ]),
            ]),
          ]),
        ]
      ),
    ],
    1
  )
}
var staticRenderFns = [
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0 small" }, [
      _c(
        "span",
        {
          staticClass: "border rounded",
          staticStyle: {
            "padding-top": "3px",
            "padding-bottom": "3px",
            "border-color": "rgba(62,100,135,1) !important",
          },
        },
        [
          _c(
            "span",
            {
              staticClass: "font-weight-bold rounded px-2",
              staticStyle: { color: "rgba(62,100,135,1)" },
            },
            [_vm._v("REMOTE")]
          ),
        ]
      ),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("div", { staticClass: "d-none col-12 mt-5 pt-5 mx-0" }, [
      _c("p", { staticClass: "lead text-center text-lighter" }, [
        _c("i", { staticClass: "fas fa-images fa-4x" }),
      ]),
      _vm._v(" "),
      _c(
        "p",
        { staticClass: "lead text-center mb-0 font-weight-bold text-muted" },
        [_vm._v("No collections found")]
      ),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("div", { staticClass: "d-none col-12 mt-5 pt-5 mx-0" }, [
      _c("p", { staticClass: "lead text-center text-lighter" }, [
        _c("i", { staticClass: "far fa-bookmark fa-4x" }),
      ]),
      _vm._v(" "),
      _c(
        "p",
        { staticClass: "lead text-center mb-0 font-weight-bold text-muted" },
        [_vm._v("No saved posts found")]
      ),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("div", { staticClass: "col-12 mt-5 pt-5 mx-0" }, [
      _c("p", { staticClass: "lead text-center text-lighter" }, [
        _c("i", { staticClass: "fas fa-map-marker-alt fa-4x" }),
      ]),
      _vm._v(" "),
      _c(
        "p",
        { staticClass: "lead text-center mb-0 font-weight-bold text-muted" },
        [_vm._v("No tagged places found")]
      ),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "lead text-center text-lighter" }, [
      _c("i", { staticClass: "far fa-heart fa-4x" }),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "lead text-center text-lighter" }, [
      _c("i", { staticClass: "far fa-image fa-4x" }),
    ])
  },
]
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************/
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
  return _c("div", [
    _vm.modal != "true"
      ? _c("div", { staticClass: "dropdown" }, [
          _c(
            "button",
            {
              staticClass:
                "btn btn-link text-dark no-caret dropdown-toggle py-0",
              attrs: {
                type: "button",
                "data-toggle": "dropdown",
                "aria-haspopup": "true",
                "aria-expanded": "false",
                title: "Post options",
              },
            },
            [
              _c("span", {
                class: [
                  _vm.size == "lg"
                    ? "fas fa-ellipsis-v fa-lg text-muted"
                    : "fas fa-ellipsis-v fa-sm text-lighter",
                ],
              }),
            ]
          ),
          _vm._v(" "),
          _c("div", { staticClass: "dropdown-menu dropdown-menu-right" }, [
            _c(
              "a",
              {
                staticClass:
                  "dropdown-item font-weight-bold text-decoration-none",
                attrs: { href: _vm.status.url },
              },
              [_vm._v("Go to post")]
            ),
            _vm._v(" "),
            _vm.activeSession == true && _vm.statusOwner(_vm.status) == false
              ? _c("span", [
                  _c(
                    "a",
                    {
                      staticClass: "dropdown-item font-weight-bold",
                      attrs: { href: _vm.reportUrl(_vm.status) },
                    },
                    [_vm._v("Report")]
                  ),
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.activeSession == true && _vm.statusOwner(_vm.status) == true
              ? _c("span", [
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          $event.preventDefault()
                          return _vm.muteProfile(_vm.status)
                        },
                      },
                    },
                    [_vm._v("Mute Profile")]
                  ),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          $event.preventDefault()
                          return _vm.blockProfile(_vm.status)
                        },
                      },
                    },
                    [_vm._v("Block Profile")]
                  ),
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.activeSession == true && _vm.profile.is_admin == true
              ? _c("span", [
                  _c("div", { staticClass: "dropdown-divider" }),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-danger text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.deletePost(_vm.status)
                        },
                      },
                    },
                    [_vm._v("Delete")]
                  ),
                  _vm._v(" "),
                  _c("div", { staticClass: "dropdown-divider" }),
                  _vm._v(" "),
                  _c("h6", { staticClass: "dropdown-header" }, [
                    _vm._v("Mod Tools"),
                  ]),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.moderatePost(_vm.status, "autocw")
                        },
                      },
                    },
                    [
                      _c("p", { staticClass: "mb-0" }, [_vm._v("Enforce CW")]),
                      _vm._v(" "),
                      _vm._m(0),
                    ]
                  ),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.moderatePost(_vm.status, "noautolink")
                        },
                      },
                    },
                    [
                      _c("p", { staticClass: "mb-0" }, [
                        _vm._v("No Autolinking"),
                      ]),
                      _vm._v(" "),
                      _vm._m(1),
                    ]
                  ),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.moderatePost(_vm.status, "unlisted")
                        },
                      },
                    },
                    [
                      _c("p", { staticClass: "mb-0" }, [
                        _vm._v("Unlisted Posts"),
                      ]),
                      _vm._v(" "),
                      _vm._m(2),
                    ]
                  ),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.moderatePost(_vm.status, "disable")
                        },
                      },
                    },
                    [
                      _c("p", { staticClass: "mb-0" }, [
                        _vm._v("Disable Account"),
                      ]),
                      _vm._v(" "),
                      _vm._m(3),
                    ]
                  ),
                  _vm._v(" "),
                  _c(
                    "a",
                    {
                      staticClass:
                        "dropdown-item font-weight-bold text-decoration-none",
                      on: {
                        click: function ($event) {
                          return _vm.moderatePost(_vm.status, "suspend")
                        },
                      },
                    },
                    [
                      _c("p", { staticClass: "mb-0" }, [
                        _vm._v("Suspend Account"),
                      ]),
                      _vm._v(" "),
                      _vm._m(4),
                    ]
                  ),
                ])
              : _vm._e(),
          ]),
        ])
      : _vm._e(),
    _vm._v(" "),
    _vm.modal == "true"
      ? _c("div", [
          _c(
            "span",
            {
              attrs: {
                "data-toggle": "modal",
                "data-target": "#mt_pid_" + _vm.status.id,
              },
            },
            [
              _c("span", {
                class: [
                  _vm.size == "lg"
                    ? "fas fa-ellipsis-v fa-lg text-muted"
                    : "fas fa-ellipsis-v fa-sm text-lighter",
                ],
              }),
            ]
          ),
          _vm._v(" "),
          _c(
            "div",
            {
              staticClass: "modal",
              attrs: {
                tabindex: "-1",
                role: "dialog",
                id: "mt_pid_" + _vm.status.id,
              },
            },
            [
              _c(
                "div",
                {
                  staticClass: "modal-dialog modal-sm modal-dialog-centered",
                  attrs: { role: "document" },
                },
                [
                  _c("div", { staticClass: "modal-content" }, [
                    _c("div", { staticClass: "modal-body text-center" }, [
                      _c("div", { staticClass: "list-group" }, [
                        _c(
                          "a",
                          {
                            staticClass:
                              "list-group-item text-dark text-decoration-none",
                            attrs: { href: _vm.statusUrl(_vm.status) },
                          },
                          [_vm._v("Go to post")]
                        ),
                        _vm._v(" "),
                        _c(
                          "a",
                          {
                            staticClass:
                              "list-group-item text-dark text-decoration-none",
                            attrs: { href: "#" },
                            on: {
                              click: function ($event) {
                                $event.preventDefault()
                                return _vm.hidePost(_vm.status)
                              },
                            },
                          },
                          [_vm._v("Hide")]
                        ),
                        _vm._v(" "),
                        _vm.activeSession == true &&
                        !_vm.statusOwner(_vm.status)
                          ? _c(
                              "a",
                              {
                                staticClass:
                                  "list-group-item text-danger font-weight-bold text-decoration-none",
                                attrs: { href: _vm.reportUrl(_vm.status) },
                              },
                              [_vm._v("Report")]
                            )
                          : _vm._e(),
                        _vm._v(" "),
                        (_vm.activeSession == true &&
                          _vm.statusOwner(_vm.status) == true) ||
                        _vm.profile.is_admin == true
                          ? _c(
                              "div",
                              {
                                staticClass:
                                  "list-group-item text-danger font-weight-bold cursor-pointer",
                                on: {
                                  click: function ($event) {
                                    $event.preventDefault()
                                    return _vm.deletePost.apply(null, arguments)
                                  },
                                },
                              },
                              [_vm._v("Delete")]
                            )
                          : _vm._e(),
                        _vm._v(" "),
                        _c(
                          "a",
                          {
                            staticClass:
                              "list-group-item text-lighter text-decoration-none",
                            attrs: { href: "#" },
                            on: {
                              click: function ($event) {
                                $event.preventDefault()
                                return _vm.closeModal()
                              },
                            },
                          },
                          [_vm._v("Close")]
                        ),
                      ]),
                    ]),
                  ]),
                ]
              ),
            ]
          ),
        ])
      : _vm._e(),
  ])
}
var staticRenderFns = [
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0  small text-muted" }, [
      _vm._v("Adds a CW to every post "),
      _c("br"),
      _vm._v(" made by this account."),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0 small text-muted" }, [
      _vm._v("Do not transform mentions, "),
      _c("br"),
      _vm._v(" hashtags or urls into HTML."),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0 small text-muted" }, [
      _vm._v("Removes account from "),
      _c("br"),
      _vm._v(" public/network timelines."),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0 small text-muted" }, [
      _vm._v("Temporarily disable account "),
      _c("br"),
      _vm._v(" until next time user log in."),
    ])
  },
  function () {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("p", { staticClass: "mb-0 small text-muted" }, [
      _vm._v("This prevents any new interactions, "),
      _c("br"),
      _vm._v(" without deleting existing data."),
    ])
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

/***/ "./node_modules/vue-masonry-css/dist/vue-masonry.es2015.js":
/*!*****************************************************************!*\
  !*** ./node_modules/vue-masonry-css/dist/vue-masonry.es2015.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/*!
 * vue-masonry-css v1.0.3
 * https://github.com/paulcollett/vue-masonry-css
 * Released under the MIT License.
 */

// the component name `<masonry />`
// can be overridden with `Vue.use(Masonry, { name: 'the-masonry' });`
var componentName = 'masonry';

var props = {
  tag: {
    type: [String],
    default: 'div'
  },
  cols: {
    type: [Object, Number, String],
    default: 2
  },
  gutter: {
    type: [Object, Number, String],
    default: 0
  },
  css: {
    type: [Boolean],
    default: true
  },
  columnTag: {
    type: [String],
    default: 'div'
  },
  columnClass: {
    type: [String, Array, Object],
    default: function () { return []; }
  },
  columnAttr: {
    type: [Object],
    default: function () { return ({}); }
  }
};

// Get the resulting value from  `:col=` prop
// based on the window width
var breakpointValue = function (mixed, windowWidth) {
  var valueAsNum = parseInt(mixed);

  if(valueAsNum > -1) {
    return mixed;
  }else if(typeof mixed !== 'object') {
    return 0;
  }

  var matchedBreakpoint = Infinity;
  var matchedValue = mixed.default || 0;

  for(var k in mixed) {
    var breakpoint = parseInt(k);
    var breakpointValRaw = mixed[breakpoint];
    var breakpointVal = parseInt(breakpointValRaw);

    if(isNaN(breakpoint) || isNaN(breakpointVal)) {
      continue;
    }

    var isNewBreakpoint = windowWidth <= breakpoint && breakpoint < matchedBreakpoint;

    if(isNewBreakpoint) {
      matchedBreakpoint = breakpoint;
      matchedValue = breakpointValRaw;
    }
  }

  return matchedValue;
};

var component = {
  props: props,

  data: function data() {
    return {
      displayColumns: 2,
      displayGutter: 0
    }
  },

  mounted: function mounted() {
    var this$1 = this;

    this.$nextTick(function () {
      this$1.reCalculate();
    });

    // Bind resize handler to page
    if(window) {
      window.addEventListener('resize', this.reCalculate);
    }
  },

  updated: function updated() {
    var this$1 = this;

    this.$nextTick(function () {
      this$1.reCalculate();
    });
  },

  beforeDestroy: function beforeDestroy() {
    if(window) {
      window.removeEventListener('resize', this.reCalculate);
    }
  },

  methods: {
    // Recalculate how many columns to display based on window width
    // and the value of the passed `:cols=` prop
    reCalculate: function reCalculate() {
      var previousWindowWidth = this.windowWidth;

      this.windowWidth = (window ? window.innerWidth : null) || Infinity;

      // Window resize events get triggered on page height
      // change which when loading the page can result in multiple
      // needless calculations. We prevent this here.
      if(previousWindowWidth === this.windowWidth) {
        return;
      }

      this._reCalculateColumnCount(this.windowWidth);

      this._reCalculateGutterSize(this.windowWidth);
    },

    _reCalculateGutterSize: function _reCalculateGutterSize(windowWidth) {
      this.displayGutter = breakpointValue(this.gutter, windowWidth);
    },

    _reCalculateColumnCount: function _reCalculateColumnCount(windowWidth) {
      var newColumns = breakpointValue(this.cols, windowWidth);

      // Make sure we can return a valid value
      newColumns = Math.max(1, Number(newColumns) || 0);

      this.displayColumns = newColumns;
    },

    _getChildItemsInColumnsArray: function _getChildItemsInColumnsArray() {
      var this$1 = this;

      var columns = [];
      var childItems = this.$slots.default || [];

      // This component does not work with a child <transition-group /> ..yet,
      // so for now we think it may be helpful to ignore until we can find a way for support
      if(childItems.length === 1 && childItems[0].componentOptions && childItems[0].componentOptions.tag == 'transition-group') {
        childItems = childItems[0].componentOptions.children;
      }

      // Loop through child elements
      for (var i = 0, visibleItemI = 0; i < childItems.length; i++, visibleItemI++) {
        // skip Vue elements without tags, which includes
        // whitespace elements and also plain text
        if(!childItems[i].tag) {
          visibleItemI--;

          continue;
        }

        // Get the column index the child item will end up in
        var columnIndex = visibleItemI % this$1.displayColumns;

        if(!columns[columnIndex]) {
          columns[columnIndex] = [];
        }

        columns[columnIndex].push(childItems[i]);
      }

      return columns;
    }
  },

  render: function render(createElement) {
    var this$1 = this;

    var columnsContainingChildren = this._getChildItemsInColumnsArray();
    var isGutterSizeUnitless = parseInt(this.displayGutter) === this.displayGutter * 1;
    var gutterSizeWithUnit =  isGutterSizeUnitless ? ((this.displayGutter) + "px") : this.displayGutter;

    var columnStyle = {
      boxSizing: 'border-box',
      backgroundClip: 'padding-box',
      width: ((100 / this.displayColumns) + "%"),
      border: '0 solid transparent',
      borderLeftWidth: gutterSizeWithUnit
    };

    var columns = columnsContainingChildren.map(function (children, index) {
      /// Create column element and inject the children
      return createElement(this$1.columnTag, {
        key: index + '-' + columnsContainingChildren.length,
        style: this$1.css ? columnStyle : null,
        class: this$1.columnClass,
        attrs: this$1.columnAttr
      }, children); // specify child items here
    });

    var containerStyle = {
      display: ['-webkit-box', '-ms-flexbox', 'flex'],
      marginLeft: ("-" + gutterSizeWithUnit)
    };

    // Return wrapper with columns
    return createElement(
      this.tag, // tag name
      this.css ? { style: containerStyle } : null, // element options
      columns // column vue elements
    );
  }
};

var Plugin = function () {};

Plugin.install = function (Vue, options) {
  if (Plugin.installed) {
    return;
  }

  if(options && options.name) {
    Vue.component(options.name, component);
  } else {
    Vue.component(componentName, component);
  }
};

if (typeof window !== 'undefined' && window.Vue) {
  window.Vue.use(Plugin);
}

/* harmony default export */ __webpack_exports__["default"] = (Plugin);


/***/ }),

/***/ "./resources/assets/js/components/MemoryProfile.vue":
/*!**********************************************************!*\
  !*** ./resources/assets/js/components/MemoryProfile.vue ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true& */ "./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true&");
/* harmony import */ var _MemoryProfile_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MemoryProfile.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& */ "./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _MemoryProfile_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "ebc1967e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/MemoryProfile.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js&":
/*!***********************************************************************************!*\
  !*** ./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib??ref--4-0!../../../../node_modules/vue-loader/lib??vue-loader-options!./MemoryProfile.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&":
/*!*******************************************************************************************************************!*\
  !*** ./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader!../../../../node_modules/css-loader??ref--8-1!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--8-2!../../../../node_modules/vue-loader/lib??vue-loader-options!./MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=style&index=0&id=ebc1967e&scoped=true&lang=css&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_style_index_0_id_ebc1967e_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));


/***/ }),

/***/ "./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true& ***!
  \*****************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/MemoryProfile.vue?vue&type=template&id=ebc1967e&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_MemoryProfile_vue_vue_type_template_id_ebc1967e_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/components/PostMenu.vue":
/*!*****************************************************!*\
  !*** ./resources/assets/js/components/PostMenu.vue ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PostMenu.vue?vue&type=template&id=bb77b854&scoped=true& */ "./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true&");
/* harmony import */ var _PostMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PostMenu.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& */ "./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PostMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "bb77b854",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/PostMenu.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib??ref--4-0!../../../../node_modules/vue-loader/lib??vue-loader-options!./PostMenu.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&":
/*!**************************************************************************************************************!*\
  !*** ./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& ***!
  \**************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader!../../../../node_modules/css-loader??ref--8-1!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/src??ref--8-2!../../../../node_modules/vue-loader/lib??vue-loader-options!./PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=style&index=0&id=bb77b854&scoped=true&lang=css&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_8_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_style_index_0_id_bb77b854_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));


/***/ }),

/***/ "./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true& ***!
  \************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./PostMenu.vue?vue&type=template&id=bb77b854&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/PostMenu.vue?vue&type=template&id=bb77b854&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PostMenu_vue_vue_type_template_id_bb77b854_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/memoryprofile.js":
/*!**********************************************!*\
  !*** ./resources/assets/js/memoryprofile.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

Vue.component('post-menu', __webpack_require__(/*! ./components/PostMenu.vue */ "./resources/assets/js/components/PostMenu.vue")["default"]);
Vue.component('memory-profile', __webpack_require__(/*! ./components/MemoryProfile.vue */ "./resources/assets/js/components/MemoryProfile.vue")["default"]);

/***/ }),

/***/ 30:
/*!****************************************************!*\
  !*** multi ./resources/assets/js/memoryprofile.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Users/dansup/Github/pixelfed/resources/assets/js/memoryprofile.js */"./resources/assets/js/memoryprofile.js");


/***/ })

},[[30,"/js/manifest"]]]);