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

/* 			id=8699 AND  */

	$sql = "SELECT 
			* 
		FROM 
			modx_visioncart_products 
		WHERE 
			active=1 AND 
			parent=0 
		ORDER BY
			id ASC";
	$res = mysqli_query($conn, $sql);
	if(mysqli_query($conn, $sql) === FALSE) {
		my_error($sql);
	}

	echo "Found {$res->num_rows} products<br /><br />";

	$products = array();

	while($row = mysqli_fetch_array($res)) {
		$prod['orig_id'] = $row['id'];

		$prod['publish_date'] = "2012-01-01";
		if(is_new($row['name'])) {
			$prod['publish_date'] = date('Y-m-d');
		}
		
		$prod['name'] = clean_name($row['name']);
		$prod['url_name'] = url_title($prod['name'], 'dash', TRUE);
		$prod['title'] = 'Reyn Spooner - ' . $prod['name'];
		$prod['long_description'] = clean_html($row['description']);
		$prod['sku'] = $row['articlenumber'];
		$prod['price'] = $row['price'];
		$prod['total_units_in_stock'] = 100;
		
		$products[] = $prod;
	}
	mysqli_free_result($res);


	foreach($products as $k => &$v) {
		$sql = "SELECT 
				cats.id, 
				cats.name, 
				cats.parent 
			FROM 
				modx_visioncart_products_categories prodcats 
			LEFT JOIN 
				modx_visioncart_categories cats on cats.id=prodcats.categoryid 
			WHERE 
				productid={$v['orig_id']}
			ORDER BY 
				cats.parent, cats.id
		";
		$res = mysqli_query($conn, $sql);

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

		$v['option_matrix'] = calcOptionMatrix($v);
/* 		print '<pre>';print_r($v);print '</pre>'; */
	}
	
	
	// Insert temp products
	mysqli_query($conn, "TRUNCATE TABLE lemon_product");	

	$sql = "INSERT INTO lemon_product VALUES (
		'',
		'Name',
		'URL Name',
		'Title',
		'Long Description',
		'Short Description',
		'Product Type',
		'Manufacturer',
		'Publish Date',
		'Price',
		'Cost',
		'Enabled',
		'Disable Completely',
		'Tax Class',
		'Price Tiers - Take into account previous orders',
		'On Sale',
		'Sale Price or Discount',
		'SKU',
		'Weight',
		'Width',
		'Height',
		'Depth',
		'Enable per product shipping cost',
		'Shipping cost',
		'Use parent product per product shipping cost settings',
		'Track Inventory',
		'Units In Stock',
		'Total Units In Stock',
		'Allow Negative Stock Values',
		'Hide if Out Of Stock',
		'Out of Stock Threshold',
		'Low Stock Threshold',
		'Expected Availability Date',
		'Allow pre-order',
		'Meta Description',
		'Meta Keywords',
		'Categories',
		'Attribute Name',
		'This Product Description',
		'Options',
		'Extra Options',
		'Global extra option sets',
		'Price Tiers',
		'Files',
		'XML Data',
		'Related Products SKU',
		'Option Matrix Record Flag',
		'Option Matrix - Parent Product SKU',
		'Visible in search results',
		'Visible in the catalog',
		'ATTR: #Shirt Swatch',
		'ATTR: #Size Chart',
		'Product groups'
	)";
	if(mysqli_query($conn, $sql) === FALSE) {
		my_error($sql);
	}

		
	foreach($products as $p):
		$sql = "INSERT INTO lemon_product (
			`orig_id`,
			`Name`,
			`URL Name`,
			`Title`,
			`Long Description`,
			`Product Type`,
			`Publish Date`,
			`Price`,
			`Enabled`,
			`Tax Class`,
			`SKU`,
			`Shipping cost`,
			`Use parent product per product shipping cost settings`,
			`Total Units In Stock`,
			`Categories`,
			`Options`,
			`Visible in search results`,
			`Visible in the catalog`
		) VALUES";

		$sql .= '(';
		$sql .= $p['orig_id'] . ',';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['name']) . '",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['url_name']) . '",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['title']) . '",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['long_description']) . '",';
		$sql .= '"Goods",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['publish_date']) . '",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['price']) . '",';
		$sql .= '1,';
		$sql .= '"Product",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['sku']) . '",';
		$sql .= '"*|*|*|0",';
		$sql .= '1,';
		$sql .= '100,';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['categories']) . '",';
		$sql .= '"' . mysqli_real_escape_string($conn, $p['option_string']) . '",';
		$sql .= '1,';
		$sql .= '1)';
		
		if(mysqli_query($conn, $sql) === FALSE) {
			my_error($sql);
		}


		// Option matrix
		if(count($p['option_matrix'])):
			$sql = "INSERT INTO lemon_product (
				`Price`,
				`Enabled`,
				`Options`,
				`Option Matrix Record Flag`,	
				`Option Matrix - Parent Product SKU`
			) VALUES ";
	
			foreach($p['option_matrix'] as $mat):
				$sql .= '(';

				// Price for this option
				if(isset($mat['price'])):
					$sql .= "\"{$mat['price']}\",";
				else:
					$sql .= '"",';
				endif;

				// Enabled flag
				if(isset($mat['enabled'])):
					$sql .= '1,';
				else:
					$sql .= '"",';
				endif;
	
				// String of options for this matrix row
				$sql .= '"';
				foreach($mat['options'] as $kkkk => $vvvv):
					$sql .= "{$kkkk}: {$vvvv} ";
				endforeach;

				$sql = rtrim($sql, ' ') . '",';

				// Option matrix flag
				$sql .= '1,'; 
				// SKU from parent product
				$sql .= '"' . mysqli_real_escape_string($conn, $p['sku']) . '"),';
			endforeach; // each option_matrix
			
			$sql = rtrim($sql, ',');

			if(mysqli_query($conn, $sql) === FALSE) {
				my_Error($sql);
			}
		endif; // /If option matrix entries

