@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">Reports</h3>
</div>
<hr>
<p class="lead">A list of reports you have made. </p>
<table class="table table-responsive">
	<thead class="bg-light">
			<th scope="col">ID</th>
			<th scope="col">Type</th>
			<th scope="col">Reported</th>
			<th scope="col">Status</th>
			<th scope="col">Created</th>
		</tr>
	</thead>
	<tbody>
		@foreach($reports as $report)
		<tr>
			<td class="font-weight-bold">{{$report->id}}</td>
			<td class="font-weight-bold">{{$report->type}}</td>
			<td class="font-weight-bold"><a href="{{$report->reported()->url()}}">{{str_limit($report->reported()->url(), 30)}}</a></td>
			@if(!$report->admin_seen)
			<td><span class="text-danger font-weight-bold">Unresolved</span></td>
			@else
			<td><span class="text-success font-weight-bold">Resolved</span></td>
			@endif
			<td class="font-weight-bold">{{$report->created_at->diffForHumans(null, true, true)}}</td>
		</tr>
		@endforeach
	</tbody>
</table>
<div class="d-flex justify-content-center mt-5 small">
	{{$reports->links()}}
</div>
@endsection