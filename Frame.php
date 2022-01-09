<?php

class Frame{
	/**
	 * Decodes the frame from WebSocket data
	 *
	 * @param string $text
	 * @return array
	 */
	public static function decode(string $text){
		if(strlen($text) === 0) return false;

		$length = ord($text[1]) & 127;
		$op_code = ord($text[0]) & 15;

		if($length < 126){
			$mask = 2;
		}
		elseif($length === 126){
			$mask = 4;
			$length = hexdec(unpack('H*', substr($text, 2, 2))[1]);
		}
		elseif($length === 127){
			$mask = 10;
			$length = hexdec(unpack('H*', substr($text, 2, 8))[1]);
		}

		$mask_data = substr($text, $mask, 4);
		$payload = substr($text, $mask + 4);
		$data = '';

		for($i = 0; $i < strlen($payload); $i++){
			$data.= $payload[$i] ^ $mask_data[$i % 4];
		}

		$code = null;
		$reason = null;
		if($op_code === 8){
			$code = unpack('n*', substr($data, 0, 2))[1];
			$reason = substr($data, 2);
		}

		return [
			'fin'=>ord($text[0]) & 128 === 128,
			'rsv'=>[
				ord($text[0]) & 64 === 64,
				ord($text[0]) & 32 === 32,
				ord($text[0]) & 16 === 16,
			],
			'op_code'=>$op_code,
			'mask'=>ord($text[1]) & 128 === 128,
			'length'=>ord($text[1]) & 127,
			'mask_key'=>substr($text, $mask, 4),
			'data'=>$data,
			'code'=>$code,
			'reason'=>$reason
		];
	}
	
	/**
	 * Encode text into WebSocket data
	 *
	 * @param string|array $text
	 * @param array $config
	 * @return string
	 */
	public static function encode(string|array $text, array $config = ['op_code'=>1, 'rsv'=>[0,0,0], 'fin'=>1]){
		$config = array_merge(['op_code'=>1, 'rsv'=>[0,0,0], 'fin'=>1], $config);
		$header = $config['fin'] * 128 + 
		$config['rsv'][0] * 64 + 
		$config['rsv'][1] * 32 + 
		$config['rsv'][2] * 16 + 
		$config['op_code'];
		$length = strlen($text);

		if($length < 126){
			$header = pack('CC', $header, $length);
		}
		elseif($length <= 65535){
			$header = pack('CCn', $header, 126, $length);
		}
		else{
			$header = pack('CCJ', $header, 127, $length);
		}

		return $header.$text;
	}

	/**
	 * Write close frame and return as string
	 *
	 * @param int $code
	 * @param string $reason
	 * @return string
	 */
	public static function close(int $code, string $reason = ''){
		return Frame::encode(hex2bin(str_pad(dechex($code), 4, '0', STR_PAD_LEFT)).$reason, ['op_code'=>8]);
	}
}