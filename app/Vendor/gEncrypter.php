<?php
class gEncrypter
{
	var $key = 'domiCiliosBogota10202';
	function encrypt($string) {
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->key), $string, MCRYPT_MODE_CBC, md5(md5($this->key))));
	}
	function decrypt($string) {
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($this->key))), "\0");
	}
}