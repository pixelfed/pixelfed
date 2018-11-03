$(document).ready(function() {

$('.nav-link.nav-notification').on('click', function(e) {
	e.preventDefault();
	let el = $(this);
	let container = $('.navbar .nav-notification-dropdown');
	if(pixelfed.notifications) {
		return;
	}
	axios.get('/api/v2/notifications')
	.then((res) => {
		$('.nav-notification-dropdown .loader').hide();
		let data = res.data;
		data.forEach(function(v, k) {
			let action = v.action;
			let notification = $('<li>').addClass('dropdown-item py-3')
			.attr('style', 'border-bottom: 1px solid #ccc')
			switch(action) {
				case 'comment':
					let avatar = $('<span>')
					.attr('class', 'notification-icon pr-3');
					let avatarImg = $('<img>')
					.attr('width', '32px')
					.attr('height', '32px')
					.attr('class', 'rounded-circle')
					.attr('style', 'border: 1px solid #ccc')
					.attr('src', v.actor.avatar);
					avatar = avatar.append(avatarImg);

					let text = $('<span>')
					.attr('href', v.url)
					.attr('class', 'font-weight-bold')
					.html(v.rendered);

					notification.append(avatar);
					notification.append(text);
					container.append(notification);
				break;

				case 'follow':
					avatar = $('<span>')
					.attr('class', 'notification-icon pr-3');
					avatarImg = $('<img>')
					.attr('width', '32px')
					.attr('height', '32px')
					.attr('class', 'rounded-circle')
					.attr('style', 'border: 1px solid #ccc')
					.attr('src', v.actor.avatar);
					avatar = avatar.append(avatarImg);

					text = $('<span>')
					.attr('href', v.url)
					.attr('class', 'font-weight-bold')
					.html(v.rendered);

					notification.append(avatar);
					notification.append(text);
					container.append(notification);
				break;
			}
		});
		let all = $('<a>')
		.attr('class', 'dropdown-item py-3 text-center text-primary font-weight-bold')
		.attr('href', '/account/activity')
		.text('View all notifications');
		container.append(all);
		pixelfed.notifications = true;
	}).catch((err) => {
		$('.nav-notification-dropdown .loader').addClass('font-weight-bold').text('Something went wrong. Please try again later.');
	});
});

});