<?php

	########################################################################

	$GLOBALS['cfg']['api']['methods'] = array_merge(array(

		"action.fave" => array (
			"description" => "Fave or unfave an item",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_action",
			"requires_crumb" => 1,
			"request_method" => "POST",
			"parameters" => array(
				array("name" => "service", "description" => "The item's service", "documented" => 1, "required" => 1),
				array("name" => "data_id", "description" => "The item's data ID", "documented" => 1, "required" => 1),
				array("name" => "action", "description" => "Either 'fave' or 'unfave'", "documented" => 1, "required" => 1)
			)
		),

		"users.follow" => array (
			"description" => "Follow a user",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_users",
			"requires_crumb" => 1,
			"request_method" => "POST",
			"parameters" => array(
				array("name" => "username", "description" => "The user you wish to follow", "documented" => 1, "required" => 1)
			)
		),

		"users.unfollow" => array (
			"description" => "Unfollow a user",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_users",
			"requires_crumb" => 1,
			"request_method" => "POST",
			"parameters" => array(
				array("name" => "username", "description" => "The user you wish to unfollow", "documented" => 1, "required" => 1)
			)
		),

		"api.spec.methods" => array (
			"description" => "Return the list of available API response methods.",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_spec"
		),

		"api.spec.formats" => array(
			"description" => "Return the list of valid API response formats, including the default format",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_spec"
		),

		"test.echo" => array(
			"description" => "A testing method which echo's all parameters back in the response.",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_test"
		),

		"test.error" => array(
			"description" => "Return a test error from the API",
			"documented" => 1,
			"enabled" => 1,
			"library" => "api_test"
		),

	), $GLOBALS['cfg']['api']['methods']);

	# the end
