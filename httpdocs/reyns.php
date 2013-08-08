<?php
	ini_set('memory_limit', '-1');
	set_time_limit(0);

	// In order to order option matrix correctly we use hard coded option values
	// const
	$optionValues = array(
		'Size' => array(
			'XXS',
			'XS',
			'S',
			'M',
			'L',
			'XL',
			'2XL',
			'3XL',
			'4XL'
		),
		'Style' => array(
			'Button Front',
			'Longsleeve',
			'Pullover Style'
		),
		'Waist' => array(
			'30',
			'32',
			'34',
			'36',
			'38',
			'40',
			'42',
			'44'
		),
		'Waist Inseam' => array(
			'30, Inseam 30',
			'30, Inseam 32',
			'30, Inseam 34',
			'32, Inseam 30',
			'32, Inseam 32',
			'32, Inseam 34',
			'34, Inseam 30',
			'34, Inseam 32',
			'34, Inseam 34',
			'36, Inseam 30',
			'36, Inseam 32',
			'36, Inseam 34',
			'38, Inseam 30',
			'38, Inseam 32',
			'38, Inseam 34',
			'40, Inseam 30',
			'40, Inseam 32',
			'40, Inseam 34',
			'42, Inseam 30',
			'42, Inseam 32',
			'42, Inseam 34'
		)
	);



	$conn = mysqli_connect('localhost', 'reyns', 'reyns', 'reyns_modx');
	if(mysqli_connect_errno()) {
		die('Could not connect: ' . mysqli_connect_error());
	}

	$sel = "select * from modx_visioncart_products where active=1 and parent=0 order by id asc";
	$res = mysqli_query($conn, $sel);

	echo "Found {$res->num_rows} products<br /><br />";

	$products = array();

	while($row = mysqli_fetch_array($res)) {
		$prod['orig_id'] = $row['id'];
		$prod['name'] = $row['name'];
		$prod['url_name'] = $row['alias'];
		$prod['title'] = "Reyn Spooner - {$row['name']}";
		$prod['long_description'] = $row['description'];
		$prod['sku'] = $row['articlenumber'];
		$prod['price'] = $row['price'];
		$prod['total_units_in_stock'] = 100;
		
		$products[] = $prod;
	}
	mysqli_free_result($res);


	foreach($products as $k => &$v) {
		$sel = "select 
			cats.id, cats.name, cats.parent 
			from modx_visioncart_products_categories prodcats 
			left join modx_visioncart_categories cats on cats.id=prodcats.categoryid where productid={$v['orig_id']}
			order by cats.parent, cats.id
		";
		$res = mysqli_query($conn, $sel);

		$cats = array();
		while($row = mysqli_fetch_array($res)) {
			$cat['orig_id'] = $row['id'];
			$cat['name'] = $row['name'];
			$cat['orig_parent_id'] = $row['parent'];
			
			$cats[] =$cat;
		}

		if(count($cats)) {
			$catstr = $cats[0]['name'];
			
			for($i = 1, $j = count($cats); $i < $j; $i++):
				$catstr .= "|{$cats[0]['name']}=>{$cats[$i]['name']}";
			endfor;
		}

		$v['categories'] = $catstr;
		mysqli_free_result($res);


/*
		$sel = "select 
			opts.* 
			from modx_visioncart_products_options prodopts 
			left join modx_visioncart_options opts on opts.id=prodopts.optionid 
			where productid={$v['orig_id']}
		";
		$res = mysqli_query($conn, $sel);

		$opts = array();
		while($row = mysqli_fetch_array($res)) {
			$opt['id'] = $row['id'];
			$opt['name'] = $row['name'];
                            
			$sel = "select
				*
				from modx_visioncart_options_values
				where optionid={$opt['id']}
			";
			$res2 = mysqli_query($conn, $sel);

			$optvals = array();
			while($row2 = mysqli_fetch_array($res2)) {
				$optvals[] = $row2['value'];
			}
			$opt['vals'] = $optvals;

			$opts[] = $opt;
		}

		$optstr = '';
		if(count($opts)) {
			foreach($opts as $opt):
				$optstr .= ' ' . $opts[0]['name'] . ': ';
			
				foreach($opt['vals'] as $val):
					$optstr .= "{$val}|";
				endforeach;
			endforeach;
		}
		$optstr = ltrim($optstr, ' ');
		$optstr = rtrim($optstr, '|');
		$v['lemon_options'] = $optstr;
		$v['options'] = $opts;
*/
		
		$v['option_matrix'] = calcOptionMatrix($v['orig_id']);

/* 		mysqli_free_result($res); */
	}
	
	
	// Insert temp products
	mysqli_query($conn, "TRUNCATE TABLE lemon_products_temp");
	
	$ins = "INSERT INTO lemon_products_temp (orig_id, name, url_name, long_description, title, sku, price, total_units_in_stock) VALUES ";
	
	foreach($products as $p):
		$ins .= "({$p['orig_id']},";
		$ins .= '"' . mysqli_real_escape_string($conn, $p['name']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['url_name']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['long_description']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['title']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['sku']) . '",';
		$ins .= "{$p['price']}, {$p['total_units_in_stock']}),";
		
		$ins2 = "INSERT INTO lemon_options_temp (orig_id, name, value) VALUES ";

		if(count($p['option_matrix'])) {
			foreach($p['option_matrix'] as $kk => $vv):
				foreach($vv as $jj):
					$ins2 .= "({$p['orig_id']},";
					$ins2 .= '"' . mysqli_real_escape_string($conn, $kk) . '",';
					$ins2 .= '"' . mysqli_real_escape_string($conn, $jj) . '"),';
				endforeach;
			endforeach;
	
			$ins2 = rtrim($ins2, ',') . ';';
	
			if(mysqli_query($conn, $ins2) === FALSE) {
				echo $p['orig_id'] . '<br />';
				echo $ins2 . '<br />';
				echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
			}
		}
	endforeach;

	$ins = rtrim($ins, ',') . ';';
	if(mysqli_query($conn, $ins) === FALSE) {
		echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
		echo $ins . '<br />';
	}

	print '<pre>';print_r($products);print '</pre>';
	
	mysqli_close($conn);



