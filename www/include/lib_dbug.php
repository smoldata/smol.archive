<?php

	function dbug() {
		if ($GLOBALS['cfg']['environment'] != 'dev') {
			return;
		}

		if (! $GLOBALS['cfg']['dbug_file_handle']) {
			$GLOBALS['cfg']['dbug_file_handle'] = fopen($GLOBALS['cfg']['dbug_log_path'], 'a');
			register_shutdown_function(function() {
				fclose($GLOBALS['cfg']['dbug_file_handle']);
			});
		}

		$fh = $GLOBALS['cfg']['dbug_file_handle'];
		$timestamp = date('Y-m-d H:i:s');

		$args = func_get_args();
		foreach ($args as $arg) {
			$arg = var_export($arg, true);
			fwrite($fh, "[$timestamp] $arg\n");
		}
	}

	# the end
