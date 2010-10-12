<?
	
	function array_to_dropdown($options,$default="",$id="",$name="",$style="",$class="") {
		$html = '<select '.($id!==""?'id="'.$id.'" ':'').($name!==""?'name="'.$name.'" ':'').($style!==""?'style="'.$style.'" ':'').($class!==""?'style="'.$style.'"':'').'>';
		foreach($options as $i => $o) {
			$html .= '<option value="'.$i.'" '.($default==$i?'selected="selected"':'').'>'.$o.'</option>';
		}
		return $html.'</select>';
	}

?>