/*********************************************************************************************************************/
/*********************************************************************************************************************/
/*********************************************************************************************************************/


function calcOptionMatrix($pid) {
	global $conn, $optionValues;
	$option_matrix = array();
	
/*
	Productids to test out
	3010
		Has options 2 and 4 (color and size) both need code to work correctly
*/
/* 	$pid = 3010; */


	// Get all products (which are basically just options) for this parent product
	$sql = "SELECT 
			id 
		FROM 
			modx_visioncart_products 
		WHERE 
			parent={$pid} AND 
			active=1 
		ORDER BY 
			id
	";
	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		my_error($sql);
		return $option_matrix;
	}
	
	$ids = array();
	while($row = mysqli_fetch_array($res)) {
		$ids[] = $row['id'];
	}
	$child_ids = $ids;
	$ids[] = $pid;
	$ids = implode(',', $ids);
	$child_ids = implode(',', $child_ids);	// Used later to determine enabled options
	

	$sql = "SELECT 
			optionid 
		FROM 
			modx_visioncart_products_options 
		WHERE 
			productid IN ({$ids}) 
		GROUP BY 
			optionid
	";
	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		my_error($sql);
		return $option_matrix;
	}

	$optids = array();
	while($row = mysqli_fetch_array($res)) {
		$optids[] = $row['optionid'];
	}


	$option_matrix = array();

	foreach($optids as $id) {
		switch($id) {
			case 1:
				// Size
				$option_matrix['Size'] = $optionValues['Size'];
			break;
			
			case 2:
				// Colors, we pull in the colors used for this product(s)
				$option_matrix['Color'] = getColorsForProduct($ids);
			break;
			
			case 3:
				$option_matrix['Style'] = $optionValues['Style'];
			break;
			
			case 4:
				// 2 different Waist, without inseam and with but both use same optionid
 				$option_matrix['Waist'] = getWaistForProduct($ids);
			break;
			
			case 5:
				echo 'Option #5 unused, ignoring...<br /><br />';
			break;

			default:
				echo "Invalid ID specified for option: {$id}<br /><br />";
			break;
		}
	}

	if(count($option_matrix) <= 0) {
		return array();
	}

