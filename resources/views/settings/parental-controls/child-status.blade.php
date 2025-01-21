@if($state)
<div class="card shadow-none border">
    @if($state === 'sent_invite')
    <div class="card-body d-flex justify-content-center flex-column align-items-center py-5" style="gap:1rem">
        <i class="far fa-envelope fa-3x"></i>
        <p class="lead mb-0 font-weight-bold">Child Invite Sent!</p>

        <div class="list-group">
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Created child invite</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Sent invite email to child</div>
            <div class="list-group-item py-1"><i class="far fa-times-circle text-dark mr-2"></i> Child joined via invite</div>
            <div class="list-group-item py-1"><i class="far fa-times-circle text-dark mr-2"></i> Child account is active</div>
        </div>
    </div>
    @elseif($state === 'awaiting_email_confirmation')
    <div class="card-body d-flex justify-content-center flex-column align-items-center py-5" style="gap:1rem">
        <i class="far fa-envelope fa-3x"></i>
        <p class="lead mb-0 font-weight-bold">Child Invite Sent!</p>

        <div class="list-group">
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Created child invite</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Sent invite email to child</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Child joined via invite</div>
            <div class="list-group-item py-1"><i class="far fa-times-circle text-dark mr-2"></i> Child account is active</div>
        </div>
    </div>
    @elseif($state === 'active')
    <div class="card-body d-flex justify-content-center flex-column align-items-center py-5" style="gap:1rem">
        <i class="far fa-check-circle fa-3x text-success"></i>
        <p class="lead mb-0 font-weight-bold">Child Account Active</p>

        <div class="list-group">
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Created child invite</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Sent invite email to child</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Child joined via invite</div>
            <div class="list-group-item py-1"><i class="far fa-check-circle text-success mr-2"></i> Child account is active</div>
        </div>

        <a class="btn btn-dark font-weight-bold px-5" href="{{ $pc->childAccount()['url'] }}">View Account</a>
    </div>
    @endif
</div>
@else
@endif
