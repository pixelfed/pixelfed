@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-6 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white d-inline-block mb-0">Stats</p>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Total posts</h5>
                                    <span class="h2 font-weight-bold mb-0">{{$data['statuses']}}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="ni ni-image"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                                <span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$data['statuses_monthly']}}</span>
                                <span class="text-nowrap">in last 30 days</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Total users</h5>
                                    <span class="h2 font-weight-bold mb-0">{{$data['users']}}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="ni ni-circle-08"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                                <span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$data['users_monthly']}}</span>
                                <span class="text-nowrap">in last 30 days</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Reports</h5>
                                    <span class="h2 font-weight-bold mb-0">{{$data['reports']}}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="ni ni-bell-55"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                                <span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$data['reports_monthly']}}</span>
                                <span class="text-nowrap">in last 30 days</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Messages</h5>
                                    <span class="h2 font-weight-bold mb-0">{{$data['contact']}}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                        <i class="ni ni-chat-round"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                                <span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$data['contact_monthly']}}</span>
                                <span class="text-nowrap">in last 30 days</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-4">

    <div class="row">
        <div class="col-md-8">
            <div class="card bg-default">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-light text-uppercase ls-1 mb-1">Overview</h6>
                            <h5 class="h3 text-white mb-0">Daily Posts</h5>
                        </div>
                        <div class="col">
                            <ul class="nav nav-pills justify-content-end">
                                <li class="nav-item mr-2 mr-md-0 posts-this-week" data-toggle="chart" data-target="#c1-dark" data-update='{"data":{"datasets":[{"data":{{$data['posts_this_week']}}}]}}'>
                                    <a href="#" class="nav-link py-2 px-3 active" data-toggle="tab">
                                        <span class="d-none d-md-block">This Week</span>
                                        <span class="d-md-none">W</span>
                                    </a>
                                </li>
                                <li class="nav-item" data-toggle="chart" data-target="#c1-dark" data-update='{"data":{"datasets":[{"data":{{$data['posts_last_week']}}}]}}'>
                                    <a href="#" class="nav-link py-2 px-3" data-toggle="tab">
                                        <span class="d-none d-md-block">Last Week</span>
                                        <span class="d-md-none">W</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Chart -->
                    <div class="chart">
                        <!-- Chart wrapper -->
                        <canvas id="c1-dark" class="chart-canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-none border mb-2" style="min-height:125px">
                <div class="card-body">
                    <p class="small text-uppercase font-weight-bold text-muted">Failed Jobs (24h)</p>
                    <p class="h2 mb-0">{{$data['failedjobs']}}</p>
                </div>
            </div>
            <div class="card shadow-none border mb-2" style="min-height:125px">
                <div class="card-body">
                    <p class="small text-uppercase font-weight-bold text-muted">Remote Instances</p>
                    <p class="h2 mb-0">{{$data['instances']}}</p>
                </div>
            </div>
            <div class="card shadow-none border mb-2" style="min-height:125px">
                <div class="card-body">
                    <p class="small text-uppercase font-weight-bold text-muted">Photos Uploaded</p>
                    <p class="h2 mb-0">{{$data['media']}}</p>
                </div>
            </div>
            <div class="card shadow-none border" style="min-height:125px">
                <div class="card-body">
                    <p class="small text-uppercase font-weight-bold text-muted">Storage Used</p>
                    <p class="human-size mb-0" data-bytes="{{$data['storage']}}">{{$data['storage']}} bytes</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('.human-size').each(function(d,a) {
            let el = $(a);
            let size = el.data('bytes');
            el.addClass('h2');
            el.text(filesize(size, {round: 0}));
        });
    });
</script>
@endpush
