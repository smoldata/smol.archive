var thingmonger = thingmonger || {};
thingmonger.follow = (function() {

	var self = {

		init: function(id) {
			$(id).click(self.click_handler);
		},
		
		click_handler: function(e) {
			e.preventDefault();
			if (e.target.tagName == 'A') {
				var $link = $(e.target);
			} else {
				var $link = $(e.target).closest('a.user-follow');
			}
			var username = $link.data('username');
			var follow_crumb = $link.data('follow-crumb');
			var unfollow_crumb = $link.data('unfollow-crumb');

			$link.addClass('user-follow-loading');
			if (! $link.hasClass('user-follow-following')) {
				var cb = function() {
					$link.addClass('user-follow-following');
					$link.removeClass('user-follow-loading');
					$link.addClass('btn-primary');
					$link.removeClass('btn-default');
				};
				self.follow_user(username, follow_crumb, cb);
			} else {
				var cb = function() {
					$link.removeClass('user-follow-following');
					$link.removeClass('user-follow-loading');
					$link.removeClass('btn-primary');
					$link.addClass('btn-default');
				};
				self.unfollow_user(username, unfollow_crumb, cb);
			}
		},

		follow_user: function(username, crumb, cb) {
			var data = {
				username: username,
				crumb: crumb
			};
			thingmonger.api.api_call('users.follow', data, cb, self.onerror);
		},

		unfollow_user: function(username, crumb, cb) {
			var data = {
				username: username,
				crumb: crumb
			};
			thingmonger.api.api_call('users.unfollow', data, cb, self.onerror);
		},

		onerror: function(e) {
			if (e.error && e.error.code) {
				console.error('[' + e.error.code + '] ' + e.error.message);
			} else {
				console.error(e);
			}
		}

	};

	return self;
})();
