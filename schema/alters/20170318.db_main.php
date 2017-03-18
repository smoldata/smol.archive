<?php

	function alters_db_main_20170318(){

		# This migration adds a new column target_id to the smol_archive
		# table. target_id is basically the same thing as data_id,
		# except that for a retweet it points to the ~retweeted~ status
		# ID instead of the retweet ID. It is particular to Twitter
		# (for now), but could apply with things like Tumblr reblogs
		# down the line.
		#
		# The reason we want to do this is to avoid showing repeats in
		# the feed:
		# 
		#    - you liked X
		#    - you retweeted X
		#
		# Having a target_id column will mean that second item has a
		# common ID with the first one, allowing the code to group them
		# into a single row.
		#
		# The context here is that we have a table smol_archive that
		# records the flow of various data_* items in a timeline. Right
		# now it has service / data_id columns that might be something
		# like 'twitter' / '843104297411919873' as its values. Tweet
		# 843104297411919873 is a RT of 841714702384271363, so we will
		# leave data_id as-is but point target_id to 841714702384271363.
		#
		# TODO: figure out how to chunk this type of thing up into pages
		# so that we don't end up overrunning memory limits.
		# (20170318/dphiffer)

		# By default target_id is the same as data_id
		$rsp = db_write("
			UPDATE smol_archive
			SET target_id = data_id
		");

		$rsp = db_fetch("
			SELECT json
			FROM data_twitter
			WHERE is_retweet = 1
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$count = count($rsp['rows']);
		echo "Found $count retweets\n";

		$count = 0;
		foreach ($rsp['rows'] as $item){

			$details = json_decode($item['json'], 'as hash');

			# The tweet that *contains* the retweet
			$esc_data_id = addslashes($details['id_str']);

			# The tweet that got retweeted
			$esc_target_id = addslashes($details['retweeted_status']['id_str']);

			echo "Found data_id $esc_data_id, assigning target_id $esc_target_id\n";

			$rsp = db_update('smol_archive', array(
				'target_id' => $esc_target_id
			), "data_id = '$esc_data_id'");
			if (! $rsp['ok']){
				return $rsp;
			}

			$count++;
		}

		return array(
			'ok' => 1,
			'count' => $count
		);
	}
