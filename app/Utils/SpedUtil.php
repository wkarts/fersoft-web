<?php

namespace App\Utils;

use Illuminate\Support\Str;

class SpedUtil {

	public function updateOrCreateC190($data, $std){
		// if(sizeof($data) == 0){
		// 	$data[] = $std;
		// 	return $data;
		// }
		$dup = false;
		for($i=0; $i<sizeof($data); $i++){
			if($data[$i]->CST_ICMS == $std->CST_ICMS && $data[$i]->CFOP == $std->CFOP){
				$dup = true;
				$data[$i]->VL_ICMS += $std->VL_ICMS;
				$data[$i]->VL_OPR += $std->VL_OPR;
			}
		}
		if($dup == false){
			array_push($data, $std);
		}
		return $data;

	}
}