<?php

	loadlib('smol_meta');
	loadlib('mlkshk_api');
	loadlib('data_mlkshk');

	########################################################################

	function smol_archive_mlkshk_save_data($account, $verbose=false) {

		$endpoints = smol_archive_mlkshk_get_endpoints($account, $verbose);

		$items = array();
		foreach ($endpoints as $filter => $endpoint){

			if ($verbose){
				echo "querying mlkshk $filter $endpoint\n";
			}

			$items[$filter] = array();

			// First get the new stuff
			$args = array();
			$rsp = smol_archive_mlkshk_query($account, $endpoint, $args, $verbose);
			if (! $rsp['ok']){
				if ($verbose){
					echo "error querying $endpoint, skipping\n";
				}
				continue;
			}
			$items[$filter] = $rsp['result'];

			// Next get the older stuff (with each run, pivot_id
			//   advances down the timeline)
			$pivot_id = smol_meta_get($account, "pivot_id_$filter");
			if ($pivot_id){

				$endpoint .= "/before/$pivot_id";
				$rsp = smol_archive_mlkshk_query($account, $endpoint, $args, $verbose);
				if ($rsp['ok']){
					$items[$filter] = array_merge($items[$filter], $rsp['result']);
				}

				if (! $rsp['result']){
					// Reached the end of the timeline,
					// reset for next time
					smol_meta_set($account, "pivot_id_$filter", 0);
				} else {
					// Continue where we left off next time
					$last_item = array_pop($rsp['result']);
					$last_id = $last_item['sharekey'];
					smol_meta_set($account, "pivot_id_$filter", $last_id);
				}
			} else {
				// Continue where we left off next time
				$last_item = array_pop($rsp['result']);
				$last_id = $last_item['sharekey'];
				smol_meta_set($account, "pivot_id_$filter", $last_id);
			}

			if ($verbose){
				echo "saving $filter data\n";
			}
			$saved_items = array();
			foreach ($items[$filter] as $item){
				$rsp = data_mlkshk_save($item, $verbose);
				if ($rsp['ok']){
					$saved_item = smol_archive_escaped_item($account, $filter, $rsp);
					$data_id = $saved_item['data_id'];
					$saved_items[$data_id] = $saved_item;
				} else {
					echo "error saving item ";
					var_export($rsp);
				}
			}

			if (empty($saved_items)){
				if ($verbose){
					echo "(no data saved)\n";
				}
				continue;
			}

			if ($verbose){
				echo "archiving $filter items\n";
			}
			smol_archive_save_items($account, $filter, $saved_items, $verbose);
		}
	}

	########################################################################

	function smol_archive_mlkshk_query($account, $endpoint, $args=array(), $verbose=false) {

		$defaults = array();
		$args = array_merge($defaults, $args);

		if ($verbose){
			echo "mlkshk_api_get $endpoint";
		}
		$rsp = mlkshk_api_get($account, $endpoint, $args);

		if (! $rsp['ok']){
			if ($verbose){
				var_export($rsp);
			}
		} else {
			$rsp['result'] = $rsp['result']['sharedfiles'];
			if ($verbose){
				echo " found " . count($rsp['result']) . " items\n";
			}
		}

		return $rsp;
	}
	
	function smol_archive_mlkshk_get_endpoints($account, $verbose=false){

		$endpoints = smol_meta_get($account, 'endpoints');
		if (! $endpoints){

			if ($verbose){
				echo "calculating mlkshk endpoints\n";
			}

			$shakes = smol_meta_get($account, 'shakes');
			if (! $shakes){

				if ($verbose){
					echo "loading mlkshk shakes\n";
				}

				$rsp = mlkshk_api_get($account, 'shakes');
				if (! $rsp['ok']){
					if ($verbose){
						var_export($rsp);
					}
					return array();
				}
				$shakes = $rsp['result'];
				$shakes['updated_at'] = date('Y-m-d H:i:s');
				smol_meta_set($account, 'shakes', $shakes);
			} else if ($verbose){
				echo "found cached shakes\n";
			}
			$user_shake = $shakes['shakes'][0]['id'];
			$endpoints = array(
				'user' => "shakes/$user_shake"
			);
			smol_meta_set($account, 'endpoints', $endpoints);
		} else if ($verbose){
			echo "found cached endpoints\n";
		}
		return $endpoints;
	}
