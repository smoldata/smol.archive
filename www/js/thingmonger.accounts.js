var thingmonger = thingmonger || {};
thingmonger.accounts = (function() {

	var self = {};

	function setup_accounts() {
		$('.account').each(function(i, account) {
			$(account).find('.edit-account').click(function(e) {
				e.preventDefault();
				$(account).find('form').toggleClass('hidden');
			});
		});
	}

	$(document).ready(function() {
		setup_accounts();
	});

	return self;
})();
