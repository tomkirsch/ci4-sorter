<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Sorter</title>
	<meta name="description" content="The small framework with powerful features">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/png" href="/favicon.ico" />
</head>

<body>
	<?php
	$qt = $sorter->quickTable("photos")
		->rowTemplate(function ($row) {
			$style = $row->photo_id % 2 ? 'background-color:#FFEEFF' : '';
			return '<tr style="' . $style . '">';
		})
		->addCol('photo_id', 'ID')
		->addCol('photo_sitephoto', 'yesno', 'desc', 'yesno')
		->addCol('numbertest', 'number', 'desc', 'number')
		->addCol('numbertest', 'money', 'desc', 'money')
		->addCol('numbertest', 'balance', 'desc', 'balance')
		->addCol('photo_created', 'date', 'asc', 'date')
		->addCol('photo_created', 'datetime', 'asc', 'datetime')
		->addCol('photo_modified', 'time', 'asc', 'time')
		->addCol('photo_modified', 'dateFormat', 'asc', 'dateFormat|l, F d')
		->addCol(NULL, 'variable replacement', NULL, '$photo_url | $photo_artistrating')
		->addCol(NULL, 'custom function', NULL, function ($value, $row) {
			return '<td>' . strlen($row->photo_title ?? "") . '</td>';
		});
	?>
	<?= $pager->links() ?>
	<?= $qt->table($photos, 'border="1" cellpadding="4"') ?>
</body>

</html>