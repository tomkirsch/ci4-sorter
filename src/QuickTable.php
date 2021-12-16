<?php namespace Tomkirsch\Sorter;
/*
	Quickly output <table> html for a database result.
	Example: 
	
<?php
$qt = new QuickTable();
// define your columns and how you'd like the values to be formatted
$qt
	->addCol('customer_id', 'Customer Number')
	
	// variable substitution
	->addCol('customer_address', 'Address', 'asc', '$customer_address<br>$customer_city')
	
	// typical formats
	->addCol('balance', 	'Balance', 		'desc', 'money')
	->addCol('widgetcount', '# of Widgets', 'desc', 'number')
	->addCol('ph_level', 	'pH', 			'desc', 'number_1') // 1 decimal place
	->addCol('created', 	'Created', 		'desc', 'datetime') 
	->addCol('thetime', 	'Time', 		'desc', 'time')
	->addCol('schedule', 	'Sched Date', 	'desc', 'dateFormat_m/d') // custom date format
	
	// custom function (most flexible - however you MUST return TD tags)
	->addCol('customer_iscash', 'Cash', 'desc', function($value, $row){ 
		return '<td class="bg-success">'.($value ? 'Yes' : 'No').'</td>'; 
	}) 
;
?>
<table class="table">
	<?= $qt->thead() ?>
	<?= $qt->tbody($customers) ?>
</table>
*/

use CodeIgniter\I18n\Time;

class QuickTable{
	protected $currentTable;
	protected $sorter;
	protected $cols = [];
	
	public function __construct(?Sorter $sorter){
		$this->sorter = $sorter ?? service('sorter');
	}
	
	public function setTable(string $tableName){
		$this->currentTable = $tableName;
		return $this;
	}
	
	public function addCol(?string $field, string $label, ?string $defaultSort='asc', $template=NULL){
		$this->currentTable = $this->currentTable ?? reset($this->sorter->tableNames());
		$qc = new QuickCol();
		$qc->table = $tthis->currentTable;
		$qc->field = $field;
		$qc->defaultSort = $defaultSort;
		$qc->template = $template;
		$this->cols[] = $qc;
		return $this;
	}
	
	public function thead(?string $tableName=NULL):string{
		$out = '<thead>';
		foreach($this->cols as $col){
			$out .= "<tr>".$col->th()."</tr>";
		}
		$out .= '</thead>';
		return $out;
	}
	public function tbody(array $data, ?string $tableName=NULL):string{
		$out = '<tbody>';
		foreach($data as $row){
			$out .= $this->tr($row);
		}
		$out .= '</tbody>';
		return $out;
	}
	public function tr($row, ?string $idField=NULL, ?string $tableName=NULL):string{		
		if(is_array($row)) $row = (object) $row;
		$dataAttr = $idField ? 'data-'.$idField.'="'.esc($row->$idField, 'attr').'"' : '';
		$out = "<tr $dataAttr>";
		foreach($this->cols as $col){
			$out .= $col->td($row);
		}
		$out .= '</tr>';
		return $out;
	}
}

class QuickCol{
	public $table;
	public $field;
	public $label;
	public $defaultSort;
	public $template;
	
	public function th():string{
		$dataAttr = $this->field ? " data-quickcol-th=\"$this->table.$this->field\"" : '';
		$out = "<th$dataAttr>";
		if($this->field){
			$out .= $this->sorter->anchorIcon("$this->field $this->defaultSort", $this->label);
		}else{
			$out .= $this->label;
		}
		$out .= '</th>';
		return $out;
	}
	public function td($row):string{
		if(is_array($row)) $row = (object) $row;
		$dataAttr = $this->field ? " data-quickcol-td=\"$this->table.$this->field\"" : '';
		$out = "<td$dataAttr>";
		$value = $this->field ? $row->$this->field : NULL;
		
		// if template is callable, use that for the <td> tags as well
		if(is_callable($this->template)){
			$out = $this->template($value, $row); // a function template should also return the tags
			return $out; // that's all, leave the method!
		}
		
		// look for predefined templates
		$formattedValue = $this->formatTemplate($this->template, $value);
		if($formattedValue === NULL){
			// no template found
			// look for $ variables
			if(is_string($this->template)){
				if($this->field && preg_match_all('/(\$[a-z_]+)/', $this->field, $matches)){
					$trans = [];
					foreach($matches[0] as $var){
						$nodollarVar = substr($var, 1);
						$trans[$var] = $row->$nodollarVar; // '$customer_name' => $row->customer_name
					}
					$formattedValue = strtr($this->template, $trans);
				}else{
					$formattedValue = $this->template;
				}
			}else{
				// not callable and not a string... use the value if not null
				$formattedValue = $value ?? '';
			}
		}
		
		$out .= $formattedValue.'</td>';
		return $out;
	}
	
	// pass an argument with underscore (ex: 'dateFormat_l, fS Y')
	// returns NULL only when no predefined template was found
	protected function formatTemplate(string $templateName, $value):?string{
		$argument = NULL;
		if($pos = strpos($templateName, '_')){
			$argument = substr($templateName, $pos + 1);
			$templateName = substr($templateName, 0, $pos);
		}
		switch($this->template){
			case 'number':
				if($argument !== NULL){
					return ($value === NULL) ? '' : number_format($value, intval($argument));
				}
				return ($value === NULL) ? '' : number_format($value);
			case 'money': 
				if($value === NULL) return '';
				$s = '$'.number_format(abs($value), 2);
				return (floatval($value) < 0) ? "($s)" : $s;
			case 'date':
				return ($value === NULL) ? '' : $this->ensureDate($value)->format('m/d/Y');
			case 'datetime':
				return ($value === NULL) ? '' : $this->ensureDate($value)->format('m/d/Y h:iA');
			case 'time':
				return ($value === NULL) ? '' : $this->ensureDate($value)->format('h:iA');
			case 'dateFormat':
				return ($value === NULL) ? '' : $this->ensureDate($value)->format($argument);
			default: 
				return NULL; // no template found
		}
	}
	
	protected function ensureDate($value){
		if(!is_a('CodeIgniter\I18n\Time') && !is_a($value, 'DateTime')){
			$value = new DateTime($value);
		}
		return $value;
	}
}