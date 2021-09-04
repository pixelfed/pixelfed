    <div class="mb-4 pb-4">
      <h4 class="font-weight-bold">{{__('settings.accountLog')}}</h4>
      <hr>
      <ul class="list-group border" style="max-height: 400px;overflow-y: auto;">
        @if($activity->count() == 0) 
        <p class="alert alert-info font-weight-bold">{{__('settings.noLogFound')}}</p>
        @endif
        @foreach($activity as $log)
        <li class="list-group-item rounded-0 border-0">
          <div class="media">
            <div class="media-body">
              <span class="my-0 font-weight-bold text-muted">
              	{{$log->action}} - <span class="font-weight-normal">{{$log->message}}</span>
              </span>
              <span class="mb-0 text-muted float-right">
              	{{$log->created_at->diffForHumans(null, false, false, false)}}
              	<span class="pl-2" data-toggle="collapse" href="#log-details-{{$log->id}}" role="button" aria-expanded="false" aria-controls="log-details-{{$log->id}}">
              		<i class="fas fa-ellipsis-v"></i>
              	</span>
              </span>
              <div class="collapse" id="log-details-{{$log->id}}">
              	<div class="py-2">
              		<p class="mb-0">
              			<span class="font-weight-bold">{{__('settings.ipAdress')}}</span>
              			<span>
              				{{$log->ip_address}}
              			</span>
              		</p>
            		<p class="mb-0">
              			<span class="font-weight-bold">{{__('settings.userAgent')}}</span>
              			<span>
              				{{$log->user_agent}}
              			</span>
              		</p>
              	</div>
              </div>
            </div>
          </div>
        </li>
        @endforeach
      </ul>
    </div>