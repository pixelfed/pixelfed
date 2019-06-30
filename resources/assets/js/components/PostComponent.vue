<template>
<div>
  <div v-if="!loaded" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
      <img src="/img/pixelfed-icon-grey.svg" class="">
  </div>
  <div v-if="loaded && warning" class="bg-white pt-3 border-bottom">
    <div class="container">
      <p class="text-center font-weight-bold">You are blocking this account</p>
      <p class="text-center font-weight-bold">Click <a href="#" class="cursor-pointer" @click.prevent="warning = false; fetchData()">here</a> to view this status</p>
    </div>
  </div>
  <div v-if="loaded && warning == false" class="postComponent">
    <div v-if="profileLayout == 'metro'" class="container px-0">
      <div class="card card-md-rounded-0 status-container orientation-unknown">
        <div class="row px-0 mx-0">
        <div class="d-flex d-md-none align-items-center justify-content-between card-header bg-white w-100">
          <a :href="statusProfileUrl" class="d-flex align-items-center status-username text-truncate" data-toggle="tooltip" data-placement="bottom" :title="statusUsername">
            <div class="status-avatar mr-2">
              <img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;">
            </div>
            <div class="username">
              <span class="username-link font-weight-bold text-dark">{{ statusUsername }}</span>
            </div>
          </a>
          <div v-if="user != false" class="float-right">
            <div class="post-actions">
            <div class="dropdown">
              <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
              <span class="fas fa-ellipsis-v text-muted"></span>
              </button>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                  <div v-if="!owner()">
                    <a class="dropdown-item font-weight-bold" :href="reportUrl()">Report</a>
                    <a class="dropdown-item font-weight-bold" v-on:click="muteProfile()">Mute Profile</a>
                    <a class="dropdown-item font-weight-bold" v-on:click="blockProfile()">Block Profile</a>
                  </div>
                  <div v-if="ownerOrAdmin()">
                    <a class="dropdown-item font-weight-bold" href="#" v-on:click.prevent="toggleCommentVisibility">{{ showComments ? 'Disable' : 'Enable'}} Comments</a>
                    <a class="dropdown-item font-weight-bold" :href="editUrl()">Edit</a>
                    <a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost(status)">Delete</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
         </div>
          <div class="col-12 col-md-8 px-0 mx-0">
              <div class="postPresenterContainer d-none d-flex justify-content-center align-items-center" v-on:dblclick="likeStatus">
                <div v-if="status.pf_type === 'photo'" class="w-100">
                  <photo-presenter :status="status" v-on:lightbox="lightbox"></photo-presenter>
                </div>

                <div v-else-if="status.pf_type === 'video'" class="w-100">
                  <video-presenter :status="status"></video-presenter>
                </div>

                <div v-else-if="status.pf_type === 'photo:album'" class="w-100">
                  <photo-album-presenter :status="status" v-on:lightbox="lightbox"></photo-album-presenter>
                </div>

                <div v-else-if="status.pf_type === 'video:album'" class="w-100">
                  <video-album-presenter :status="status"></video-album-presenter>
                </div>

                <div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
                  <mixed-album-presenter :status="status" v-on:lightbox="lightbox"></mixed-album-presenter>
                </div>

                <div v-else class="w-100">
                  <p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
                </div>
              </div>
          </div>

          <div class="col-12 col-md-4 px-0 d-flex flex-column border-left border-md-left-0">
            <div class="d-md-flex d-none align-items-center justify-content-between card-header py-3 bg-white">
              <a :href="statusProfileUrl" class="d-flex align-items-center status-username text-truncate" data-toggle="tooltip" data-placement="bottom" :title="statusUsername">
                <div class="status-avatar mr-2">
                  <img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;">
                </div>
                <div class="username">
                  <span class="username-link font-weight-bold text-dark">{{ statusUsername }}</span>
                </div>
              </a>
                <div class="float-right">
                  <div class="post-actions">
                  <div v-if="user != false" class="dropdown">
                    <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
                    <span class="fas fa-ellipsis-v text-muted"></span>
                    </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                          <span v-if="!owner()">
                            <a class="dropdown-item font-weight-bold" :href="reportUrl()">Report</a>
                            <a class="dropdown-item font-weight-bold" v-on:click="muteProfile">Mute Profile</a>
                            <a class="dropdown-item font-weight-bold" v-on:click="blockProfile">Block Profile</a>
                          </span>
                          <span v-if="ownerOrAdmin()">
                            <a class="dropdown-item font-weight-bold" href="#" v-on:click.prevent="toggleCommentVisibility">{{ showComments ? 'Disable' : 'Enable'}} Comments</a>
                            <a class="dropdown-item font-weight-bold" :href="editUrl()">Edit</a>
                            <a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost">Delete</a>
                          </span>
                        </div>
                    </div>
                  </div>
                </div>
            </div>
            <div class="d-flex flex-md-column flex-column-reverse h-100" style="overflow-y: auto;">
              <div class="card-body status-comments pb-5">
                <div class="status-comment">
                  <p :class="[status.content.length > 420 ? 'mb-1 read-more' : 'mb-1']" style="overflow: hidden;">
                    <span class="font-weight-bold pr-1">{{statusUsername}}</span>
                    <span class="comment-text" :id="status.id + '-status-readmore'" v-html="status.content"></span>
                  </p>

                  <div v-if="showComments">
                    <hr>
                    <div class="postCommentsLoader text-center">
                      <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                      </div>
                    </div>
                    <div class="postCommentsContainer d-none">
                      <p v-if="status.reply_count > 10"class="mb-1 text-center load-more-link d-none"><a href="#" class="text-muted" v-on:click="loadMore">Load more comments</a></p>
                      <div class="comments">
                        <div v-for="(reply, index) in results" class="pb-3" :key="'tl' + reply.id + '_' + index">
                          <div v-if="reply.sensitive == true">
                            <div class="card card-body shadow-none border border-left-blue py-3 px-1 text-center small">
                              <p class="mb-0">This comment may contain sensitive material</p>
                              <p class="font-weight-bold text-primary cursor-pointer mb-0" @click="reply.sensitive = false;">Show</p>
                            </div>
                          </div>
                          <div v-else>
                            <p class="d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;">
                              <span>
                                <a class="text-dark font-weight-bold mr-1" :href="reply.account.url" v-bind:title="reply.account.username">{{truncate(reply.account.username,15)}}</a>
                                <span class="text-break" v-html="reply.content"></span>
                              </span>
                              <span class="pl-2" style="min-width:38px">
                                <span v-on:click="likeReply(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
                                  <post-menu :status="reply" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block pl-2" v-on:deletePost="deleteComment(reply.id, index)"></post-menu>
                              </span>
                            </p>
                            <p class="">
                              <a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(reply.created_at)" :href="reply.url"></a>
                              <span v-if="reply.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{reply.favourites_count == 1 ? '1 like' : reply.favourites_count + ' likes'}}</span>
                              <span class="text-muted comment-reaction font-weight-bold cursor-pointer" v-on:click="replyFocus(reply, index)">Reply</span>
                            </p>
                            <div v-if="reply.reply_count > 0" class="cursor-pointer" style="margin-left:30px;" v-on:click="toggleReplies(reply)">
                               <span class="show-reply-bar"></span>
                               <span class="comment-reaction font-weight-bold text-muted">{{reply.thread ? 'Hide' : 'View'}} Replies ({{reply.reply_count}})</span>
                            </div>
                            <div v-if="reply.thread == true" class="comment-thread">
                              <div v-for="(s, sindex) in reply.replies" class="pb-3" :key="'cr' + s.id + '_' + index">
                                <p class="d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;">
                                  <span>
                                    <a class="text-dark font-weight-bold mr-1" :href="s.account.url" :title="s.account.username">{{s.account.username}}</a>
                                    <span class="text-break" v-html="s.content"></span>
                                  </span>
                                  <span class="pl-2" style="min-width:38px">
                                    <span v-on:click="likeReply(s, $event)"><i v-bind:class="[s.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
                                      <post-menu :status="s" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block pl-2" v-on:deletePost="deleteCommentReply(s.id, sindex, index) "></post-menu>
                                  </span>
                                </p>
                                <p class="">
                                  <a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(s.created_at)" :href="s.url"></a>
                                  <span v-if="s.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{s.favourites_count == 1 ? '1 like' : s.favourites_count + ' likes'}}</span>
                                </p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
              <div class="card-body flex-grow-0 py-1">
                <div class="reactions my-1">
                  <h3 v-bind:class="[reactions.liked ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus"></h3>
                  <h3 v-if="!status.comments_disabled" class="far fa-comment pr-3 m-0 cursor-pointer" title="Comment" v-on:click="replyFocus(status)"></h3>
                  <h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.shared ? 'far fa-share-square pr-3 m-0 text-primary cursor-pointer' : 'far fa-share-square pr-3 m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus"></h3>
                  <h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.bookmarked ? 'fas fa-bookmark text-warning m-0 float-right cursor-pointer' : 'far fa-bookmark m-0 float-right cursor-pointer']" title="Bookmark" v-on:click="bookmarkStatus"></h3>
                </div>
                <div class="reaction-counts font-weight-bold mb-0">
                  <span style="cursor:pointer;" v-on:click="likesModal">
                    <span class="like-count">{{status.favourites_count || 0}}</span> likes
                  </span>
                  <span v-if="status.visibility == 'public'" class="float-right" style="cursor:pointer;" v-on:click="sharesModal">
                    <span class="share-count pl-4">{{status.reblogs_count || 0}}</span> shares
                  </span>
                </div>
                <div class="timestamp pt-2 d-flex align-items-bottom justify-content-between">
                  <a v-bind:href="statusUrl" class="small text-muted">
                    {{timestampFormat()}}
                  </a>
                  <span class="small text-muted text-capitalize cursor-pointer" v-on:click="visibilityModal">{{status.visibility}}</span>
                </div>
              </div>
            </div>
            <div v-if="showComments && user.length !== 0" class="card-footer bg-white px-2 py-0">
              <ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
                <li class="nav-item" v-on:click="emojiReaction">😂</li>
                <li class="nav-item" v-on:click="emojiReaction">💯</li>
                <li class="nav-item" v-on:click="emojiReaction">❤️</li>
                <li class="nav-item" v-on:click="emojiReaction">🙌</li>
                <li class="nav-item" v-on:click="emojiReaction">👏</li>
                <li class="nav-item" v-on:click="emojiReaction">👌</li>
                <li class="nav-item" v-on:click="emojiReaction">😍</li>
                <li class="nav-item" v-on:click="emojiReaction">😯</li>
                <li class="nav-item" v-on:click="emojiReaction">😢</li>
                <li class="nav-item" v-on:click="emojiReaction">😅</li>
                <li class="nav-item" v-on:click="emojiReaction">😁</li>
                <li class="nav-item" v-on:click="emojiReaction">🙂</li>
                <li class="nav-item" v-on:click="emojiReaction">😎</li>
                <li class="nav-item" v-on:click="emojiReaction" v-for="e in emoji">{{e}}</li>
              </ul>
            </div>
            <div v-if="showComments" class="card-footer bg-white sticky-md-bottom p-0">
              <div v-if="user.length == 0" class="comment-form-guest p-3">
                <a href="/login">Login</a> to like or comment.
              </div>
              <form v-else class="border-0 rounded-0 align-middle" method="post" action="/i/comment" :data-id="statusId" data-truncate="false">
                <textarea class="form-control border-0 rounded-0" name="comment" placeholder="Add a comment…" autocomplete="off" autocorrect="off" style="height:56px;line-height: 18px;max-height:80px;resize: none; padding-right:4.2rem;" v-model="replyText"></textarea>
                <input type="button" value="Post" class="d-inline-block btn btn-link font-weight-bold reply-btn text-decoration-none" v-on:click.prevent="postReply"/>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div v-if="profileLayout == 'moment'" class="momentui">
      <div class="bg-dark mt-md-n4">
        <div class="container" v-on:dblclick="likeStatus">
              <div class="postPresenterContainer d-none d-flex justify-content-center align-items-center bg-dark">
                <div v-if="status.pf_type === 'photo'" class="w-100">
                  <photo-presenter :status="status" v-on:lightbox="lightbox"></photo-presenter>
                </div>

                <div v-else-if="status.pf_type === 'video'" class="w-100">
                  <video-presenter :status="status"></video-presenter>
                </div>

                <div v-else-if="status.pf_type === 'photo:album'" class="w-100">
                  <photo-album-presenter :status="status" v-on:lightbox="lightbox"></photo-album-presenter>
                </div>

                <div v-else-if="status.pf_type === 'video:album'" class="w-100">
                  <video-album-presenter :status="status"></video-album-presenter>
                </div>

                <div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
                  <mixed-album-presenter :status="status" v-on:lightbox="lightbox"></mixed-album-presenter>
                </div>

                <div v-else class="w-100">
                  <p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
                </div>
              </div>
        </div>
      </div>
      <div class="bg-white">
        <div class="container">
          <div class="row py-5">
            <div class="col-12 col-md-8">
              <div class="reactions py-2">
                <h3 v-bind:class="[reactions.liked ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus"></h3>
                <h3 v-if="!status.comments_disabled" class="far fa-comment pr-3 m-0 cursor-pointer" title="Comment" v-on:click="replyFocus(status)"></h3>
                <h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.shared ? 'far fa-share-square pr-3 m-0 text-primary float-right cursor-pointer' : 'far fa-share-square pr-3 m-0 share-btn float-right cursor-pointer']" title="Share" v-on:click="shareStatus"></h3>
              </div>
                <div class="reaction-counts font-weight-bold mb-0">
                  <span style="cursor:pointer;" v-on:click="likesModal">
                    <span class="like-count">{{status.favourites_count || 0}}</span> likes
                  </span>
                  <span v-if="status.visibility == 'public'" class="float-right" style="cursor:pointer;" v-on:click="sharesModal">
                    <span class="share-count pl-4">{{status.reblogs_count || 0}}</span> shares
                  </span>
                </div>
              <hr>
              <div class="media align-items-center">
                <img :src="statusAvatar" class="rounded-circle shadow-lg mr-3" alt="avatar" width="72px" height="72px">
                <div class="media-body lead">
                  by <a :href="statusProfileUrl">{{statusUsername}}</a>
                </div>
              </div>
              <hr>
              <div>
                <p class="lead"><i class="far fa-clock"></i> {{timestampFormat()}}</p>
                <div class="lead" v-html="status.content"></div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div v-if="status.comments_disabled" class="bg-light p-5 text-center lead">
                <p class="mb-0">Comments have been disabled on this post.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <b-modal ref="likesModal"
    id="l-modal"
    hide-footer
    centered
    title="Likes"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in likes" :key="'modal_likes_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
              </a>
            </p>
          </div>
        </div>
      </div>
      <infinite-loading @infinite="infiniteLikesHandler" spinner="spiral">
        <div slot="no-more"></div>
        <div slot="no-results"></div>
      </infinite-loading>
    </div>
  </b-modal>
  <b-modal ref="sharesModal"
    id="s-modal"
    hide-footer
    centered
    title="Shares"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in shares" :key="'modal_shares_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <div class="d-inline-block">
              <p class="mb-0" style="font-size: 14px">
                <a :href="user.url" class="font-weight-bold text-dark">
                  {{user.username}}
                </a>
              </p>
              <p class="text-muted mb-0" style="font-size: 14px">
                  {{user.display_name}}
                </a>
              </p>
            </div>
            <p class="float-right"><!-- <a class="btn btn-primary font-weight-bold py-1" href="#">Follow</a> --></p>
          </div>
        </div>
      </div>
      <infinite-loading @infinite="infiniteSharesHandler" spinner="spiral">
        <div slot="no-more"></div>
        <div slot="no-results"></div>
      </infinite-loading>
    </div>
  </b-modal>
  <b-modal
    id="lightbox"
    ref="lightboxModal"
    :hide-header="true"
    :hide-footer="true"
    centered
    size="lg"
    body-class="p-0"
    >
    <div v-if="lightboxMedia" :class="lightboxMedia.filter_class">
      <img :src="lightboxMedia.url" class="img-fluid" style="min-height: 100%; min-width: 100%">
    </div>
  </b-modal>
