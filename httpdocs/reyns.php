<?php

	$conn = mysqli_connect('localhost', 'reyns', 'reyns', 'reyns_modx');
	if(mysqli_connect_errno()) {
		die('Could not connect: ' . mysqli_connect_error());
	}


	$sel = "select * from modx_visioncart_products where active=1 and parent=0 order by id asc";
	$res = mysqli_query($conn, $sel);

	echo "Found {$res->num_rows} products";

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
		$v['categories'] = $cats;
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
		$v['options'] = $opts;
		mysqli_free_result($res);
	}


	print '<pre>';print_r($products);print '</pre>';
	
	mysqli_close($conn);
