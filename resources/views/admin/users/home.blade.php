@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<h3 class="font-weight-bold">Users</h3>
	<form method="get">
		<input type="hidden" name="a" value="search">
		<div class="input-group">
			<input class="form-control" name="q" placeholder="Search usernames" value="{{request()->input('q')}}">
			<div class="input-group-append">
				<button type="submit" class="btn btn-primary">
					<i class="fas fa-search"></i>
				</button>
			</div>
		</div>
	</form>
</div>
<hr>
<div v-if="selectedAll">
	<button class="btn btn-danger font-weight-bold mb-3" id="selectedAllInput" @@click="deleteSelected">Delete selected accounts</button>
</div>
<div class="table-responsive">
	<table class="table">
		<thead class="bg-light">
			<tr class="text-center">
				<th scope="col" class="border-0" width="1%">
					<div class="custom-control custom-checkbox account-select-check">
						<input type="checkbox" class="custom-control-input" id="allCheck" v-model="selectedAll">
						<label class="custom-control-label" for="allCheck"></label>
					</div>
				</th>
				<th scope="col" class="border-0" width="5%">
					<span>ID</span> 
				</th>
				<th scope="col" class="border-0" width="40%">
					<span>Username</span>
				</th>
				<th scope="col" class="border-0" width="5%">
					<span>Statuses</span>
				</th>
				<th scope="col" class="border-0" width="5%">
					<span>Followers</span>
				</th>
				<th scope="col" class="border-0" width="5%">
					<span>Following</span>
				</th>
				<th scope="col" class="border-0" width="30%">
					<span>Actions</span>
				</th>
			</tr>
		</thead>
		<tbody>
			@foreach($users as $key => $user)
			@if($user->status == 'deleted')
			<tr class="font-weight-bold text-center user-row">
				<th scope="row">
					<div class="custom-control custom-checkbox account-select-check">
						<input type="checkbox" class="custom-control-input" disabled>
						<label class="custom-control-label"></label>
					</div>
				</th>
				<td>
					<span class="text-danger" class="text-monospace">{{$user->id}}</span>
				</td>
				<td class="text-left">
					<img src="/storage/avatars/default.jpg" width="20" height="20" class="rounded-circle mr-1" />

					<span title="{{$user->username}}" data-toggle="tooltip" data-placement="bottom">
						<span class="text-danger">{{$user->username}}</span>
					</span>
				</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>
					<span class="font-weight-bold small">
						<span class="text-danger">Account Deleted</span>
					</span>
				</td>
			</tr>
			@else 
			<tr class="font-weight-bold text-center user-row">
				<th scope="row">
					<div class="custom-control custom-checkbox account-select-check">
						<input type="checkbox" id="{{$key}}" class="custom-control-input action-check" data-id="{{$user->id}}" data-username="{{$user->username}}">
						<label class="custom-control-label" for="{{$key}}"></label>
					</div>
				</th>
				<td>
					<span class="text-monospace">{{$user->id}}</span>
				</td>
				<td class="d-flex align-items-center">
					@if($user->account)
					<img src="{{$user->account['avatar']}}" width="20" height="20" class="rounded-circle mr-2" onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;" />
					@endif
					<span title="{{$user->username}}" data-toggle="tooltip" data-placement="bottom">
						<span>{{$user->username}}</span>
						@if($user->is_admin)
						<i class="text-danger fas fa-certificate" title="Admin"></i>
						@endif
					</span>
				</td>
				<td>
					@if($user->account)
					 {{$user->account['statuses_count']}}
					@else
					0
					@endif
				</td>
				<td>
					@if($user->account)
					 {{$user->account['followers_count']}}
					@else
					0
					@endif
				</td>
				<td>
					@if($user->account)
					 {{$user->account['following_count']}}
					@else
					0
					@endif
				</td>
				<td>
					<span class="action-row font-weight-lighter">
						<a href="/{{$user->username}}" class="pr-2 text-muted small font-weight-bold" title="View Profile" data-toggle="tooltip" data-placement="bottom">
							Profile
						</a>

						<a href="/i/admin/users/show/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Profile Review" data-toggle="tooltip" data-placement="bottom">
							Review
						</a>

						<a href="/i/admin/users/modtools/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Moderation Logs" data-toggle="tooltip" data-placement="bottom">
							Mod Tools
						</a>
						@if($user->status !== 'deleted' && !$user->is_admin)
						<a href="/i/admin/users/delete/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Delete account" data-toggle="tooltip" data-placement="bottom" onclick="deleteAccount({{$user->id}})">
							Delete
						</a>
						@endif
					</span>
				</td>
			</tr>
			@endif
			@endforeach
		</tbody>
	</table>
