<?php namespace Tomkirsch\Sorter;
/*
	Quickly output <table> html for a database result.
*/

use CodeIgniter\I18n\Time;

class QuickTable{
	protected $currentTable;
	protected $cols = [];
	protected $sorter;
	
	public function __construct(Sorter $sorter, string $tableName){
		$this->sorter = $sorter;
		$this->currentTable = $tableName;
	}
	
	public function addCol(?string $field, string $label, ?string $defaultSort='asc', $template=NULL){
		$qc = new QuickCol();
		$qc->table = $this->currentTable;
		$qc->field = $field;
		$qc->defaultSort = $defaultSort;
		$qc->template = $template;
		$this->cols[] = $qc;
		return $this;
	}
	public function table(array $data, string $attr=''):string{
		return "<table $attr>".$this->thead().$this->tbody($data)."</table>";
	}
	public function thead():string{
		$out = '<thead>';
		foreach($this->cols as $col){
			$out .= "<tr>".$col->th()."</tr>";
		}
		$out .= '</thead>';
		return $out;
	}
	public function tbody(array $data):string{
		$out = '<tbody>';
		foreach($data as $row){
			$out .= $this->tr($row);
		}
		$out .= '</tbody>';
		return $out;
	}
	public function tr($row, ?string $idField=NULL):string{		
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
			case 'yesno':
				if($argument === NULL) return '';
				return (bool) $value ? 'Yes' : 'No';
			case 'number':
				if($argument !== NULL){
					return ($value === NULL) ? '' : number_format($value, intval($argument));
				}
				return ($value === NULL) ? '' : number_format($value);
			case 'money': 
				if($value === NULL) return '';
				return '$'.number_format($value, 2);
			case 'balance':
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