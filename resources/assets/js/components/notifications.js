$(document).ready(function() {

	$('.nav-link.nav-notification').on('click', function(e) {
		e.preventDefault();
		let el = $(this);
		if(el.attr('data-toggle') == 'tooltip') {
			el.attr('data-toggle', 'dropdown');
			el.tooltip('dispose');
		}
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
				if(v.read_at == null) {
					notification.attr('style', 'border: 1px solid #6cb2eb;background-color: #eff8ff;border-bottom:none;');
				} else {
					notification.attr('style', 'border-bottom: 1px solid #ccc;');
				}
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
			.attr('style', 'border-top:1px solid #ccc')
			.text('View all notifications');
			container.append(all);
			pixelfed.notifications = true;
		}).catch((err) => {
			$('.nav-notification-dropdown .loader').addClass('font-weight-bold').text('Something went wrong. Please try again later.');
		});
	});

	$('.notification-action[data-type="mark_read"]').on('click', function(e) {
		e.preventDefault();

		axios.post('/api/v2/notifications', {
			'action': 'mark_read'
		}).then(res => {
			pixelfed.notifications = false;
			ls.del('n.lastCheck');
			ls.del('n.count');
			swal(
				'Success!',
				'All of your notifications have been marked as read.',
				'success'
			);
		}).catch(err => {
			swal(
				'Something went wrong!',
				'An error occurred, please try again later.',
				'error'
			);
		});
	});

	pixelfed.n.showCount = (count = 1) => {
		let el = $('.nav-link.nav-notification');
		el.tooltip('dispose');
		el.attr('title', count)
		el.attr('data-toggle', 'tooltip');
		el.tooltip({
			template: '<div class="tooltip notification-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner bg-danger px-3"></div></div>'
		});
		setTimeout(function() {
			el.fadeIn(function() {
				el.tooltip('show')
			});
		}, 500);
	}

	pixelfed.n.sound = () => {
		let beep = new Audio('/static/beep.mp3');
		beep.play();
	}

	pixelfed.n.check = (count) => {
		// pixelfed.n.sound();
		pixelfed.n.showCount(count);
	}

	pixelfed.n.fetch = (force = false) => {
		let now = Date.now();
		let ts = ls.get('n.lastCheck');
		let count = ls.get('n.count');
		let offset = now - 9e5;

		if(ts == null) {
			ts = now;
		}

		if(!force && count != null || ts > offset) {
			//pixelfed.n.showCount(count);
			ls.set('n.lastCheck', ts);
			return;
		}

		axios.get('/api/v2/notifications')
		.then(res => {
			let len = res.data.length;
			if(len > 0) {
				ls.set('n.count', len);
				ls.set('n.lastCheck', Date.now());
				pixelfed.n.check(len);
			}
		}).catch(err => {
		})
	}


});