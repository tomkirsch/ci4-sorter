<?php

namespace Tomkirsch\Sorter;

/**
 * Controls sorting in tables.
 * Usage: 
 * $sorter = \Config::sorter(['categories'=>'category_ordernum asc']);
 * $list = $model->orderBy($sorter->getSort())->findAll();
 * 
 * Quick Tables can easily spit out the sortable columns in your views:
 * $qt = $sorter->quickTable('categories')->addCol('category_name', 'Category');
 * print $qt->table();
 */

class Sorter
{
	const GET_SORT_KEY = 'sort';
	const GET_FIELD_KEY = 'field';
	const GET_DIR_KEY = 'dir';

	/**
	 * @var SorterConfig
	 */
	public $config;

	/**
	 * @var string The base URL to use for sortable links. Defaults to current_url()
	 */
	protected $url;

	/**
	 * @var array The current sorts (read in request)
	 */
	protected $currentSort;

	/**
	 * @var array All GET data
	 */
	protected $currentGetArray;

	/**
	 * @var array List of tables for the current request
	 */
	protected $tables = [];

	/**
	 * Pass an assoc array of tables, with values being the default field and sort dir
	 * @param array $tables Ex: ['categories'=>'category_ordernum asc']
	 * @param null|string $url Defaults to current_url()
	 * @param null|SorterConfig $config
	 */
	public function __construct(array $tables = [], ?string $url = NULL, ?SorterConfig $config = NULL)
	{
		$this->config = $config ?? new SorterConfig();
		$this->url = $url ?? current_url();
		$request = service('request');
		$this->currentGetArray = $request->getGet() ?? [];
		$this->currentSort = $request->getGet(static::GET_SORT_KEY) ?? [];
		$this->setTables($tables);
	}

	/**
	 * Gets the QuickTable instance for the given table name
	 * @param null|string $tableName If no table is passed, it will use the first one.
	 */
	public function quickTable(?string $tableName = NULL): QuickTable
	{
		$tableName = $tableName ?? $this->tableNames()[0];
		$qt = new QuickTable($this, $tableName);
		return $qt;
	}

	/**
	 * Sets the base url for sorting links
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * Add a table to the list
	 */
	public function addTable(string $table, ?string $defaultField = NULL, ?string $defaultDir = NULL)
	{
		if (isset($this->tables[$table])) throw new \Exception("Table [$table] has already been defined.");
		$parts = $defaultField ? explode(' ', $defaultField) : [];
		$this->setTable($table, $parts[0] ?? NULL, $defaultDir ?? $parts[1] ?? NULL);
	}

	/**
	 * See constructor
	 */
	public function setTables(array $tables)
	{
		foreach ($tables as $table => $data) {
			$parts = explode(' ', $data);
			$this->setTable($table, $parts[0] ?? NULL, $parts[1] ?? NULL);
		}
		return $this;
	}

	/**
	 * Sets properties for the given table
	 */
	public function setTable(string $table, ?string $defaultField = NULL, ?string $defaultDir = NULL)
	{
		$this->tables[$table] = new SorterTable($table, [
			'defaultField' => $defaultField,
			'defaultDir' => $defaultDir,
			'currentField' => $this->currentSort[$table][static::GET_FIELD_KEY] ?? NULL,
			'currentDir' => $this->currentSort[$table][static::GET_DIR_KEY] ?? NULL,
		]);
		return $this;
	}

	/**
	 * Get table names that have been defined
	 */
	public function tableNames(): array
	{
		return array_keys($this->tables);
	}

	/**
	 * Gets sort string for sql
	 */
	public function getSort(?string $tableName = NULL): string
	{
		$table = $tableName ? $this->tables[$tableName] : reset($this->tables);
		return $table->getSort();
	}

	/**
	 * Gets the current direction for a field, or NULL if it isn't being sorted by this field
	 */
	public function currentDir(string $field, ?string $tableName = NULL): ?string
	{
		$table = $tableName ? $this->tables[$tableName] : reset($this->tables);
		$parts = explode(' ', $field);
		$field = $parts[0] ?? $field;
		return ($table->currentField === $field) ? $table->dir() : NULL;
	}

	/**
	 * Anchor + icon html
	 */
	public function anchorIcon(string $field, string $content, $attr = '', ?string $table = NULL, bool $mergeCurrentGet = TRUE, array $params = []): string
	{
		return $this->anchor($field, $content . ' ' . $this->icon($field, $table), $attr, $table, $mergeCurrentGet, $params);
	}

	/**
	 * Icon html
	 */
	public function icon(string $field, ?string $table = NULL): ?string
	{
		$dir = $this->currentDir($field, $table);
		if (!$dir) return '';
		return '<i class="fa fa-sort-' . $dir . '"></i>';
	}

	/**
	 * Anchor html
	 */
	public function anchor(string $field, string $content, $attr = '', ?string $table = NULL, bool $mergeCurrentGet = TRUE, array $params = []): string
	{
		return anchor($this->url($field, $table, $mergeCurrentGet, $params), $content, $attr);
	}

	/**
	 * Generate a url for sorting links
	 */
	public function url(string $field, ?string $table = NULL, bool $mergeCurrentGet = TRUE, array $params = []): string
	{
		$q = stristr($this->url, '?') === FALSE ? '?' : '&';
		$q .= $this->queryString($field, $table, $mergeCurrentGet, $params);
		return $this->url . $q;
	}

	/**
	 * utility - run http_build_query()
	 */
	public function queryString(string $field, ?string $table = NULL, bool $mergeCurrentGet = TRUE, array $params = []): string
	{
		$ar = $this->queryArray($field, $table);
		if ($mergeCurrentGet) {
			$ar = array_merge($this->currentGetArray, $ar);
		}
		$ar = array_merge($ar, $params);
		return http_build_query($ar);
	}

	/**
	 * utility - get sort array for GET
	 */
	public function queryArray(string $field, ?string $tableName = NULL): array
	{
		$table = $tableName ? $this->tables[$tableName] : reset($this->tables);
		$parts = explode(' ', $field);
		$field = $parts[0] ?? $field;
		$dir = $parts[1] ?? $table->dir();
		if ($table->currentField === $field) {
			$dir = ($table->currentDir === 'asc') ? 'desc' : 'asc';
		}
		return [
			static::GET_SORT_KEY => [
				$table->id => [
					static::GET_FIELD_KEY => $field,
					static::GET_DIR_KEY => $dir,
				],
			]
		];
	}
}

/**
 * A container for sorter tables
 */
class SorterTable
{
	public $id;
	public $defaultField;
	public $defaultDir;
	public $currentField;
	public $currentDir;

	public function __construct(string $id, $options)
	{
		$this->id = $id;
		foreach ($options as $key => $val) {
			if (property_exists($this, $key)) {
				$this->{$key} = $val;
			}
		}
	}
	/**
	 * get sort string for SQL
	 */
	public function getSort(): string
	{
		$field = $this->field();
		if (!$field) return '';
		$dir = $this->dir();
		$dir = $dir ? ' ' . $dir : '';
		return $field . $dir;
	}
	public function field(): ?string
	{
		return $this->currentField ?? $this->defaultField ?? NULL;
	}
	public function dir(): ?string
	{
		return $this->currentDir ?? $this->defaultDir ?? 'asc';
	}
}
