<?php
	ini_set('memory_limit', '-1');
	set_time_limit(0);

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
		
		$v['option_matrix'] = calcOptionMatrix($v['orig_id'], $v['options']);

		mysqli_free_result($res);
	}
	
	
	// Insert temp products
	mysqli_query($conn, "TRUNCATE TABLE lemon_products_temp");
	
	$ins = "INSERT INTO lemon_products_temp (orig_id, name, url_name, long_description, title, sku, price, total_units_in_stock) VALUES ";
	$ins2 = "INSERT INTO lemon_options_temp (orig_id, name, value) VALUES ";

	foreach($products as $p):
		$ins .= "({$p['orig_id']},";
		$ins .= '"' . mysqli_real_escape_string($conn, $p['name']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['url_name']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['long_description']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['title']) . '",';
		$ins .= '"' . mysqli_real_escape_string($conn, $p['sku']) . '",';
		$ins .= "{$p['price']}, {$p['total_units_in_stock']}),";
		
		foreach($p['options'] as $opt):
			foreach($opt['vals'] as $oval):
				$ins2 .= "({$p['orig_id']},";
				$ins2 .= '"' . mysqli_real_escape_string($conn, $opt['name']) . '",';
				$ins2 .= '"' . mysqli_real_escape_string($conn, $oval) . '"),';
/* 				echo $ins2 . '<br />'; */
			endforeach;
		endforeach;
	endforeach;

	$ins = rtrim($ins, ',') . ';';
	$ins2 = rtrim($ins2, ',') . ';';
 
	if(mysqli_query($conn, $ins) === FALSE) {
		echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
		echo $ins . '<br />';
	}

	if(mysqli_query($conn, $ins2) === FALSE) {
		echo $ins2 . '<br />';
		echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
	}


	print '<pre>';print_r($products);print '</pre>';
	
	mysqli_close($conn);



/*********************************************************************************************************************/
/*********************************************************************************************************************/
/*********************************************************************************************************************/


function calcOptionMatrix($pid, $options) {
	global $conn;
	$mat = array();

	if(count($options) < 2) {
		return $mat;
	}

	mysqli_query($conn, "TRUNCATE TABLE lemon_optmatrix");

	$ins = "INSERT INTO lemon_optmatrix (opt_id, name, value) VALUES ";

	foreach($options as $opt):
		foreach($opt['vals'] as $val):
			$ins .= "({$opt['id']},";
			$ins .= '"' . mysqli_real_escape_string($conn, $opt['name']) . '",';
			$ins .= '"' . mysqli_real_escape_string($conn, $val) . '"),';
		endforeach;

/* 		print '<pre>';print_r($val);print '</pre>'; */
/* 		print '<pre>';print_r($ins);print '</pre>'; */
	endforeach;

	$ins = rtrim($ins, ',') . ';';

/* 	print '<pre>';print_r($ins);print '</pre>'; */

	$res = mysqli_query($conn, $ins);
	if($res === FALSE) {
		echo $ins . '<br />';
		echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
	}


	// Calc values we need
	$tvals = array();
	$tables = array();
	$where = array();

	for($i = 0; $i < count($options); $i++) {
		
		$tvals[] = "t{$i}.opt_id, t{$i}.name, t{$i}.value";
		$tables[] = "lemon_optmatrix t{$i}";
		$where[] = "t{$i}.opt_id={$options[$i]['id']}";
	}
	$tvals = implode(',', $tvals);
	$tables = implode(',', $tables);
	$where = implode(' AND ', $where);

/*
	print '<pre>';print_r($tvals);print '</pre>';
	print '<pre>';print_r($tables);print '</pre>';
	print '<pre>';print_r($where);print '</pre>';
*/
	
	$sel = "SELECT {$tvals} from lemon_optmatrix CROSS JOIN {$tables} WHERE {$where}";
/* 	print '<pre>';print_r($sel);print '</pre>'; */

	$res = mysqli_query($conn, $sel);
	if($res === FALSE) {
		echo $sel . '<br />';
		echo('error: ' . mysqli_error($conn) . '<br /><br /><br />');
	}

	while($row = mysqli_fetch_array($res)) {
		$mat[] = $row;
	}

/* 	print '<pre>';print_r($matrix);print '</pre>'; */

	return $mat;
}