</div>
<div class="d-flex justify-content-center mt-5 small">
	<ul class="pagination">
		@if($pagination['prev'] !== null || $pagination['prev'] == 1)
		<li class="page-item"><a class="page-link pagination__prev" href="?page={{$pagination['prev']}}{{$pagination['query']}}" rel="prev">« Previous</a></li>
		@else
		<li class="page-item disabled"><span class="page-link" >« Previous</span></li>
		@endif
		<li class="page-item"><a class="page-link pagination__next" href="?page={{$pagination['next']}}{{$pagination['query']}}" rel="next">Next »</a></li>
	</ul>
</div>
@endsection

@push('styles')
<style type="text/css">
	.user-row:hover {
		background-color: #eff8ff;
	}
	.user-row:hover .action-row {
		display: block;
	}
	.user-row:hover .last-active {
		display: none;
	}
</style>
@endpush
@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.human-size').each(function(d,a) {
			let el = $(a);
			let size = el.data('bytes');
			el.text(filesize(size, {round: 0}));
		});
	});

	function deleteAccount(id) {
		event.preventDefault();
		if(!window.confirm('Are you sure you want to delete this account?')) {
			return;
		}

		axios.post('/i/admin/users/delete/' + id)
		.then(res => {
			swal('Account Deleted', 'Successfully deleted this account! This page will refresh once you press OK.', 'success')
			.then(res => {
				window.location.reload();
			});
		})
	}

	let app = new Vue({
		el: '#panel',

		data() {
			return {
				selectedAll: false
			}
		},

		watch: {
			selectedAll(val) {
				if(val) {
					if(document.querySelectorAll('.action-check').length == 0) {
						this.selectedAll = false;
						return;
					}
					document.querySelectorAll('.action-check').forEach(v => v.checked = true)
				} else {
					document.querySelectorAll('.action-check').forEach(v => v.checked = false)
				}
			}
		},

		methods: {
			async deleteSelected() {
				let usernames = [...document.querySelectorAll('.action-check:checked')].map(el => el.dataset.username);
				let ids = [...document.querySelectorAll('.action-check:checked')].map(el => el.dataset.id);

				swal({
					title: 'Confirm mass deletion',
					text: "Are you sure you want to delete the following accounts: \n\n" + usernames.join(" \n"),
					icon: 'warning',
					dangerMode: true,
					buttons: {
						cancel: {
							text: "Cancel",
							value: false,
							closeModal: true,
							visible: true,
						},
						delete: {
							text: "Delete",
							value: "delete",
							className: "btn-danger"
						}
					}
				})
				.then(async (res) => {
					if(res !== 'delete') {
						swal('Mass delete cancelled', '', 'success');
					} else {
						swal({
							title: 'Processing mass deletes',
							text: 'Do not close or navigate away from this page while we process this request',
							icon: 'warning',
							timer: 4000
						})

						await axios.all(ids.map((acct) => this.deleteAccountById(acct)))
						.finally(() => {
							swal({
								title: 'Accounts successfully deleted!',
								text: 'This page will refresh shortly!',
								icon: 'success',
								timer: 1000
							})
							setTimeout(() => {
								window.location.reload();
							}, 10000)
						})
					}
				})
			},

			async deleteAccountById(id) {
				await axios.post('/i/admin/users/delete/' + id)
			}
		}
	});

</script>
@endpush
