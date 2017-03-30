<?php

	loadlib('smol_meta');
	loadlib('mlkshk_api');
	loadlib('data_mlkshk');

	########################################################################

	function smol_archive_mlkshk_save_data($account, $verbose=false) {

		$filters = smol_archive_mlkshk_filters($account, $verbose);

		$items = array();
		foreach ($filters as $filter => $details){

			$endpoint = $details['endpoint'];

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
			} else if ($rsp['result']) {
				// Continue where we left off next time
				$last_item = array_pop($rsp['result']);
				$last_id = $last_item['sharekey'];
				smol_meta_set($account, "pivot_id_$filter", $last_id);
			}

			if ($verbose){
				echo "saving $filter data\n";
			}
			$saved_items = array();
			if ($items[$filter]){
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

	########################################################################

	function smol_archive_mlkshk_filters($account, $verbose=false){

		$filters = smol_meta_get($account, 'filters');
		if (! $filters ||
		    ! is_array($filters['user'])){

			if ($verbose){
				echo "setting up default mlkshk filters\n";
			}

			$shakes = smol_archive_mlkshk_shakes($account, $verbose);
			$user_shake = $shakes['shakes'][0]['id'];
			$filters = array(
				'user' => array(
					'endpoint' => "shakes/$user_shake",
					'label' => 'User'
				),
				'faves' => array(
					'endpoint' => 'favorites',
					'label' => 'Faves'
				)
			);
			smol_meta_set($account, 'filters', $filters);
		} else if ($verbose){
			echo "found cached filters\n";
		}
		return $filters;
	}

	########################################################################

	function smol_archive_mlkshk_shakes($account, $verbose=false){
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
		return $shakes;
	}

	########################################################################

	function smol_archive_mlkshk_add_filters($account, $verbose=false){

		$shakes = smol_archive_mlkshk_shakes($account);
		$add_filters = array();

		foreach ($shakes['shakes'] as $shake){
			$id = substr($shake['url'], 18); // http://mlkshk.com/[...]
			if (preg_match('/^user\//', $id)){
				$id = 'user';
				$add_filters[$id] = 'User';
			} else {
				$add_filters[$id] = $shake['name'];
			}
		}

		$filters = smol_archive_mlkshk_filters($account);
		foreach ($add_filters as $filter => $details){
			if ($filters[$filter]){
				unset($add_filters[$filter]);
			}
		}
		return $add_filters;
	}

	########################################################################

	function smol_archive_mlkshk_add_filter($account, $filter){

		$filters = smol_archive_mlkshk_filters($account);
		$shakes = smol_archive_mlkshk_shakes($account);

		foreach ($shakes['shakes'] as $shake){
			$id = substr($shake['url'], 18); // http://mlkshk.com/[...]
			if (preg_match('/^user\//', $id)){
				$id = 'user';
				$label = 'User';
			} else {
				$label = $shake['name'];
			}
			$endpoint = "shakes/{$shake['id']}";
			if ($id == $filter){
				$filters[$filter] = array(
					'label' => $label,
					'endpoint' => $endpoint
				);
			}
		}

		if ($filters[$filter]){
			smol_meta_set($account, 'filters', $filters);
			return array(
				'ok' => 1
			);
		}
		return array(
			'ok' => 0,
			'error' => 'Did not add filter'
		);
	}
	
	# the end
