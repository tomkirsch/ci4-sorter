<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Sorter</title>
	<meta name="description" content="The small framework with powerful features">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
</head>
<body>
<?php
$qt = $sorter->quickTable() 
	->addCol('image_id', 'ID')
	->addCol('image_sitephoto', 'yesno', 'desc', 'yesno')
	->addCol('numbertest', 'number', 'desc', 'number')
	->addCol('numbertest', 'money', 'desc', 'money')
	->addCol('numbertest', 'balance', 'desc', 'balance')
	->addCol('image_created', 'date', 'asc', 'date')
	->addCol('image_created', 'datetime', 'asc', 'datetime')
	->addCol('image_modified', 'time', 'asc', 'time')
	->addCol('image_modified', 'dateFormat', 'asc', 'dateFormat_l, F d')
	->addCol(NULL, 'variable replacement', NULL, '$image_largestsize | $image_artistrating')
	->addCol('image_body', 'custom function', 'desc', function($value, $row){
		return '<td>'.strlen($value).'</td>';
	})
;
?>
	<?= $pager->links() ?>
	<?= $qt->table($images, 'border="1" cellpadding="4"') ?>
</body>
</html>
