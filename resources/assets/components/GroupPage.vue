<template>
    <div class="group-feed-component">
        <div v-if="!initalLoad">
            <p class="text-center mt-5 pt-5 font-weight-bold">Loading...</p>
        </div>

        <template v-else>
            <div class="row border-bottom m-0 p-0">
                <sidebar />

                <div class="col-12 col-md-9 px-md-0">
                    <div class="bg-white mb-3 border-bottom">
                        <div class="">
                            <group-banner :group="group" />

                            <div class="col-12 group-feed-component-header px-3 px-md-5">
                                <div class="media align-items-end">
                                    <img
                                        v-if="group.metadata && group.metadata.hasOwnProperty('avatar')"
                                        :src="group.metadata.avatar.url"
                                        width="169"
                                        height="169"
                                        class="bg-white mx-4 rounded-circle border shadow p-1"
                                        style="object-fit: cover;"
                                        :style="{ 'margin-top': group.metadata && group.metadata.hasOwnProperty('header') && group.metadata.header.url ? '-100px' : '0' }">

                                    <div class="media-body">
                                        <h3 class="d-flex align-items-start">
                                            <span>{{ group.name.slice(0,118) }}</span>
                                            <sup v-if="group.verified" class="fa-stack ml-n2" title="Verified Group" data-toggle="tooltip">
                                                <i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
                                                <i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
                                            </sup>
                                        </h3>
                                        <p class="text-muted mb-0" style="font-weight: 300;">
                                            <span>
                                                <i class="fas fa-globe mr-1"></i>
                                                {{ group.membership == 'all' ? 'Public Group' : 'Private Group'}}
                                            </span>
                                            <span class="mx-2">
                                                ·
                                            </span>
                                            <span>{{ group.member_count == 1 ? group.member_count + ' Member' : group.member_count + ' Members' }}</span>
                                            <span class="mx-2">
                                                ·
                                            </span>
                                            <span v-if="group.local" class="rounded member-label">Local</span>
                                            <span v-else class="rounded remote-label">Remote</span>
                                            <span v-if="group.self && group.self.hasOwnProperty('role') && group.self.role">
                                                <span class="mx-2">
                                                    ·
                                                </span>
                                                <span class="rounded member-label">{{ group.self.role }}</span>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <button v-if="!isMember && !group.self.is_requested" class="btn btn-primary cta-btn font-weight-bold" @click="joinGroup" :disabled="requestingMembership">
                                        <span v-if="!requestingMembership">
                                            {{ group.membership == 'all' ? 'Join' : 'Request Membership' }}
                                        </span>
                                        <div
                                            v-else
                                            class="spinner-border spinner-border-sm"
                                            role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </button>

                                    <button
                                        v-else-if="!isMember && group.self.is_requested"
                                        class="btn btn-light border cta-btn font-weight-bold"
                                        @click.prevent="cancelJoinRequest">
                                        <i class="fas fa-user-clock mr-1"></i> Requested to Join
                                    </button>

                                    <button
                                        v-else-if="!isAdmin && isMember && !group.self.is_requested"
                                        type="button"
                                        class="btn btn-light border cta-btn font-weight-bold"
                                        @click.prevent="leaveGroup">
                                        <i class="fas sign-out-alt mr-1"></i> Leave Group
                                    </button>

                                    <!-- <div v-if="isAdmin">
                                        <a
                                            class="btn btn-light border cta-btn font-weight-bold"
                                            :href="group.url + '/settings'">
                                            Settings
                                        </a>
                                    </div> -->
                                </div>
                            </div>
                            <div class="col-12 border-top group-feed-component-menu px-5">
                                <ul class="nav font-weight-bold group-feed-component-menu-nav">
                                    <li class="nav-item">
                                        <a :class="{active: tab == 'about'}" class="nav-link" href="#" @click.prevent="switchTab('about')">About</a>
                                    </li>
                                    <li class="nav-item">
                                        <a :class="{active: tab == 'feed'}" class="nav-link" href="#" @click.prevent="switchTab('feed')">Feed</a>
                                    </li>
                                    <li v-if="group.self.is_member" class="nav-item">
                                        <a :class="{active: tab == 'topics'}" class="nav-link" href="#" @click.prevent="switchTab('topics')">Topics</a>
                                    </li>
                                    <li v-if="group.self.is_member" class="nav-item">
                                        <a :class="{active: tab == 'members'}" class="nav-link" href="#" @click.prevent="switchTab('members')">
                                            Members
                                            <span v-if="group.self.is_member && isAdmin && atabs.request_count" class="badge badge-danger rounded-pill ml-2" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.request_count}}</span>
                                        </a>
                                    </li>
                                    <!-- <li v-if="group.self.is_member" class="nav-item">
                                        <a :class="{active: tab == 'events'}" class="nav-link" href="#" @click.prevent="switchTab('events')">Events</a>
                                    </li> -->
                                    <li v-if="group.self.is_member" class="nav-item">
                                        <a :class="{active: tab == 'media'}" class="nav-link" href="#" @click.prevent="switchTab('media')">Media</a>
                                    </li>
                                    <!-- <li v-if="group.self.is_member" class="nav-item">
                                        <a class="nav-link" href="#">Popular</a>
                                    </li> -->
                                    <!-- <li v-if="group.self.is_member" class="nav-item">
                                        <a :class="{active: tab == 'polls'}" class="nav-link" href="#" @click.prevent="switchTab('polls')">Polls</a>
                                    </li> -->

                                    <!-- <li v-if="group.self.is_member && isAdmin" class="nav-item">
                                        <a class="nav-link" href="#">Messages</a>
                                    </li> -->

                                    <!-- <li v-if="group.self.is_member && isAdmin" class="nav-item">
                                        <a :class="{active: tab == 'insights'}" class="nav-link" href="#" @click.prevent="switchTab('insights')">Insights</a>
                                    </li> -->
                                    <!-- <li v-if="group.self.is_member && isAdmin && group.membership != 'all'" class="nav-item">
                                        <a :class="{active: tab == 'requests'}" class="nav-link" href="#" @click.prevent="switchTab('requests')">
                                            <span class="mr-2">
                                                <i class="far fa-user-plus mr-1"></i>
                                                Requests
                                            </span>
                                            <span v-if="atabs.request_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.request_count}}</span>
                                            <span v-if="atabs.request_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">99+</span>
                                        </a>
                                    </li> -->
                                    <li v-if="group.self.is_member && isAdmin" class="nav-item">
                                        <a :class="{active: tab == 'moderation'}" class="nav-link d-flex align-items-top" href="#" @click.prevent="switchTab('moderation')">
                                            <span class="mr-2">Moderation</span>
                                            <span v-if="atabs.moderation_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.moderation_count}}</span>
                                        </a>
                                    </li>
                                </ul>
                                <div>
                                    <button
                                        v-if="group.self.is_member"
                                        class="btn btn-light btn-sm border px-3 rounded-pill mr-2"
                                        @click="showSearchModal">
                                        <i class="far fa-search"></i>
                                    </button>
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-light btn-sm border px-3 rounded-pill dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="far fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" @click.prevent="copyLink">
                                                Copy Group Link
                                            </a>

                                            <a class="dropdown-item" href="#" @click.prevent="showInviteModal">
                                                Invite friends
                                            </a>

                                            <a v-if="!isAdmin" class="dropdown-item" href="#" @click.prevent="reportGroup">
                                                Report Group
                                            </a>

                                            <a v-if="isAdmin" class="dropdown-item" :href="group.url + '/settings'">
                                                Settings
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container-xl group-feed-component-body">
                        <keep-alive>
                            <div v-if="tab == 'feed'" class="row mb-5">

                                <div v-if="!permalinkMode" class="col-12 col-md-7 mt-3">
                                    <div v-if="group.self.is_member">
                                        <!-- <div class="card card-body border my-3 shadow-sm rounded-lg">
                                            <div class="media align-items-center">
                                                <img :src="profile.avatar" class="rounded-circle border mr-3" width="42px" height="42px">
                                                <div class="media-body">
                                                    <div class="reply-form form-group mb-0">
                                                        <input class="form-control form-control-lg rounded-pill bg-light border-0" placeholder="Write something..." v-model="composeText">
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-if="composeText && composeText.length > 1" class="mb-0">
                                                <button class="btn btn-primary font-weight-bold float-right mt-3" @click="newPost()">Post</button>
                                            </p>
                                        </div> -->

                                        <group-compose
                                            v-if="initalLoad"
                                            :profile="profile"
                                            :group-id="groupId"
                                            v-on:new-status="pushNewStatus" />

                                        <div v-if="feed.length == 0" class="mt-3">
                                            <div class="card card-body shadow-none border d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <p class="font-weight-bold mb-0">No posts yet!</p>
                                            </div>
                                        </div>

                                        <div v-else class="group-timeline">
                                            <p class="font-weight-bold mb-1">Recent Posts</p>


                                            <!-- <div class="status-card-component shadow-sm mb-3 rounded-lg">
                                                <div class="card shadow-none border rounded-0">
                                                    <div class="card-body pb-0">
                                                        <div class="media">
                                                            <img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar" class="rounded-circle box-shadow mr-2">

                                                            <div class="media-body">
                                                                <div class="pl-2 d-flex align-items-top">
                                                                    <div>
                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/dansup" class="username font-weight-bold text-dark text-decoration-none text-break">
                                                                                dansup
                                                                            </a>
                                                                        </p>

                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/groups/328821658771132416/329186991407239168" class="font-weight-light text-muted small">13h</a>

                                                                            <span class="text-lighter" style="padding-left: 2px; padding-right: 2px;">·</span>

                                                                            <span class="text-muted small">
                                                                                <i class="fas fa-globe"></i>
                                                                            </span>
                                                                        </p>
                                                                    </div>

                                                                    <span class="text-right" style="flex-grow: 1;">
                                                                        <button type="button" class="btn btn-link text-dark py-0">
                                                                            <span class="fas fa-ellipsis-h text-lighter"></span>

                                                                            <span class="sr-only">Post Menu</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div>
                                                                <div class="">
                                                                    <p class="pt-2 text-break" style="font-size: 15px;">
                                                                        Made some improvements!
                                                                    </p>

                                                                    <div class="my-3 row px-0 mx-0 card card-body my-0 py-0 border shadow-none">
                                                                            <img src="https://opengraph.githubassets.com/f66d0f7bf17df4a45382b83c1ffde2f25e3d700f9d87ab8c9ec2029c3a1e16b6/pixelfed/pixelfed/pull/2865" class="img-fluid">
                                                                        <div class="bg-light px-3 pt-2 pb-3">
                                                                            <p class="text-muted mb-0 small">GITHUB.COM</p>
                                                                            <p class="mb-0" style="font-size: 16px;font-weight:500;">Update LikeController, add UndoLikePipeline and federate Undo Like ac… by dansup · Pull Request #2865 · pixelfed/pixelfed</p>
                                                                            <p class="mb-0 text-muted" style="font-size:14px;line-height:15px;">…tivities</p>
                                                                        </div>
                                                                    </div>

                                                                    <div class="border-top my-0">
                                                                        <div class="d-flex justify-content-between py-2 px-4">
                                                                            <button class="btn btn-link py-0 text-decoration-none text-muted">
                                                                                <i class="far fa-heart mr-1"></i>
                                                                                Like
                                                                            </button>

                                                                            <div>
                                                                                <i class="far fa-comment cursor-pointer text-muted mr-1"></i>
                                                                                Comment
                                                                            </div>

                                                                            <div>
                                                                                <i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
                                                                                Share
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <!-- OGP -->
                                            <!-- <div class="status-card-component shadow-sm mb-3 rounded-lg">
                                                <div class="card shadow-none border rounded-0">
                                                    <div class="card-body pb-0">
                                                        <div class="media">
                                                            <img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar" class="rounded-circle box-shadow mr-2">

                                                            <div class="media-body">
                                                                <div class="pl-2 d-flex align-items-top">
                                                                    <div>
                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/dansup" class="username font-weight-bold text-dark text-decoration-none text-break">
                                                                                dansup
                                                                            </a>
                                                                        </p>

                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/groups/328821658771132416/329186991407239168" class="font-weight-light text-muted small">13h</a>

                                                                            <span class="text-lighter" style="padding-left: 2px; padding-right: 2px;">·</span>

                                                                            <span class="text-muted small">
                                                                                <i class="fas fa-globe"></i>
                                                                            </span>
                                                                        </p>
                                                                    </div>

                                                                    <span class="text-right" style="flex-grow: 1;">
                                                                        <button type="button" class="btn btn-link text-dark py-0">
                                                                            <span class="fas fa-ellipsis-h text-lighter"></span>

                                                                            <span class="sr-only">Post Menu</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div>
                                                                <div class="">
                                                                    <div class="my-3 row px-0 mx-0 card card-body my-0 py-0 border shadow-none">
                                                                            <img src="https://www.ctvnews.ca/polopoly_fs/1.5533318.1628281952!/httpImage/image.jpg_gen/derivatives/landscape_620/image.jpg" class="img-fluid">
                                                                        <div class="bg-light px-3 pt-2 pb-3">
                                                                            <p class="text-muted mb-0 small">CTVNEWS.CA</p>
                                                                            <p class="mb-0" style="font-size: 16px;font-weight:500;">No charges against Alberta man who fatally shot home intruder: RCMP</p>
                                                                            <p class="mb-0 text-muted" style="font-size:14px;line-height:15px;">No charges will be laid against an Alberta man who shot and killed an intruder after being beaten with a baseball bat, RCMP announced Friday.</p>
                                                                        </div>
                                                                    </div>

                                                                    <div class="border-top my-0">
                                                                        <div class="d-flex justify-content-between py-2 px-4">
                                                                            <button class="btn btn-link py-0 text-decoration-none text-muted">
                                                                                <i class="far fa-heart mr-1"></i>
                                                                                Like
                                                                            </button>

                                                                            <div>
                                                                                <i class="far fa-comment cursor-pointer text-muted mr-1"></i>
                                                                                Comment
                                                                            </div>

                                                                            <div>
                                                                                <i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
                                                                                Share
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <!-- SOUNDCLOUD -->
                                            <!-- <div class="status-card-component shadow-sm mb-3 rounded-lg">
                                                <div class="card shadow-none border rounded-0">
                                                    <div class="card-body pb-0">
                                                        <div class="media">
                                                            <img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar" class="rounded-circle box-shadow mr-2">

                                                            <div class="media-body">
                                                                <div class="pl-2 d-flex align-items-top">
                                                                    <div>
                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/dansup" class="username font-weight-bold text-dark text-decoration-none text-break">
                                                                                dansup
                                                                            </a>
                                                                        </p>

                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/groups/328821658771132416/329186991407239168" class="font-weight-light text-muted small">13h</a>

                                                                            <span class="text-lighter" style="padding-left: 2px; padding-right: 2px;">·</span>

                                                                            <span class="text-muted small">
                                                                                <i class="fas fa-globe"></i>
                                                                            </span>
                                                                        </p>
                                                                    </div>

                                                                    <span class="text-right" style="flex-grow: 1;">
                                                                        <button type="button" class="btn btn-link text-dark py-0">
                                                                            <span class="fas fa-ellipsis-h text-lighter"></span>

                                                                            <span class="sr-only">Post Menu</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div>
                                                                <div class="">
                                                                    <p class="pt-2 text-break" style="font-size: 15px;">
                                                                        What does everyone think??
                                                                    </p>

                                                                    <div class="my-3 row p-0 m-0">
                                                                        <iframe width="100%" height="166" scrolling="no" frameborder="no" allow="autoplay" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/34019569&color=0066cc"></iframe><div style="font-size: 10px; color: #cccccc;line-break: anywhere;word-break: normal;overflow: hidden;white-space: nowrap;text-overflow: ellipsis; font-family: Interstate,Lucida Grande,Lucida Sans Unicode,Lucida Sans,Garuda,Verdana,Tahoma,sans-serif;font-weight: 100;"><a href="https://soundcloud.com/the-bugle" title="The Bugle" target="_blank" style="color: #cccccc; text-decoration: none;">The Bugle</a> · <a href="https://soundcloud.com/the-bugle/bugle-179-playas-gon-play" title="Bugle 179 - Playas gon play" target="_blank" style="color: #cccccc; text-decoration: none;">Bugle 179 - Playas gon play</a></div>
                                                                    </div>

                                                                    <div class="border-top my-0">
                                                                        <div class="d-flex justify-content-between py-2 px-4">
                                                                            <button class="btn btn-link py-0 text-decoration-none text-muted">
                                                                                <i class="far fa-heart mr-1"></i>
                                                                                Like
                                                                            </button>

                                                                            <div>
                                                                                <i class="far fa-comment cursor-pointer text-muted mr-1"></i>
                                                                                Comment
                                                                            </div>

                                                                            <div>
                                                                                <i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
                                                                                Share
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <!-- YOUTUBE -->
                                            <!-- <div class="status-card-component shadow-sm mb-3 rounded-lg">
                                                <div class="card shadow-none border rounded-0">
                                                    <div class="card-body pb-0">
                                                        <div class="media">
                                                            <img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar" class="rounded-circle box-shadow mr-2">

                                                            <div class="media-body">
                                                                <div class="pl-2 d-flex align-items-top">
                                                                    <div>
                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/dansup" class="username font-weight-bold text-dark text-decoration-none text-break">
                                                                                dansup
                                                                            </a>
                                                                        </p>

                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/groups/328821658771132416/329186991407239168" class="font-weight-light text-muted small">13h</a>

                                                                            <span class="text-lighter" style="padding-left: 2px; padding-right: 2px;">·</span>

                                                                            <span class="text-muted small">
                                                                                <i class="fas fa-globe"></i>
                                                                            </span>
                                                                        </p>
                                                                    </div>

                                                                    <span class="text-right" style="flex-grow: 1;">
                                                                        <button type="button" class="btn btn-link text-dark py-0">
                                                                            <span class="fas fa-ellipsis-h text-lighter"></span>

                                                                            <span class="sr-only">Post Menu</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div>
                                                                <div class="">
                                                                    <p class="pt-2 text-break" style="font-size: 15px;">
                                                                        What does everyone think??
                                                                    </p>

                                                                    <div class="my-3 row p-0 m-0">
                                                                        <iframe width="100%" height="315" src="https://www.youtube.com/embed/lH78Tb0r_f8" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                                                    </div>

                                                                    <div class="border-top my-0">
                                                                        <div class="d-flex justify-content-between py-2 px-4">
                                                                            <button class="btn btn-link py-0 text-decoration-none text-muted">
                                                                                <i class="far fa-heart mr-1"></i>
                                                                                Like
                                                                            </button>

                                                                            <div>
                                                                                <i class="far fa-comment cursor-pointer text-muted mr-1"></i>
                                                                                Comment
                                                                            </div>

                                                                            <div>
                                                                                <i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
                                                                                Share
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <!-- PHOTOS -->
                                            <!-- <div class="status-card-component shadow-sm mb-3 rounded-lg">
                                                <div class="card shadow-none border rounded-0">
                                                    <div class="card-body pb-0">
                                                        <div class="media">
                                                            <img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar" class="rounded-circle box-shadow mr-2">

                                                            <div class="media-body">
                                                                <div class="pl-2 d-flex align-items-top">
                                                                    <div>
                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/dansup" class="username font-weight-bold text-dark text-decoration-none text-break">
                                                                                dansup
                                                                            </a>
                                                                        </p>

                                                                        <p class="mb-0">
                                                                            <a href="https://pixelfed.test/groups/328821658771132416/329186991407239168" class="font-weight-light text-muted small">13h</a>

                                                                            <span class="text-lighter" style="padding-left: 2px; padding-right: 2px;">·</span>

                                                                            <span class="text-muted small">
                                                                                <i class="fas fa-globe"></i>
                                                                            </span>
                                                                        </p>
                                                                    </div>

                                                                    <span class="text-right" style="flex-grow: 1;">
                                                                        <button type="button" class="btn btn-link text-dark py-0">
                                                                            <span class="fas fa-ellipsis-h text-lighter"></span>

                                                                            <span class="sr-only">Post Menu</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div>
                                                                <div class="">
                                                                    <p class="pt-2 text-break" style="font-size: 15px;">
                                                                        What does everyone think??
                                                                    </p>

                                                                    <div class="mb-1 row px-3">
                                                                        <div class="col px-0">
                                                                            <img src="https://pixelfed.test/img/sample-post.jpeg" class="img-fluid border rounded-lg">
                                                                        </div>
                                                                        <div class="col px-0">
                                                                            <img src="https://pixelfed.test/img/sample-post.jpeg" class="img-fluid border rounded-lg">
                                                                        </div>
                                                                    </div>

                                                                    <div class="mb-3 row px-3">
                                                                        <div class="col px-0">
                                                                            <img src="https://pixelfed.test/img/sample-post.jpeg" class="img-fluid border rounded-lg">
                                                                        </div>
                                                                        <div class="col px-0">
                                                                            <img src="https://pixelfed.test/img/sample-post.jpeg" class="img-fluid border rounded-lg">
                                                                        </div>
                                                                    </div>

                                                                    <div class="border-top my-0">
                                                                        <div class="d-flex justify-content-between py-2 px-4">
                                                                            <button class="btn btn-link py-0 text-decoration-none text-muted">
                                                                                <i class="far fa-heart mr-1"></i>
                                                                                Like
                                                                            </button>

                                                                            <div>
                                                                                <i class="far fa-comment cursor-pointer text-muted mr-1"></i>
                                                                                Comment
                                                                            </div>

                                                                            <div>
                                                                                <i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
                                                                                Share
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <group-status
                                                v-for="(status, index) in feed"
                                                :key="'gs:' + status.id + index"
                                                :prestatus="status"
                                                :profile="profile"
                                                :group-id="groupId"
                                                v-on:comment-focus="commentFocus(index)"
                                                v-on:status-delete="statusDelete(index)"
                                                v-on:likes-modal="showLikesModal(index)" />

                                            <b-modal
                                                ref="likeBox"
                                                size="sm"
                                                centered
                                                hide-footer
                                                title="Likes"
                                                body-class="list-group-flush p-0">
                                                <div class="list-group py-1" style="max-height:300px;overflow-y:auto;">
                                                    <div
                                                        class="list-group-item border-top-0 border-left-0 border-right-0 py-2"
                                                        :class="{ 'border-bottom-0': index + 1 == likes.length }"
                                                        v-for="(user, index) in likes" :key="'modal_likes_'+index">
                                                        <div class="media align-items-center">
                                                            <a :href="user.url">
                                                                <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
                                                            </a>
                                                            <div class="media-body">
                                                                <p class="mb-0" style="font-size: 14px">
                                                                    <a :href="user.url" class="font-weight-bold text-dark">
                                                                        {{user.username}}
                                                                    </a>
                                                                </p>
                                                                <p v-if="!user.local" class="text-muted mb-0 text-truncate mr-3" style="font-size: 14px" :title="user.acct" data-toggle="dropdown" data-placement="bottom">
                                                                    <span class="font-weight-bold">{{user.acct.split('@')[0]}}</span><span class="text-lighter">&commat;{{user.acct.split('@')[1]}}</span>
                                                                </p>
                                                                <p v-else class="text-muted mb-0 text-truncate" style="font-size: 14px">
                                                                    {{user.display_name}}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <infinite-loading @infinite="infiniteLikesHandler" :distance="800" spinner="spiral">
                                                        <div slot="no-more"></div>
                                                        <div slot="no-results"></div>
                                                    </infinite-loading>
                                                </div>
                                            </b-modal>

                                            <div v-if="feed.length > 2" :distance="800">
                                                <infinite-loading @infinite="infiniteFeed">
                                                    <div slot="no-more"></div>
                                                    <div slot="no-results"></div>
                                                </infinite-loading>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else>
                                        <div class="card card-body mt-3 shadow-none border d-flex align-items-center justify-content-center" style="height: 100px;">
                                            <p class="lead mb-0">Join to participate in this group.</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="col-12 col-md-7 mt-3">
                                    <group-status
                                        :prestatus="status"
                                        :profile="profile"
                                        :group-id="groupId"
                                        :permalink-mode="true" />
                                </div>

                                <div class="col-12 col-md-5">
                                    <group-info-card :group="group" />
                                </div>
                            </div>

                            <group-about v-else-if="tab == 'about'" :group="group" />

                            <group-media v-else-if="tab == 'media'" :group="group" />

                            <group-members
                                v-else-if="tab == 'members'"
                                :group="group"
                                :request-count="atabs.request_count"
                                :is-admin="isAdmin"
                                :profile="profile"
                                v-on:decrementrc="decrementJoinRequestCount"
                                v-on:incrementMemberCount="incrementMemberCount"/>

                            <group-topics v-else-if="tab == 'topics'" :group="group" />

                            <group-insights v-else-if="tab == 'insights'" :group="group" />

                            <group-moderation
                                v-else-if="tab == 'moderation'"
                                :group="group"
                                v-on:decrement="decrementModCounter" />

                            <div v-else>
                                <p class="lead text-center font-weight-bold mt-5 text-muted">No content found</p>
                            </div>

                        </keep-alive>
                        <search-modal ref="searchModal" :group="group" :profile="profile" />
                        <invite-modal ref="inviteModal" :group="group" :profile="profile" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script type="text/javascript">
    import StatusCard from '~/partials/StatusCard.vue';
    import GroupMembers from '@/groups/partials/GroupMembers.vue';
    import GroupCompose from '@/groups/partials/GroupCompose.vue';
    import GroupStatus from '@/groups/partials/GroupStatus.vue';
    import GroupAbout from '@/groups/partials/GroupAbout.vue';
    import GroupMedia from '@/groups/partials/GroupMedia.vue';
    import GroupModeration from '@/groups/partials/GroupModeration.vue';
    import GroupTopics from '@/groups/partials/GroupTopics.vue';
    import GroupInfoCard from '@/groups/partials/GroupInfoCard.vue';
    import LeaveGroup from '@/groups/partials/LeaveGroup.vue';
    import GroupInsights from '@/groups/partials/GroupInsights.vue';
    import SearchModal from '@/groups/partials/GroupSearchModal.vue';
    import InviteModal from '@/groups/partials/GroupInviteModal.vue';
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import GroupBanner from '@/groups/partials/Page/GroupBanner.vue';

    export default {
        props: {
            groupId: {
                type: String
            },

            path: {
                type: String
            },

            permalinkMode: {
                type: Boolean,
                default: false
            },

            permalinkId: {
                type: String,
            }
        },

        components: {
            'status-card': StatusCard,
            'group-about': GroupAbout,
            'group-status': GroupStatus,
            'group-members': GroupMembers,
            'group-compose': GroupCompose,
            'group-topics': GroupTopics,
            'group-info-card': GroupInfoCard,
            'group-media': GroupMedia,
            'group-moderation': GroupModeration,
            'leave-group': LeaveGroup,
            'group-insights': GroupInsights,
            'search-modal': SearchModal,
            'invite-modal': InviteModal,
            'sidebar': SidebarComponent,
            'group-banner': GroupBanner
        },

        data() {
            return {
                initalLoad: false,
                profile: undefined,
                group: {},
                isMember: false,
                isAdmin: false,
                tab: 'feed',
                requestingMembership: false,
                composeText: null,
                feed: [],
                ids: [],
                maxId: null,
                status: undefined,
                likes: [],
                likesPage: 1,
                likesId: undefined,
                atabs: {
                    moderation_count: 0,
                    request_count: 0
                }
            };
        },

        mounted() {
            axios.get('/api/pixelfed/v1/accounts/verify_credentials')
            .then(res => {
                this.profile = res.data;
                this.fetchGroup();
            })
            .catch(err => {
                window.location.href = '/login?_next=' + encodeURIComponent(window.location.href);
            });

            if(this.permalinkMode) {
                this.fetchPermalink();
            } else {
                this.fetchFeed();
            }
        },

        methods: {
            initObservers() {
                // let video = document.querySelectorAll('video');
                // let isPaused = false;
                // let observer = new IntersectionObserver((entries, observer) => {
                //  entries.forEach(entry => {
                //      if (entry.intersectionRatio !=1  && !video.paused){
                //          video.pause();
                //          isPaused = true;
                //      }
                //      else if (isPaused) {
                //          video.play();
                //          isPaused = false
                //      }
                //  });
                // }, {threshold: 1});
                // observer.observe(video);
            },

            fetchGroup() {
                axios.get('/api/v0/groups/' + this.groupId)
                .then(res => {
                    this.group = res.data;
                    this.isMember = res.data.self.is_member;
                    this.isAdmin = ['founder', 'admin'].includes(res.data.self.role);

                    if(this.isAdmin) {
                        this.fetchAdminTabs();
                    }

                    if(this.path) {
                        if(this.isMember && ['about', 'topics', 'members', 'events', 'media', 'polls'].includes(this.path)) {
                            setTimeout(() => {
                                this.tab = this.path;
                                this.initalLoad = true;
                            }, 500);
                        } else if (this.isAdmin && ['insights', 'moderation'].includes(this.path)) {
                            setTimeout(() => {
                                this.tab = this.path;
                                this.initalLoad = true;
                            }, 500);
                        } else {
                            history.pushState(null, null, this.group.url);
                            this.initalLoad = true;
                        }
                    } else {
                        this.initalLoad = true;
                    }
                })
                .catch(err => {
                    // window.location.href = '/groups/unavailable';
                });
            },

            fetchAdminTabs() {
                axios.get('/api/v0/groups/' + this.groupId + '/atabs')
                .then(res => {
                    this.atabs = res.data;
                })
            },

            fetchFeed() {
                axios.get('/api/v0/groups/' + this.groupId + '/feed')
                .then(res => {
                    let self = this;
                    if(res.data && res.data.length) {
                        this.feed = res.data;
                        this.maxId = this.feed[this.feed.length - 1].id;
                        res.data.forEach(d => {
                            if(self.ids.indexOf(d.id) == -1) {
                                self.ids.push(d.id);
                            }
                        });
                    }
                    this.initObservers();
                })
            },

            fetchPermalink() {
                axios.get('/api/v0/groups/status', {
                    params: {
                        gid: this.groupId,
                        sid: this.permalinkId
                    }
                }).then(res => {
                    this.status = res.data;
                    if(this.status.in_reply_to_id) {
                        this.status.showCommentDrawer = true;
                    }
                }).catch(err => {
                    this.permalinkMode = false;
                    this.fetchFeed();
                });
            },

            timestampFormat(date, showTime = false) {
                let ts = new Date(date);
                return showTime ? ts.toDateString() + ' · ' + ts.toLocaleTimeString() : ts.toDateString();
            },

            switchTab(tab) {
                window.scrollTo(0,0);
                if(tab == 'feed' && this.permalinkMode) {
                    this.permalinkMode = false;
                    this.fetchFeed();
                }
                let url = tab == 'feed' ? this.group.url : this.group.url + '/' + tab;
                history.pushState(tab, null, url);
                this.tab = tab;
            },

            joinGroup() {
                this.requestingMembership = true;

                axios.post('/api/v0/groups/'+this.groupId+'/join')
                .then(res => {
                    this.requestingMembership = false;
                    this.group = res.data;
                    this.fetchGroup();
                    this.fetchFeed();
                }).catch(err => {
                    let body = err.response;

                    if(body.status == 422) {
                        this.tab = 'feed';
                        history.pushState('', null, this.group.url);
                        this.requestingMembership = false;
                        swal('Oops!', body.data.error, 'error');
                    }
                });
            },

            cancelJoinRequest() {
                if(!window.confirm('Are you sure you want to cancel your request to join this group?')) {
                    return;
                }

                axios.post('/api/v0/groups/'+this.groupId+'/cjr')
                .then(res => {
                    this.requestingMembership = false;
                }).catch(err => {
                    let body = err.response;

                    if(body.status == 422) {
                        swal('Oops!', body.data.error, 'error');
                    }
                });
            },

            leaveGroup() {
                if(!window.confirm('Are you sure you want to leave this group? Any content you shared will remain accessible. You won\'t be able to rejoin for 24 hours.')) {
                    return;
                }

                axios.post('/api/v0/groups/'+this.groupId+'/leave')
                .then(res => {
                    this.tab = 'feed';
                    history.pushState('', null, this.group.url);
                    this.feed = [];
                    this.isMember = false;
                    this.isAdmin = false;
                    this.group.self.role = null;
                    this.group.self.is_member = false;
                });
            },

            pushNewStatus(status) {
                this.feed.unshift(status);
            },

            commentFocus(index) {
                let status = this.feed[index];
                status.showCommentDrawer = true;
            },

            statusDelete(index) {
                this.feed.splice(index, 1);
            },

            infiniteFeed($state) {
                if(this.feed.length < 3) {
                    $state.complete();
                    return;
                }
                let apiUrl = '/api/v0/groups/' + this.groupId + '/feed';
                axios.get(apiUrl, {
                    params: {
                        limit: 6,
                        max_id: this.maxId
                    },
                }).then(res => {
                    if (res.data.length) {
                        // let self = this;
                        // data.forEach(d => {
                        //  if(self.ids.indexOf(d.id) == -1) {
                        //      if(self.maxId >= d.id) {
                        //          self.maxId = d.id;
                        //      }
                        //      self.ids.push(d.id);
                        //      self.feed.push(d);
                        //  }
                        // });
                        let posts = res.data.filter(p => this.ids.indexOf(p.id) == -1);
                        this.maxId = posts[posts.length - 1].id;
                        this.feed.push(...posts);
                        this.ids.push(...posts.map(p => p.id));
                        setTimeout(() => {
                            this.initObservers();
                        }, 1000);
                        $state.loaded();
                    } else {
                        $state.complete();
                    }
                });
            },

            decrementModCounter(amount) {
                let count = this.atabs.moderation_count;
                if(count == 0) {
                    return;
                }
                this.atabs.moderation_count = (count - amount);
            },

            setModCounter(amount) {
                this.atabs.moderation_count = amount;
            },

            decrementJoinRequestCount(amount = 1) {
                let count = this.atabs.request_count;
                this.atabs.request_count = (count - amount)
            },

            incrementMemberCount() {
                let count = this.group.member_count;
                this.group.member_count = (count + 1);
            },

            copyLink() {
                window.App.util.clipboard(this.group.url);
                this.$bvToast.toast(`Succesfully copied group url to clipboard`, {
                    title: 'Success',
                    variant: 'success',
                    autoHideDelay: 5000
                });
            },

            reportGroup() {
                swal('Report Group', 'Are you sure you want to report this group?')
                .then(res => {
                    if(res) {
                        location.href = `/i/report?id=${this.group.id}&type=group`;
                    }
                });
            },

            showSearchModal() {
                event.currentTarget.blur();
                this.$refs.searchModal.open();
            },

            showInviteModal() {
                event.currentTarget.blur();
                this.$refs.inviteModal.open();
            },

            showLikesModal(index) {
                this.likesId = this.feed[index].id;
                axios.get('/api/v0/groups/'+this.groupId+'/likes/'+this.likesId)
                .then(res => {
                    this.likes = res.data;
                    this.likesPage++;
                    this.$refs.likeBox.show();
                });
            },

            infiniteLikesHandler($state) {
                if(this.likes.length < 3) {
                    $state.complete();
                    return;
                }
                axios.get('/api/v0/groups/'+this.groupId+'/likes/'+this.likesId, {
                    params: {
                        page: this.likesPage,
                    },
                }).then(res => {
                    if (res.data.length > 0) {
                        this.likes.push(...res.data);
                        this.likesPage++;
                        if(res.data.length != 10) {
                            $state.complete();
                        } else {
                            $state.loaded();
                        }
                    } else {
                        $state.complete();
                    }
                });
            },
        }
    }
</script>

<style lang="scss">
    .group-feed-component {
        &-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 1rem 0;
            background-color: #fff;

            .cta-btn {
                width: 190px;
            }
        }

        &-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;

            &-nav {
                .nav-item {
                    .nav-link {
                        padding-top: 1rem;
                        padding-bottom: 1rem;
                        color: #6c757d;

                        &.active {
                            color: #2c78bf;
                            border-bottom: 2px solid #2c78bf;
                        }
                    }
                }

                &:not(last-child) {
                    .nav-item {
                        margin-right: 14px;
                    }
                }
            }
        }

        &-body {
            min-height: 40vh;
        }

        .member-label {
            padding: 2px 5px;
            font-size: 12px;
            color: rgba(75, 119, 190, 1);
            background:rgba(137, 196, 244, 0.2);
            border:1px solid rgba(137, 196, 244, 0.3);
            font-weight:400;
            text-transform: capitalize;
        }

        .dropdown-item {
            font-weight: 600;
        }

        .remote-label {
            padding: 2px 5px;
            font-size: 12px;
            color: #B45309;
            background: #FEF3C7;
            border: 1px solid #FCD34D;
            font-weight: 400;
            text-transform: capitalize;
        }
    }
</style>
