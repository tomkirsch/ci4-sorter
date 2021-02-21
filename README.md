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
	model('MyModel')->orderBy($sorter->getSort())->findAll();
	
	// if you have more than one table, you'll want to pass the name
	model('MyModel')->orderBy($sorter->getSort('foo'))->findAll();
```
In your view, build links in your view using anchorIcon(), anchor(), url(), queryString() or queryArray()
```
	// if you don't pass the sorter in the view, you'll need to get it via service
	<?php $sorter = service('Sorter'); ?>
	
	<th><?= $sorter->anchorIcon('category_ordernum desc', 'Order') ?></th>
	
	// or if you have more than one table:
	<th><?= $sorter->anchorIcon('category_ordernum desc', 'Order', '', 'cat') ?></th>
```
Look at these methods in the class for more usage (GET parameters, HTML attributes, etc)