</div>
</template>

<style type="text/css" scoped>
  .status-comments,
  .reactions {
    background: #fff;
  }
  .postPresenterContainer {
    background: #fff;
  }
  @media(min-width: 720px) {  
    .postPresenterContainer { 
      min-height: 600px;  
    } 
  }
  ::-webkit-scrollbar {
      width: 0px;
      background: transparent;
  }
  .reply-btn {
    position: absolute;
    bottom: 12px;
    right: 20px;
    width: 60px;
    text-align: center;
    border-radius: 0 3px 3px 0;
  }
  .text-lighter {
    color:#B8C2CC !important;
  }
  .text-break {
    overflow-wrap: break-word;
  }
  .comments p {
    margin-bottom: 0;
  }
  .comment-reaction {
    font-size: 80%;
  }
  .show-reply-bar {
    display: inline-block;
    border-bottom: 1px solid #999;
    height: 0;
    margin-right: 16px;
    vertical-align: middle;
    width: 24px;
  }
  .comment-thread {
    margin: 4px 0 0 40px;
    width: calc(100% - 40px);
  }
  .emoji-reactions .nav-item {
    font-size: 1.2rem;
    padding: 9px;
    cursor: pointer;
  }
  .emoji-reactions::-webkit-scrollbar {
    width: 0px;
    height: 0px;
    background: transparent;
  }
