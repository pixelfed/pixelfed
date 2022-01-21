@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-0">Custom Emoji</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
			<h1 class="text-center">This feature is not enabled</h1>
			<p class="text-center">To enable this feature, set <code>CUSTOM_EMOJI=true</code> in<br /> your .env file and run <code>php artisan config:cache</code></p>
		</div>
	</div>
</div>
@endsection
