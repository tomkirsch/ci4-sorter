<?php

namespace Tomkirsch\Sorter;

use CodeIgniter\I18n\Time;
use Closure;

/**
 * Quickly output <table> html for a database result; most methods are chainable. 
 * Use Sorter->quickTable() to instantiate.
 */

class QuickTable
{
	protected $currentTable;
	protected $cols = [];
	protected $rowTemplate;
	protected $sorter;

	public function __construct(Sorter $sorter, string $tableName)
	{
		$this->sorter = $sorter;
		$this->currentTable = $tableName;
	}

	/**
	 * Add a column to the table. When passing a closure as $template, you MUST send the <td> and </td> tags in the returned string!
	 * @param null|string $field The database field to sort by. Passing NULL will prevent sorting by this field.
	 * @param string $label The <th> label
	 * @param null|string $defaultSort The default sort direction ('asc' or 'desc')
	 * @param null|string|Closure $template String values: yesno|number|money|balance|date|datetime|time|dateFormat_xxx, or pass an anon function with <td> and </td> tags in the returned string
	 */
	public function addCol(?string $field, string $label, ?string $defaultSort = 'asc', $template = NULL)
	{
		$qc = new QuickCol($this->sorter->config);
		$qc->table = $this->currentTable;
		$qc->field = $field;
		$qc->defaultSort = $defaultSort;
		$qc->template = $template;
		$qc->label = $field ? $this->sorter->anchorIcon("$field $defaultSort", $label) : $label;

		$this->cols[] = $qc;
		return $this;
	}

	/**
	 * Pass anon function to customize the opening <tr> tag.
	 * @param null|Closure $template You must return the opening <tr> tag only!
	 */
	public function rowTemplate($template = NULL)
	{
		$this->rowTemplate = $template;
		return $this;
	}

	/**
	 * Output the <table>
	 */
	public function table(array $data, string $attr = ''): string
	{
		return "<table $attr>" . $this->thead() . $this->tbody($data) . "</table>";
	}
	/**
	 * Output the <thead> and <tr>
	 */
	public function thead(): string
	{
		$out = '<thead><tr>';
		foreach ($this->cols as $col) {
			$out .= $col->th();
		}
		$out .= '</tr></thead>';
		return $out;
	}
	/**
	 * Output the <tbody>
	 */
	public function tbody(array $data): string
	{
		$out = '<tbody>';
		foreach ($data as $row) {
			$out .= $this->tr($row);
		}
		$out .= '</tbody>';
		return $out;
	}
	/**
	 * Output the <tr>
	 */
	public function tr($row, ?string $idField = NULL): string
	{
		if (is_array($row)) $row = (object) $row;
		// is there a row template? then use it
		if (is_object($this->rowTemplate) && ($this->rowTemplate instanceof Closure)) {
			// note we ditch $out
			$closure = $this->rowTemplate; // we must place it in a variable to call it
			$out = $closure($row);
		} else {
			$dataAttr = $idField ? 'data-' . $idField . '="' . esc($row->$idField, 'attr') . '"' : '';
			$out = "<tr $dataAttr>";
		}
		foreach ($this->cols as $col) {
			$out .= $col->td($row);
		}
		$out .= '</tr>';
		return $out;
	}
}

/**
 * This class represents a field/column in the QuickTable
 */
class QuickCol
{
	public $table;
	public $field;
	public $label;
	public $defaultSort;
	public $template;
	public $config;

	public function __construct(SorterConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * <th> tag
	 */
	public function th(): string
	{
		$dataAttr = $this->field ? " data-quickcol-th=\"$this->table.$this->field\"" : '';
		$out = "<th$dataAttr>$this->label</th>";
		return $out;
	}

	/**
	 * <td> tag
	 * @param array|object $row
	 */
	public function td($row): string
	{
		if (is_array($row)) $row = (object) $row;
		$dataAttr = $this->field ? " data-quickcol-td=\"$this->table.$this->field\"" : '';
		$out = "<td$dataAttr>";
		$value = $this->field ? $row->{$this->field} : NULL;

		// look for predefined templates first
		$formattedValue = is_string($this->template) ? $this->formatTemplate($this->template, $value, $row) : NULL;
		if ($formattedValue !== NULL) {
			// all done
			return $out . $formattedValue . '</td>';
		}

		// is template a closure?
		if (is_object($this->template) && ($this->template instanceof Closure)) {
			// closure... we pass the entire $row as second arg
			// note we ditch $out
			$closure = $this->template; // we must place it in a variable to call it
			return $closure($value, $row);
			// is template a callable function?
		} else if (is_string($this->template) && function_exists($this->template)) {
			// something like ucwords()...
			return $out . call_user_func($this->template, $value) . '</td>';
		}

		// look for $ variables
		if (is_string($this->template)) {
			if (preg_match_all('/(\$[a-z_]+)/', $this->template, $matches)) {
				$trans = [];
				foreach ($matches[0] as $var) {
					$nodollarVar = substr($var, 1);
					$trans[$var] = $row->$nodollarVar; // '$customer_name' => $row->customer_name
				}
				$formattedValue = strtr($this->template, $trans);
			} else {
				$formattedValue = $this->template;
			}
		} else {
			// not a string... use the value if not null
			$formattedValue = $value ?? '';
		}

		$out .= $formattedValue . '</td>';
		return $out;
	}

	/**
	 * Pass arguments with underscore: (ex: 'dateFormat_l, fS Y')
	 * Returns NULL only when no predefined template was found
	 */
	protected function formatTemplate(string $templateName, $value, $row): ?string
	{
		$arguments = [];
		if (stristr($templateName, '_')) {
			$parts = explode("_", $templateName);
			$templateName = array_shift($parts);
			$arguments = $parts;
		}
		switch ($templateName) {
			case 'yesno':
				if ($value === NULL) return '';
				return (bool) $value ? 'Yes' : 'No';
			case 'number':
				dd(...$arguments);
				return ($value === NULL) ? '' : number_format($value, ...$arguments);
			case 'money':
				if ($value === NULL) return '';
				return '$' . number_format($value, 2);
			case 'balance':
				if ($value === NULL) return '';
				$s = '$' . number_format(abs($value), 2);
				return (floatval($value) < 0) ? "($s)" : $s;
			case 'date':
				return ($value === NULL) ? '' : $this->ensureDate($value, $row)->format('m/d/Y');
			case 'datetime':
				return ($value === NULL) ? '' : $this->ensureDate($value, $row)->format('m/d/Y h:iA');
			case 'time':
				return ($value === NULL) ? '' : $this->ensureDate($value, $row)->format('h:iA');
			case 'dateFormat':
				return ($value === NULL) ? '' : $this->ensureDate($value, $row)->format(...$arguments);
			default:
				return NULL; // no template found
		}
	}

	/**
	 * Ensures a value is a Time instance for format() function.
	 * You can write your own getDate() function in the SorterConfig to adjust for Time Zone, etc.
	 */
	protected function ensureDate($value, $row): Time
	{
		if (method_exists($this->config, "getDate")) {
			return $this->config->getDate($value, $this->field, $row);
		} else {
			return is_a($value, '\CodeIgniter\I18n\Time') ? $value : new Time($value);
		}
	}
}
