<?php
	include('config.php');

	/************************************/
	/*								   	*/
	/*		Raw data from database  	*/
	/*									*/
	/* 		1. Unique product			*/
	/* 		2. Unique equipment 		*/
	/*									*/
	/************************************/

	/* DB connect */
	$con = mysql_connect("$host:$port", $user, $pwd);
	if (!$con){
		die('Could not connect: ' . mysql_error());
	}
	$db_selected = mysql_select_db($db, $con);

	/* 1. unique product */
	$productType = array();
	$index = 0;
	$sql = "SELECT * FROM $db_table group by `GROUPNAME`,`GROUPNAME DESCR` ORDER BY `GROUPNAME DESCR` ASC";
	$result = mysql_query($sql,$con);
	while($row = mysql_fetch_assoc($result)){
		$productType[$index]['GROUPNAME'] = $row['GROUPNAME'];
		$productType[$index]['GROUPNAME_DESCR'] = $row['GROUPNAME DESCR'];
		$productType[$index++]['SUM'] = 0;
	}
	/* 2. unique equipment */
	$equipmentType = array();
	$index = 0;
	$sql = "SELECT * FROM $db_table group by `EQUIPMENT DESCR` ORDER BY `EQUIPMENT DESCR` ASC";
	$result = mysql_query($sql,$con);
	while($row = mysql_fetch_assoc($result)){
		$equipmentType[$index]['EQUIPMENT'] = $row['EQUIPMENT DESCR'];
		$equipmentType[$index]['PRIORITY'] = 0;
		$equipmentType[$index]['workingStatus'] = '';
		$equipmentType[$index++]['SUM'] = 0;
	}

	/********************************************************************************************/
	/*												  										   	*/
	/*		Statistic Calculate: 		  			  										  	*/
	/*																							*/
	/* 		1. SumOfEquipmentAvailableForProduct 												*/
	/* 		2. productSum => 																	*/
	/*			how many kind of equipment can produce this product  							*/
	/* 		3. equipmentSum => 																	*/
	/*			how many kind of product can the equipment produce  							*/
	/* 		4. equipment peek priority 															*/
	/*			( SumOfEquipmentAvailableForProduct( 1/productSum ))							*/
	/* 		5. input sort for the order of product arrangement 									*/
	/*			( By 1/productSum )																*/
	/* 		6. product inner sort for the order of choosing equipment 							*/
	/*			( By {equipment peek priority} && {EquipmentAvailableForProduct} )				*/
	/* 											  												*/
	/*												 										  	*/
	/********************************************************************************************/
	
	$TmpProductEquipmentMap = array();
	foreach( $productType as $key => $value){
		$GROUPNAME = $value['GROUPNAME'];
		$GROUPNAME_DESCR = $value['GROUPNAME_DESCR'];
		$TmpCount = 0;
		$sql = "SELECT `EQUIPMENT DESCR` FROM $db_table WHERE `GROUPNAME`='$GROUPNAME' AND `GROUPNAME DESCR`='$GROUPNAME_DESCR' ORDER BY `EQUIPMENT DESCR` ASC";
		$result = mysql_query($sql,$con);
		while($row = mysql_fetch_assoc($result)){
			$TmpProductEquipmentMap[$key][$TmpCount++] = $row['EQUIPMENT DESCR'];
		}
		$index = 0;
		foreach( $equipmentType as $equipmentIndex => $equipment ){
			if( $index < count($TmpProductEquipmentMap[$key]) ){
				if( $TmpProductEquipmentMap[$key][$index] == $equipment['EQUIPMENT'] ){
					/* 1. SumOfEquipmentAvailableForProduct  */
					$productType[$key][$equipment['EQUIPMENT']] = 1;  
					/* 2. productSum =>  how many kind of equipment can produce this product */
					$productType[$key]['SUM']++;  
					/* 3. equipmentSum =>	how many kind of product can the equipment produce */
					$equipmentType[$equipmentIndex]['SUM']++;  
					$index++;
				}else{
					$productType[$key][$equipment['EQUIPMENT']] = 0;
				}	
			}else{
				$productType[$key][$equipment['EQUIPMENT']] = 0;
			}
		}
		/* 4. equipment peek priority ( SumOfEquipmentAvailableForProduct( 1/productSum )) */
		foreach( $equipmentType as $equipmentIndex => $equipment ){
			if( $productType[$key][$equipment['EQUIPMENT']] ) 
			$equipmentType[$equipmentIndex]['PRIORITY']+= (int)(100/$productType[$key]['SUM']);
		}		

	}
     
	/*  5. input sort for the order of product arrangement ( By 1/productSum ) */
	$productSort = array();
	$index = 0;
	foreach( $productType as $rawIndexOfproductType => $product ){
		$productSort[$index]['rawIndexFromProductType'] = $rawIndexOfproductType;
		$productSort[$index]['productName'] = $product['GROUPNAME'].' '.$product['GROUPNAME_DESCR'];
		$productSort[$index++]['SUM'] = $product['SUM'];
	}
	usort($productSort, build_sorter('SUM'));

	/* 		6. product inner sort for the order of choosing equipment 					*/
	/*			( By {equipment peek priority} && {EquipmentAvailableForProduct} )		*/

	foreach( $productSort as $productIndex => $product_sort ){
		$productInnerSort = array();
		$index = 0;
		foreach( $equipmentType as $equipmentIndex => $equipment ){
			if( $productType[$product_sort['rawIndexFromProductType']][$equipment['EQUIPMENT']] ){
				$productInnerSort[$index]['rawIndexFromEquipmentType'] = $equipmentIndex;
				$productInnerSort[$index]['equipmentName'] = $equipment['EQUIPMENT'];
				$productInnerSort[$index++]['PRIORITY'] = $equipment['PRIORITY'];
			}
		}
		usort($productInnerSort, build_sorter('PRIORITY'));
		$productSort[$productIndex]['equipmentSort'] = $productInnerSort;
	}
	fputs(fopen("json/equipmentType.json","w"),json_encode($equipmentType));
	fputs(fopen("json/productType.json","w"),json_encode($productType));
	fputs(fopen("json/productSort.json","w"),json_encode($productSort));

	mysql_close($con); 
	
	function build_sorter($key) {
	    return function ($a, $b) use ($key) {
	        return strnatcmp($a[$key], $b[$key]);
	    };
	}
?>