/* 		print '<pre>';print_r($p['long_description']);print '</pre>'; */
	endforeach;

	print '<pre>';print_r($products);print '</pre>';
	
	mysqli_close($conn);



/*********************************************************************************************************************/
/*********************************************************************************************************************/
/*********************************************************************************************************************/

function is_new($str) {
	$search = array(
		'/"NEW"/',
		'/"New"/',
		'/"new"/'
	);
	
	foreach($search as $s):
		if(preg_match($s, $str)) {
			return true;
		}
	endforeach;
	
	return false;
}


function clean_name($name) {
	$name = trim(preg_replace('/"NEW"/', '', $name));
	$name = trim(preg_replace('/"New"/', '', $name));
	$name = trim(preg_replace('/"new"/', '', $name));
	return $name;
}


function clean_html($str) {
	$str = preg_replace('/\r/', ' ', $str);
	$str = preg_replace('/\n/', ' ', $str);
	$str = trim(preg_replace('/\s\s+/', ' ', $str));
	$str = trim(preg_replace('/<br>/', ' ', $str));
	$str = trim(preg_replace('/<BR>/', ' ', $str));
	$str = trim(preg_replace('/<BR \/>/', ' ', $str));
	$str = trim(preg_replace('/<br \/>/', ' ', $str));
	$str = html_entity_decode($str);
	$str = htmlentities(strip_tags($str));
	$str = preg_replace('/\s{2,}/', ' ', $str);
	return $str;
}


function url_title($str, $separator = 'dash', $lowercase = FALSE)
{
    if ($separator == 'dash')
    {
        $search     = '_';
        $replace    = '-';
    }
    else
    {
        $search     = '-';
        $replace    = '_';
    }

    $trans = array(
		'&\#\d+?;'              => '',
		'&\S+?;'                => '',
		'\s+'                   => $replace,
		'[^a-z0-9\-\._]'        => '',
		$replace.'+'            => $replace,
		$replace.'$'            => $replace,
		'^'.$replace            => $replace,
		'\.+$'                  => ''
	);

    $str = strip_tags($str);

    foreach ($trans as $key => $val) {
        $str = preg_replace("#".$key."#i", $val, $str);
    }

    if ($lowercase === TRUE) {
        $str = strtolower($str);
    }

    return trim(stripslashes($str));
}


function calcOptionMatrix(&$product) {
	global $conn, $optionValues;
	$option_matrix = array();

	$pid = $product['orig_id'];

	// Default state
	$product['option_string'] = '';
	
	
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
		return array();
	}
	
	$ids = array();
	while($row = mysqli_fetch_array($res)) {
		$ids[] = $row['id'];
	}
	$child_ids = $ids;
	$ids[] = $pid;
	$ids = implode(',', $ids);
	

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
		return array();
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

	// Build option string for main product entry
	$optstr = '';
	foreach($option_matrix as $k => $v):
		$optstr .= "{$k}: ";
		$tmp = array();

		foreach($v as $val):
			$tmp[] = $val;
		endforeach;
		
		$optstr .= implode('|', $tmp) . ' ';
	endforeach;
	
	$product['option_string'] = rtrim($optstr, ' ');


	// Builed option matrix using sql
	mysqli_query($conn, "TRUNCATE TABLE lemon_optmatrix");

	$sql = "INSERT INTO lemon_optmatrix (name, value) VALUES ";

	foreach($option_matrix as $k => $v):
		foreach($v as $val):
			$sql .= '("' . mysqli_real_escape_string($conn, $k) . '",';
			$sql .= '"' . mysqli_real_escape_string($conn, $val) . '"),';
		endforeach;
	endforeach;

	$sql = rtrim($sql, ',') . ';';

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
	$tmp = array();

/* 	print '<pre>';print_r($res);print '</pre>'; */

	
	while($row = mysqli_fetch_array($res, MYSQLI_NUM)) {
		for($i = 0, $j = $res->field_count; $i < $j; $i += 2):
			 $tmp[$row[$i]] = $row[$i + 1];
		endfor;

		$option_matrix[] = array('options' => $tmp);
	}

/* 	print '<pre>';print_r($option_matrix);print '</pre>'; */
	
	// Now setup enabled and price fields for option matrix
	foreach($child_ids as $cid):
		$sql = "SELECT 
			prod.price, opts.name, optvals.value
			FROM 
				modx_visioncart_products prod
			LEFT JOIN
				modx_visioncart_products_options prodopts ON prodopts.productid=prod.id
			LEFT JOIN
				modx_visioncart_options opts ON opts.id=prodopts.optionid
			LEFT JOIN
				modx_visioncart_options_values optvals ON optvals.id=prodopts.valueid
			WHERE 
				productid={$cid}
		";
		$res = mysqli_query($conn, $sql);
		if($res === FALSE) {
			my_error($sql);
			return array();
		}
	
		$option_prods = array();
		$price = 0;
		while($row = mysqli_fetch_array($res, MYSQLI_NUM)) {
			$option_prods[$row[1]] = $row[2];
			$price = $row[0];
		}

		foreach($option_matrix as &$v):
/* 			print '<pre>';print_r($option_prods);print '</pre>'; */
			if($v['options'] == $option_prods) {
				if($product['price'] != $price) {
					$v['price'] = $price;
				}
				else {
					$v['price'] = '';
				}
				$v['enabled'] = 1;
				break;
			}
		endforeach;

		
/* 		print '<pre>';print_r($option_prods);print '</pre>'; */
	endforeach;

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
		$ret[] = clean_html($row['value']);
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