</style>
<style type="text/css" scoped>
  .momentui .bg-dark {
    background: #000 !important;
  }
  .momentui .carousel.slide,
  .momentui .carousel-item {
    background: #000 !important;
  }
</style>

<script>

pixelfed.postComponent = {};

export default {
    props: [
      'status-id',
      'status-username',
      'status-template',
      'status-url',
      'status-profile-url',
      'status-avatar',
      'status-profile-id',
      'profile-layout'
    ],
    data() {
        return {
            status: false,
            media: {},
            user: false,
            reactions: {
              liked: false,
              shared: false
            },
            likes: [],
            likesPage: 1,
            shares: [],
            sharesPage: 1,
            lightboxMedia: false,
            replyText: '',
            relationship: {},
            results: [],
            pagination: {},
            min_id: 0,
            max_id: 0,
            reply_to_profile_id: 0,
            thread: false,
            showComments: false,
            warning: false,
            loaded: false,
            loading: null,
            replyingToId: this.statusId,
            replyToIndex: 0,
            emoji: ['😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥'],
          }
    },

    beforeMount() {
      let u = new URLSearchParams(window.location.search);
      if(u.has('ui') && u.get('ui') == 'moment' && this.profileLayout != 'moment') {
        this.profileLayout = 'moment';
      }
      if(u.has('ui') && u.get('ui') == 'metro' && this.profileLayout != 'metro') {
        this.profileLayout = 'metro';
      }
    },

    mounted() {
      this.fetchRelationships();
    },

    updated() {
      $('.carousel').carousel();
      window.pixelfed.readmore();
    },

    methods: {
      showMuteBlock() {
        let sid = this.status.account.id;
        let uid = this.user.id;
        if(sid == uid) {
          $('.post-actions .menu-author').removeClass('d-none');
        } else {
          $('.post-actions .menu-user').removeClass('d-none');
        }
      },

      reportUrl() {
        return '/i/report?type=post&id=' + this.status.id;
      },

      editUrl() {
        return this.status.url + '/edit';
      },

      timestampFormat() {
          let ts = new Date(this.status.created_at);
          return ts.toDateString() + ' ' + ts.toLocaleTimeString();
      },

      fetchData() {
          axios.get('/api/v2/profile/'+this.statusUsername+'/status/'+this.statusId)
            .then(response => {
                let self = this;
                self.status = response.data.status;
                self.user = response.data.user;
                self.media = self.status.media_attachments;
                self.reactions = response.data.reactions;
                self.likes = response.data.likes;
                self.shares = response.data.shares;
                self.likesPage = 2;
                self.sharesPage = 2;
                this.showMuteBlock();
                if(self.status.comments_disabled == false) {
                  self.showComments = true;
                  this.fetchComments();
                }
                this.loaded = true;
                $('head title').text(this.status.account.username + ' posted a photo: ' + this.status.favourites_count + ' likes');
            }).catch(error => {
              if(!error.response) {
              } else {
                switch(error.response.status) {
                  case 401:
                  break;
                  default:
                  break;
                }
              }
            });
      },

      likesModal() {
        if(this.status.favourites_count == 0 || $('body').hasClass('loggedIn') == false) {
          return;
        }
        this.$refs.likesModal.show();
      },

      sharesModal() {
        if(this.status.reblogs_count == 0 || $('body').hasClass('loggedIn') == false) {
          return;
        }
        this.$refs.sharesModal.show();
      },

      infiniteLikesHandler($state) {
        let api = '/api/v2/likes/profile/'+this.statusUsername+'/status/'+this.statusId;
        axios.get(api, {
          params: {
            page: this.likesPage,
          },
        }).then(({ data }) => {
          if (data.data.length > 0) {
            this.likes.push(...data.data);
            this.likesPage++;
            $state.loaded();
          } else {
            $state.complete();
          }
        });
      },

      infiniteSharesHandler($state) {
        axios.get('/api/v2/shares/profile/'+this.statusUsername+'/status/'+this.statusId, {
          params: {
            page: this.sharesPage,
          },
        }).then(({ data }) => {
          if (data.data.length > 0) {
            this.shares.push(...data.data);
            this.sharesPage++;
            $state.loaded();
          } else {
            $state.complete();
          }
        });
      },

      likeStatus(event) {
        if($('body').hasClass('loggedIn') == false) {
          window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
          return;
        }

        axios.post('/i/like', {
          item: this.status.id
        }).then(res => {
          this.status.favourites_count = res.data.count;
          if(this.reactions.liked == true) {
            this.reactions.liked = false;
            let user = this.user.id;
            this.likes = this.likes.filter(function(like) {
              return like.id !== user;
            });
          } else {
            this.reactions.liked = true;
            let user = this.user;
            this.likes.push(user);
          }
        }).catch(err => {
          console.error(err);
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      shareStatus() {
        if($('body').hasClass('loggedIn') == false) {
          window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
          return;
        }

        axios.post('/i/share', {
          item: this.status.id
        }).then(res => {
          this.status.reblogs_count = res.data.count;
          if(this.reactions.shared == true) {
            this.reactions.shared = false;
            let user = this.user.id;
            this.shares = this.shares.filter(function(reaction) {
              return reaction.id !== user;
            });
          } else {
            this.reactions.shared = true;
            let user = this.user;
            this.shares.push(user);
          }
        }).catch(err => {
          console.error(err);
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      bookmarkStatus() {
        if($('body').hasClass('loggedIn') == false) {
          window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
          return;
        }

        axios.post('/i/bookmark', {
          item: this.status.id
        }).then(res => {
          if(this.reactions.bookmarked == true) {
            this.reactions.bookmarked = false;
          } else {
            this.reactions.bookmarked = true;
          }
        }).catch(err => {
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      muteProfile() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/mute', {
          type: 'user',
          item: this.status.account.id
        }).then(res => {
          swal('Success', 'You have successfully muted ' + this.status.account.acct, 'success');
        }).catch(err => {
          swal('Error', 'Something went wrong. Please try again later.', 'error');
        });
      },

      blockProfile() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/block', {
          type: 'user',
          item: this.status.account.id
        }).then(res => {
          swal('Success', 'You have successfully blocked ' + this.status.account.acct, 'success');
        }).catch(err => {
          swal('Error', 'Something went wrong. Please try again later.', 'error');
        });
      },

      deletePost(status) {
        if(!this.ownerOrAdmin()) {
          return;
        }
        var result = confirm('Are you sure you want to delete this post?');
        if (result) {
            if($('body').hasClass('loggedIn') == false) {
            return;
            }
            axios.post('/i/delete', {
              type: 'status',
              item: this.status.id
            }).then(res => {
              swal('Success', 'You have successfully deleted this post', 'success');
              setTimeout(function() {
                window.location.href = '/';
              }, 3000);
            }).catch(err => {
              swal('Error', 'Something went wrong. Please try again later.', 'error');
            });
        }
      },

      owner() {
        return this.user.id === this.status.account.id;
      },

      admin() {
        return this.user.is_admin == true;
      },

      ownerOrAdmin() {
        return this.owner() || this.admin();
      },

      lightbox(src) {
        this.lightboxMedia = src;
        this.$refs.lightboxModal.show();
      },

      postReply() {
        let self = this;
        if(this.replyText.length == 0 ||
          this.replyText.trim() == '@'+this.status.account.acct) {
          self.replyText = null;
          $('textarea[name="comment"]').blur();
          return;
        }
        let data = {
          item: this.replyingToId,
          comment: this.replyText
        }
        axios.post('/i/comment', data)
        .then(function(res) {
          let entity = res.data.entity;
          if(entity.in_reply_to_id == self.status.id) {
            self.results.push(entity);
            let elem = $('.status-comments')[0];
            elem.scrollTop = elem.clientHeight;
          } else {
            if(self.replyToIndex >= 0) {
              let el = self.results[self.replyToIndex];
              el.replies.push(entity);
              el.reply_count = el.reply_count + 1;
            }
          }
          self.replyText = '';
        });
      },

      deleteComment(id, i) {
        axios.post('/i/delete', {
          type: 'comment',
          item: id
        }).then(res => {
          this.results.splice(i, 1);
        }).catch(err => {
          swal('Something went wrong!', 'Please try again later', 'error');
        });
      },

      deleteCommentReply(id, i, pi) {
        axios.post('/i/delete', {
          type: 'comment',
          item: id
        }).then(res => {
          this.results[pi].replies.splice(i, 1);
          --this.results[pi].reply_count;
        }).catch(err => {
          swal('Something went wrong!', 'Please try again later', 'error');
        });
      },

      l(e) {
        let len = e.length;
        if(len < 10) { return e; }
        return e.substr(0, 10)+'...';
      },

      replyFocus(e, index) {
          this.replyToIndex = index;
          this.replyingToId = e.id;
          this.reply_to_profile_id = e.account.id;
          this.replyText = '@' + e.account.username + ' ';
          $('textarea[name="comment"]').focus();
      },

      fetchComments() {
          let url = '/api/v2/comments/'+this.statusUsername+'/status/'+this.statusId;
          axios.get(url)
            .then(response => {
                let self = this;
                this.results = _.reverse(response.data.data);
                this.pagination = response.data.meta.pagination;
                if(this.results.length > 0) {
                  $('.load-more-link').removeClass('d-none');
                }
                $('.postCommentsLoader').addClass('d-none');
                $('.postCommentsContainer').removeClass('d-none');
            }).catch(error => {
              if(!error.response) {
                $('.postCommentsLoader .lds-ring')
                  .attr('style','width:100%')
                  .addClass('pt-4 font-weight-bold text-muted')
                  .text('An error occurred, cannot fetch comments. Please try again later.');
              } else {
                switch(error.response.status) {
                  case 401:
                    $('.postCommentsLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('Please login to view.');
                  break;

                  default:
                    $('.postCommentsLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('An error occurred, cannot fetch comments. Please try again later.');
                  break;
                }
              }
            });
      },

      loadMore(e) {
          e.preventDefault();
          if(this.pagination.total_pages == 1 || this.pagination.current_page == this.pagination.total_pages) {
            $('.load-more-link').addClass('d-none');
            return;
          }
          $('.postCommentsLoader').removeClass('d-none');
          let next = this.pagination.links.next;
          axios.get(next)
            .then(response => {
                let self = this;
                let res =  response.data.data;
                $('.postCommentsLoader').addClass('d-none');
                for(let i=0; i < res.length; i++) {
                  this.results.unshift(res[i]);
                }
                this.pagination = response.data.meta.pagination;
            });
      },

      likeReply(status, $event) {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/like', {
          item: status.id
        }).then(res => {
          status.favourites_count = res.data.count;
          if(status.favourited == true) {
            status.favourited = false;
          } else {
            status.favourited = true;
          }
        }).catch(err => {
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      truncate(str,lim) {
        return _.truncate(str,{
          length: lim
        });
      },

      timeAgo(ts) {
        let date = Date.parse(ts);
        let seconds = Math.floor((new Date() - date) / 1000);
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) {
          return interval + "y";
        }
        interval = Math.floor(seconds / 604800);
        if (interval >= 1) {
          return interval + "w";
        }
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) {
          return interval + "d";
        }
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) {
          return interval + "h";
        }
        interval = Math.floor(seconds / 60);
        if (interval >= 1) {
          return interval + "m";
        }
        return Math.floor(seconds) + "s";
      },

      emojiReaction() {
        let em = event.target.innerText;
        if(this.replyText.length == 0) {
          this.reply_to_profile_id = this.status.account.id;
          this.replyText = em + ' ';
          $('textarea[name="comment"]').focus();
        } else {
          this.reply_to_profile_id = this.status.account.id;
          this.replyText += em + ' ';
          $('textarea[name="comment"]').focus();
        }
      },

      toggleCommentVisibility() {
        if(this.ownerOrAdmin() == false) {
          return;
        }

        let state = this.status.comments_disabled;
        let self = this;

        if(state == true) {
          // re-enable comments
          axios.post('/i/visibility', {
            item: self.status.id,
            disableComments: false
          }).then(function(res) {
              window.location.href = self.status.url;
          }).catch(function(err) {
            return;
          });
        } else {
          // disable comments
          axios.post('/i/visibility', {
            item: self.status.id,
            disableComments: true
          }).then(function(res) {
            self.status.comments_disabled = false;
            self.showComments = false;
          }).catch(function(err) {
            return;
          });
        }
      },

      fetchRelationships() {
        if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == false) {
          this.fetchData();
          return;
        } else {
          axios.get('/api/v1/accounts/relationships', {
            params: {
              'id[]': this.statusProfileId
            }
          }).then(res => {
            if(res.data[0] == null) {
              this.fetchData();
              return;
            }
            this.relationship = res.data[0];
            if(res.data[0].blocking == true) {
              this.loaded = true;
              this.warning = true;
              return;
            } else {
              this.fetchData();
              return;
            }
          });
        }
      },

      visibilityModal() {
        switch(this.status.visibility) {
          case 'public':
            swal('Public Post', 'This post is visible to everyone.', 'info');
          break;

          case 'unlisted':
            swal('Unlisted Post', 'This post is visible on profiles and with a direct links. It is not displayed on timelines.', 'info');
          break;

          case 'private':
            swal('Private Post', 'This post is only visible to followers.', 'info');
          break;
        }
      },

      toggleReplies(reply) {
        if(reply.thread) {
          reply.thread = false;
        } else {
          if(reply.replies.length > 0) {
            reply.thread = true;
            return;
          }
          let url = '/api/v2/comments/'+reply.account.username+'/status/'+reply.id;
          axios.get(url)
            .then(response => {
                reply.replies = _.reverse(response.data.data);
                reply.thread = true;
            });
        }
      }

    },
}
</script>
