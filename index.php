<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Equipment-Product Mapping</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/ink/3.1.9/css/ink-flex.min.css"> -->
	<link rel="stylesheet" href="css/ink.min.css" type="text/css">
	<link rel="stylesheet" href="css/style.css" type="text/css">
	<script type="text/javascript">
		function copyDataToTextArea() {
		   var _data = document.getElementById("data");
		   var _textArea = document.getElementById("copydata");
		   var _selectedData="";

		   for(i=0; i<document.getElementById("data").length; i++) {
			   var _listElement = document.getElementById("data")[i];
			   if(_listElement.selected) {
			      _selectedData = _selectedData + _listElement.value + "\n";
			   }
		   }
		   _textArea.value +=_selectedData;
		}
	</script>
</head>
<body>
<?php 
	/* Sorting DataStructure */
	$productType = json_decode(file_get_contents("json/productType.json"), true);
	$equipmentType = json_decode(file_get_contents("json/equipmentType.json"), true); 
	$productSort = json_decode(file_get_contents("json/productSort.json"), true);
?>
<div class="ink-grid">
	<!-- Input Querying GUI -->
	<h2 class="input-title">Equipment-Product Mapping</h2>
	<div class="column-group horizontal-gutters push-center">
		<form class="ink-form all-30" id="InputForm" method="post" action="<?$actionPage?>">
			<textarea id="copydata" name="product" class="inputArea all-100" ></textarea>
			<input class="ink-button blue all-100" type="Submit" value="Submit">
		</form>
		<select id="data" multiple="multiple" class="all-20" ondblclick="copyDataToTextArea()">
			<?php foreach( $productSort as $index => $product ) { echo '<option>'.$product['productName'].',</option>'; } ?>
		</select>
	</div>
	<div class="column-group push-center all-50">
		<div class="input-hint all-70">1.產品輸入請與Remaining一樣
  若亂輸入會斜體顯示在Remaining
2.數量與名稱用逗點隔開
3.多比輸入請用enter換行
4.可輸入不同比重複產品 數量累加
		</div>
		<div class="input-hint all-30">Example Format:
C1 Dow_Fxx,2 
C2 Hxx(QCT),5
C1 ABF_Fxx,1
		</div>
	</div>

<?php 
	/*  Input Format: 								*/
	/*		[ productName ],[ quantity ]\n   		*/
	
	$Input = explode("\n",$_POST['product']);
	foreach( $Input as $i => $tmp ){ $Input[$i] = explode(',',$tmp); }
	
	$inputPrepared = array();
	foreach( $productSort as $index => $product ) {
		$inputPrepared[$product['productName']]['Quantity'] = 0;
		$inputPrepared[$product['productName']]['rawIndexFromProductSort'] = $index;
	}

	foreach( $Input as $input ) if( $input[1] ) $inputPrepared[$input[0]]['Quantity'] += $input[1];

	foreach( $inputPrepared as $inputProductName => $inputProduct ){
		foreach( $productSort[$inputProduct['rawIndexFromProductSort']]['equipmentSort'] as $equipment ){
			if( $inputPrepared[$inputProductName]['Quantity'] > 0 && $equipmentType[$equipment['rawIndexFromEquipmentType']]['workingStatus'] == '' ){
				$equipmentType[$equipment['rawIndexFromEquipmentType']]['workingStatus'] = $inputProductName ;
				$inputPrepared[$inputProductName]['Quantity'] = $inputPrepared[$inputProductName]['Quantity'] - 1;
			}
		}
	}
?>
	<!-- Remaining Display  -->
	<h3 class="table-title">Remaining</h3>
	<div class="ink-grid push-center">
		<div class="card-left column-group horizontal-gutters">
		<?php 
			$tmpCount = 0;
			foreach( $inputPrepared as $inputProductName => $inputProduct ){
				if( $tmpCount > count( $productType )-1 ){
					echo '<div class="wrong-input all-25 small-33 tiny-50">'.$inputProductName.' : '.$inputProduct['Quantity'].'</div>';
				}else{
					echo '<div class="regular-input all-25 small-33 tiny-50">'.$inputProductName.' : '.$inputProduct['Quantity'].'</div>';
				}
				$tmpCount++;
			}	
		?>
		</div>
	</div>

	<!-- EquipmentStatus Display -->
	<h3 class="table-title">Working Status</h3>
	<div class="ink-grid push-center">
		<div class="card-left column-group horizontal-gutters">
		<?php 
			foreach( $equipmentType as $equipment ){
				echo '<div class="all-25 small-33 tiny-50"><div class="card">';
				echo '<div class="card-title">'.$equipment['EQUIPMENT'].'</div>';
				echo '<div class="card-content">'.($equipment['workingStatus']==''?'<span>empty</span>':$equipment['workingStatus']).'</div></div></div>';
			}
		?>
		</div>
	</div>

	<!-- EquipmentAvailableForProduct  -->
	<h3 class="table-title">Basic Statistic</h3>
	<table class="ink-table bordered">
		<thead>
			<tr>
				<th></th>
				<?php  foreach( $equipmentType as $equipment ){  echo '<th>'.$equipment['EQUIPMENT'].'</th>'; } ?>
				<th>Equipment Sum</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				foreach( $productType as $key => $value){
					echo '<tr><td>'.$value['GROUPNAME'].' '.$value['GROUPNAME_DESCR'].'</td>';
					foreach( $equipmentType as $equipment ){ echo '<td>'.$value[$equipment['EQUIPMENT']].'</td>'; }
					echo '<td>'.$value['SUM'].'</td></tr>';
				}
			?>
			<tr>
				<td>Product Sum</td>
				<?php foreach( $equipmentType as $equipment ){ echo '<td>'.$equipment['SUM'].'</td>'; } ?> 
				<td></td>
			</tr>
			<tr>
				<td>Priority</td> 
				<?php foreach( $equipmentType as $equipment ){ echo '<td>'.$equipment['PRIORITY'].'</td>'; } ?>
				<td></td>
			</tr>
		</tbody>
	</table>

	<!-- Input Order Table  -->
	<h3 class="table-title">Input Order</h3>
	<table class="ink-table bordered">
		<thead>
			<tr>
				<th>Product Name</th>
				<th>Equipment Sum</th>
				<th>Sorted Equipment</th>
			</tr>
		</thead>
		<tbody>	
			<?php
				foreach( $productSort as $value ){
					echo '<tr><td>'.$value['productName'].'</td>';
					echo '<td>'.$value['SUM'].'</td><td class="align-left">';
					foreach( $value['equipmentSort'] as $equipment ){
						echo '<div class="equipment-box"> '.$equipment['equipmentName'].' ('. $equipment['PRIORITY'].') </div> ';
					}
					echo '</td></tr>';
				}
			?>
		</tbody>
	</table> 
</div>		
</body>
<html>