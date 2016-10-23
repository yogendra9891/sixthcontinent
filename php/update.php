<?php
	/* Format print_r */
	function p($array) {
		echo '<pre>';
		print_r($array);
		echo '</pre>';
	}

	/* Database Connection */
	//mysql_connect('dbsixthcontinent.cxpwipjz0okc.eu-west-1.rds.amazonaws.com', 'usixthcontinent', 'psixthcontinent');
	mysql_connect('localhost', 'root', '');
	mysql_select_db('sixthcondb_dev');

	/* Fetch Data From Transaction */
	$sql = mysql_query('SELECT * FROM `Transaction` WHERE `status` = "COMPLETED"');   
	while($row = mysql_fetch_assoc($sql)){
	    $Transactions[] = $row;
	}
	//p($Transactions);

	/* Update total_revenue in WalletBusiness */
	if(!empty($Transactions)) {
		foreach($Transactions as $val) {
			$revenue = $val['final_price'] + $val['citizen_income_used'];
			//echo 'Shop Id: '.$val['seller_id'].' Revenue Added: '.$revenue.'<br/>';
			$query = 'UPDATE WalletBusiness 
					  SET 
					  		`total_revenue` = `total_revenue` + '.$revenue.'
					  WHERE 
					  		seller_id = '.$val["seller_id"].'
			  		';
			mysql_query($query);
		}
	}
?>