@extends('portfolio.layout')

@section('content')
<div class="container">
	<div class="row mt-5 pt-5">
		<div class="col-12 text-center">
			<p class="mb-5">
				<span class="logo-mark px-3"><span class="text-gradient-primary">portfolio</span></span>
			</p>

            <h1>404 - Not Found</h1>

			<p class="lead pt-3 mb-4">This portfolio or post is either not active or has been removed.</p>

			<p class="mt-3">
				<a href="{{ config('app.url') }}" class="text-muted" style="text-decoration: underline;">Go back home</a>
			</p>
		</div>
	</div>
</div>
@endsection
