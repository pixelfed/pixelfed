  <div class="col-12 col-md-3 py-3 d-none d-md-block" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item {{request()->is('*/getting-started')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.getting-started')}}">{{__('helpcenter.gettingStarted')}}</a>
      </li>
      <li class="nav-item {{request()->is('*/sharing-media')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.sharing-media')}}">{{__('helpcenter.sharingMedia')}}</a>
      </li>
      <li class="nav-item {{request()->is('*/your-profile')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.your-profile')}}">{{__('helpcenter.profile')}}</a>
      </li>
      {{-- <li class="nav-item {{request()->is('*/stories')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.stories')}}">{{__('helpcenter.stories')}}</a>
      </li> --}}
      <li class="nav-item {{request()->is('*/hashtags')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.hashtags')}}">{{__('helpcenter.hashtags')}}</a>
      </li>
      <li class="nav-item {{request()->is('*/discover')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.discover')}}">{{__('helpcenter.discover')}}</a>
      </li>
      {{-- <li class="nav-item {{request()->is('*/direct-messages')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.dm')}}">{{__('helpcenter.directMessages')}}</a>
      </li> --}}
      {{-- <li class="nav-item {{request()->is('*/tagging-people')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.tagging-people')}}">{{__('helpcenter.taggingPeople')}}</a>
      </li> --}}
      <li class="nav-item {{request()->is('*/timelines')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.timelines')}}">{{__('helpcenter.timelines')}}</a>
      </li>
      {{-- <li class="nav-item {{request()->is('*/embed')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.embed')}}">{{__('helpcenter.embed')}}</a>
      </li> --}}
      <li class="nav-item {{request()->is('*/import')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.import')}}">Instagram Import</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item {{request()->is('*/community-guidelines')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.community-guidelines')}}">
          {{__('helpcenter.communityGuidelines')}}
        </a>
      </li>
      {{-- <li class="nav-item {{request()->is('*/what-is-the-fediverse')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.what-is-fediverse')}}">{{__('helpcenter.whatIsTheFediverse')}}</a>
      </li>
      <li class="nav-item {{request()->is('*/controlling-visibility')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.controlling-visibility')}}">
          {{__('helpcenter.controllingVisibility')}}
        </a>
      </li>
      <li class="nav-item {{request()->is('*/blocking-accounts')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.blocking-accounts')}}">
          {{__('helpcenter.blockingAccounts')}}
        </a>
      </li>--}}
      <li class="nav-item {{request()->is('*/safety-tips')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.safety-tips')}}">
          {{__('helpcenter.safetyTips')}}
        </a>
      </li>
      {{--<li class="nav-item {{request()->is('*/report-something')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.report-something')}}">
          {{__('helpcenter.reportSomething')}}
        </a>
      </li> --}}
      {{-- <li class="nav-item {{request()->is('*/data-policy')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.data-policy')}}">
          {{__('helpcenter.dataPolicy')}}
        </a>
      </li> --}}
    </ul>
  </div>