/*
	print '<pre>';print_r($option_matrix);print '</pre>';
	echo '<br /><br />';
*/

	mysqli_query($conn, "TRUNCATE TABLE lemon_optmatrix");

	$sql = "INSERT INTO lemon_optmatrix (name, value) VALUES ";

	foreach($option_matrix as $k => $v):
		foreach($v as $val):
			$sql .= '("' . mysqli_real_escape_string($conn, $k) . '",';
			$sql .= '"' . mysqli_real_escape_string($conn, $val) . '"),';
		endforeach;
	endforeach;

	$sql = rtrim($sql, ',') . ';';

/* 	print '<pre>';print_r($sql);print '</pre>'; */

	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		g_error($pid);
		my_error($sql);
		return NULL;
	}

	// Calc values we need
	$tvals = array();
	$tables = array();
	$where = array();
	$i = 0;
	
	foreach($option_matrix as $k=>$v) {
		$tvals[] = "t{$i}.name, t{$i}.value";
		
		if($i > 0) {
			$tables[] = "lemon_optmatrix t{$i}";
		}
		
		$where[] = "t{$i}.name='{$k}'";
		$i++;
	}
	$tvals = implode(',', $tvals);
	$tables = implode(' CROSS JOIN ', $tables);
	$where = implode(' AND ', $where);

/*
	print '<pre>';print_r($tvals);print '</pre>';
	print '<pre>';print_r($tables);print '</pre>';
	print '<pre>';print_r($where);print '</pre>';
*/
	
	$sql = "SELECT {$tvals} from lemon_optmatrix t0 ";
	if(strlen($tables)) {
		$sql .= "CROSS JOIN {$tables} ";
	}
	$sql .=  "WHERE {$where}";
	
/* 	print '<pre>';print_r($sql);print '</pre>'; */

	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		my_error($sql);
	}

	$option_matrix = array();
	while($row = mysqli_fetch_array($res, MYSQLI_NUM)) {
		$option_matrix[] = $row;
	}

/* 	print '<pre>';print_r($option_matrix);print '</pre>'; */

	return $option_matrix;
}


function getColorsForProduct($pids) {
	global $conn;
	
	$ret = array();
	
	$sql = "SELECT 
			optvals.value
		FROM 
			modx_visioncart_products_options prodopts
		LEFT JOIN
			modx_visioncart_options_values optvals ON optvals.id=prodopts.valueid
		WHERE 
			prodopts.productid IN ({$pids}) 
			AND
			prodopts.optionid=2			
		GROUP BY 
			optvals.value
	";
	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		my_error($sql);
		return $ret;
	}

	$optset = array();
	while($row = mysqli_fetch_array($res)) {
		$ret[] = $row['value'];
	}
	
	return $ret;
}


function getWaistForProduct($pids) {
	global $conn, $optionValues;
	
	$ret = array();
	
	$sql = "SELECT 
			optvals.value
		FROM 
			modx_visioncart_products_options prodopts
		LEFT JOIN
			modx_visioncart_options_values optvals ON optvals.id=prodopts.valueid
		WHERE 
			prodopts.productid IN ({$pids}) 
			AND
			prodopts.optionid=4
		GROUP BY 
			optvals.value
		LIMIT 1
	";
	$res = mysqli_query($conn, $sql);
	if($res === FALSE) {
		my_error($sql);
		return $ret;
	}

	while($row = mysqli_fetch_array($res)) {
		$type = $row['value'];
	}
	
	mysqli_free_result($res);

	if(strpos($type, 'Inseam') === FALSE) {
		$ret = $optionValues['Waist'];
	}
	else {
		$ret = $optionValues['Waist Inseam'];
	}

/* 	print '<pre>';print_r($ret);print '</pre>'; */
	
	return $ret;
}


function my_error($sql) {
	global $conn;
	echo $sql . '<br />error: ' . mysqli_error($conn) . '<br /><br />';
}

function g_error($val) {
	echo "error: {$val}<br /><br />";
}
