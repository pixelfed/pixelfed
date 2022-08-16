@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white">Pages</p>
					<p class="lead text-white mt-n4 mb-0">Manage public and custom page content</p>
				</div>

                @if($pages->count() < 5)
                <div class="col-12">
                    <hr>
                    <div class="btn-group">
                        @if(!$pages->contains('slug', '=', '/site/about'))
                        <form class="form-inline mr-1" method="post" action="/i/admin/settings/pages/create">
                            @csrf
                            <input type="hidden" name="page" value="about">
                            <button type="submit" class="btn btn-default font-weight-bold">Customize About Page</button>
                        </form>
                        @endif
                        @if(!$pages->contains('slug', '=', '/site/privacy'))
                        <form class="form-inline mr-1" method="post" action="/i/admin/settings/pages/create">
                            @csrf
                            <input type="hidden" name="page" value="privacy">
                            <button type="submit" class="btn btn-default font-weight-bold">Customize Privacy Page</button>
                        </form>
                        @endif
                        @if(!$pages->contains('slug', '=', '/site/terms'))
                        <form class="form-inline mr-1" method="post" action="/i/admin/settings/pages/create">
                            @csrf
                            <input type="hidden" name="page" value="terms">
                            <button type="submit" class="btn btn-default font-weight-bold">Customize Terms Page</button>
                        </form>
                        @endif
                        @if(!$pages->contains('slug', '=', '/site/kb/community-guidelines'))
                        <form class="form-inline mr-1" method="post" action="/i/admin/settings/pages/create">
                            @csrf
                            <input type="hidden" name="page" value="community_guidelines">
                            <button type="submit" class="btn btn-default font-weight-bold">Customize Guidelines Page</button>
                        </form>
                        @endif
                        @if(!$pages->contains('slug', '=', '/site/legal-notice'))
                        <form class="form-inline" method="post" action="/i/admin/settings/pages/create">
                            @csrf
                            <input type="hidden" name="page" value="legal_notice">
                            <button type="submit" class="btn btn-default font-weight-bold">Customize Legal Notice Page</button>
                        </form>
                        @endif
                  </div>
                </div>
                @endif
			</div>
		</div>
	</div>
</div>
<div class="container-fluid mt-4">
  @if($pages->count())
  <div class="table-responsive">
    <table class="table">
      <thead class="bg-light">
        <tr class="text-center">
          <th scope="col" class="border-0" width="5%">
            <span>ID</span> 
          </th>
          <th scope="col" class="border-0" width="50%">
            <span>Slug</span>
          </th>
          <th scope="col" class="border-0" width="15%">
            <span>State</span>
          </th>
          <th scope="col" class="border-0" width="30%">
            <span>Updated</span>
          </th>
        </tr>
      </thead>
      <tbody>
        @foreach($pages as $page)
        <tr class="font-weight-bold text-center page-row">
          <th scope="row">
            <a href="{{$page->editUrl()}}">{{$page->id}}</a>
          </th>
          <td>{{$page->slug}}</td>
          @if($page->active)
          <td class="text-success font-weight-bold">Live</td>
          @else
          <td class="text-muted">Draft</td>
          @endif
          <td>{{$page->updated_at->diffForHumans(null, true, true, true)}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-center mt-5 small">
    {{$pages->links()}}
  </div>
  @else
  <div class="card border shadow-none rounded-0">
    <div class="card-body text-center">
      <p class="lead text-muted font-weight-bold py-5">No custom pages found</p>
    </div>
  </div>
  @endif
@endsection
