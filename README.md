# ci4-sorter

## Installation

Add service in `App\Config\Services`

```
	public static function sorter(array $tables=[], ?string $url=NULL, $getShared=TRUE){
		$sorter = $getShared ? static::getSharedInstance('sorter', $tables, $url) : new \Tomkirsch\Sorter\Sorter($tables, $url);
		return $sorter;
	}
```

## Usage

In controller, you can access it with the service. You can pass an associative array to setup if you'd like.

```
	$sorter = service('Sorter');
	// or pass a configuration to save a step:
	$sorter = service('Sorter', [
		'foo'=>'foo_id asc',
		'bar'=>'bar_id asc',
	]);
```

To add tables, use the addTable() method:

```
	// add your tables. The "table name" is only used in GET parameters, so it doesn't need to match your database
	$sorter->addTable('foo', 'foo_id', 'asc');
	// you can pass the default sort using a space too:
	$sorter->addTable('bar', 'bar_id asc');
```

Just all getSort($tableName) to get the current sort field and direction. If none are passed, it'll use the default you specified above.

```
	// this quick version uses the first table
	$list = model('MyModel')->orderBy($sorter->getSort())->findAll();

	// if you have more than one table, you'll want to pass the name
	$list = model('MyModel')->orderBy($sorter->getSort('foo'))->findAll();
```

In your view, use QuickTable to configure columns, templates, and output the thead and tbody:

```
<?php
$qt = $sorter->quickTable('foo') // pass the table name, or you can omit for only 1 table
	// define your columns and how you'd like the values to be formatted...
	// simple
	->addCol('customer_id', 'Customer Number')

	// template with variable substitution
	->addCol('customer_address', 'Address', 'asc', '$customer_address<br>$customer_city')

	// typical formats
	->addCol('iscash', 		'Cash', 		'desc', 'yesno')
	->addCol('amount', 		'Amount', 		'desc', 'money')
	->addCol('balance', 	'Balance', 		'desc', 'balance') // negative values are shown with parentheses
	->addCol('widgetcount', '# of Widgets', 'desc', 'number')	// number with grouped thousands
	->addCol('ph_level', 	'pH', 			'desc', 'number|1') // 1 decimal place (arguments separated by pipe)
	->addCol('created', 	'Created', 		'desc', 'datetime')
	->addCol('thetime', 	'Time', 		'desc', 'time')
	->addCol('schedule', 	'Sched Date', 	'desc', 'dateFormat|D m/d') // custom date format (arguments separated by pipe)

	// closure (most flexible - however you MUST return TD tags)
	->addCol('customer_iscash', 'Cash', 'desc', function($value, $row){
		return '<td class="bg-success">'.($value ? 'Yes' : 'No').'</td>';
	})

	// you can also define a closure for the opening <tr> tag:
	->rowTemplate(function($row){
		return '<tr data-foobar="'.$row->id.'">';
	})
;
?>
<?= $qt->table($list, 'class="table table-bordered"') ?>
```

For more control, you can also build links in your view using anchorIcon(), anchor(), url(), queryString() or queryArray()

```
	<th><?= $sorter->anchorIcon('category_ordernum desc', 'Order') ?></th>

	// or if you have more than one table:
	<th><?= $sorter->anchorIcon('category_ordernum desc', 'Order', '', 'cat') ?></th>
```

Look at these methods in the class for more usage (GET parameters, HTML attributes, etc)
