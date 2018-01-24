<?php

if (!function_exists('JsonData')) {

	/**
	 * Status Response
	 *
	 * @param string $message
	 * @param int $status
	 * @return \Illuminate\Http\JsonResponse
	 */
	function JsonData($data, $message = 'success', $status = 200)
	{
		return Response()->json([
			'status'  => strval($status),
			'message' => $message,
			'data'    => JSON_NUMERIC_STRING($data),
		], $status, [], 0);
//        return json_encode($data, 0);
	}

    function JSON_NUMERIC_STRING($array){
        foreach($array as $key => $value){
            if(is_array($value)){
                $array[$key] = JSON_NUMERIC_STRING($value);
            }elseif(is_numeric($value)){
                $array[$key] = strval($value);
            }
        }
        return $array;
    }
}