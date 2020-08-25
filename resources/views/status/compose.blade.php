@extends('layouts.app')

@section('content')

<div class="alert alert-info text-center rounded-0">
<div class="container">
<span class="font-weight-bold">ComposeUI v3 is deprecated</span>
<br>
Please use the <a href="#" onclick="event.preventDefault();window.App.util.compose.post()" class="font-weight-bold">new UI</a> to compose a post.
</div>
</div>

@endsection