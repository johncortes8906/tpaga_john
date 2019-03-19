<?php 
	function lzw_compress($uncompressed) {
        $dictSize = 256;
        $dictionary = array();
        for ($i = 0; $i < 256; $i++) {
            $dictionary[chr($i)] = $i;
        }
        $w = "";
        $result = "";
        for ($i = 0; $i < strlen($uncompressed); $i++) {
            $c = charAt($uncompressed, $i);
            $wc = $w.$c;
            if (isset($dictionary[$wc])) {
                $w = $wc;
            } else {
                if ($result != "") {
                    $result .= ",".$dictionary[$w];
                } else {
                    $result .= $dictionary[$w];
                }
                $dictionary[$wc] = $dictSize++;
                $w = "".$c;
            }
        }
        if ($w != "") {
            if ($result != "") {
                $result .= ",".$dictionary[$w];
            } else {
                $result .= $dictionary[$w];
            }
        }
        return $result;
    }
    function lzw_decompress($compressed) {
        $compressed = explode(",", $compressed);
        $dictSize = 256;
        $dictionary = array();
        for ($i = 1; $i < 256; $i++) {
            $dictionary[$i] = chr($i);
        }
        $w = chr($compressed[0]);
        $result = $w;
        for ($i = 1; $i < count($compressed); $i++) {
            $entry = "";
            $k = $compressed[$i];
            if (isset($dictionary[$k])) {
                $entry = $dictionary[$k];
            } else if ($k == $dictSize) {
                $entry = $w.charAt($w, 0);
            } else {
                return null;
            }
            $result .= $entry;
            $dictionary[$dictSize++] = $w.charAt($entry, 0);
            $w = $entry;
        }
        return $result;
    }
    function charAt($string, $index){
        if($index < mb_strlen($string)){
            return mb_substr($string, $index, 1);
        } else{
            return -1;
        }
    }