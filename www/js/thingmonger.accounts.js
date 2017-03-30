var thingmonger = thingmonger || {};
thingmonger.accounts = (function() {

	var self = {};

	function setup_accounts() {
		$('.account').each(function(i, account) {
			$(account).find('.edit-account').click(function(e) {
				e.preventDefault();
				$(account).closest('.account').toggleClass('account-edit');
			});
			$(account).find('.remove-account').click(function(e) {
				if (! confirm('Are you sure you want to remove that?')) {
					e.preventDefault();
				}
			});
		});
	}

	$(document).ready(function() {
		setup_accounts();
	});

	return self;
})();
