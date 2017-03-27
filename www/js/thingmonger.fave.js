var thingmonger = thingmonger || {};
thingmonger.fave = (function() {

	var self = {
		init: function(id) {
			$(id).click(function(e) {
				e.preventDefault();
				self.clickHandler($(id));
			});
		},
		
		clickHandler: function($link) {

			var action = $link.hasClass('item-fave-active') ? 'unfave' : 'fave';
			var data = {
				service: $link.data('service'),
				target_id: $link.data('id'),
				action: action,
				crumb: $link.data('crumb')
			};
			
			var method = 'action.fave';

			var onsuccess = function() {
				$link.removeClass('item-fave-loading');
				if (action == 'fave') {
					$link.addClass('item-fave-active');
				} else {
					$link.removeClass('item-fave-active');
				}
			};

			var onerror = function() {
				console.error('Could not ' + action + ' item');
			};

			$link.addClass('item-fave-loading');
			thingmonger.api.api_call(method, data, onsuccess, onerror);
		}
	};

	return self;
})();
