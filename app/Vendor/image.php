<?php
    class ImageEditor {
    	
		//Recorta a area de uma imagem(jpg, jpeg, gif, png)
        function imageZoomCrop($imgOri, $zoom, $cropw ,$croph, $x, $y, $quality = 100){
            $savefns = array('jpg' => 'imagejpeg', 'png' => 'imagepng', 'gif' => 'imagegif');
			$createfns = array('jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'gif' => 'imagecreatefromgif');
			
            list($width, $height) = getimagesize($imgOri);
			$ext     = pathinfo($imgOri, PATHINFO_EXTENSION);
			
			$create = $createfns[$ext];
			$image = $create($imgOri);
			
			$save = $savefns[$ext];
			
			if($zoom == 100) {
				// Solo hacer el crop
				$portion = imagecreatetruecolor($cropw, $croph);
				imagecopyresampled($portion, $image, 0, 0, $x, $y, $cropw, $croph, $width, $height);
			} else {
				$ratio = $cropw / $croph;
				
				$cropw_real = 100 * $cropw / $zoom;
				$croph_real = $cropw_real / $ratio;
				
				$x = (100 * $x / $zoom);
				$y = (100 * $y / $zoom);
				
				$min = imagecreatetruecolor($cropw_real, $croph_real);
				imagecopyresampled($min, $image, 0, 0, $x, $y, $cropw_real, $croph_real, $cropw_real, $croph_real);
            	// Resize al 100%
            	$portion = imagecreatetruecolor($cropw, $croph);
				imagecopyresampled($portion, $min, 0, 0, 0, 0, $cropw, $croph, $cropw_real, $croph_real);
				imagedestroy($min);
			} 
			
			$save($portion, $imgOri, $quality);
			
			imagedestroy($image);
			imagedestroy($portion);
        }

        function imageResize($imgOri, $width, $height = null, $quality = 100){
            $savefns = array('jpg' => 'imagejpeg', 'png' => 'imagepng', 'gif' => 'imagegif');
			$createfns = array('jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'gif' => 'imagecreatefromgif');
			
            list($ori_width, $ori_height) = getimagesize($imgOri);
			$ext     = pathinfo($imgOri, PATHINFO_EXTENSION);
			
			$create = $createfns[$ext];
			$image = $create($imgOri);
			
			if($height == null) {
				$ratio = $ori_width / $ori_height;
				$height = $width / $ratio;
			}
			
			$resized = imagecreatetruecolor($width, $height);
			imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $ori_width, $ori_height);
			
			$save = $savefns[$ext];
			$save($resized, $imgOri, $quality);
			
			imagedestroy($image);
			imagedestroy($resized);
        }

		function imageRotate($imgOri, $angle, $quality = 80) {
			$ext = pathinfo($imgOri, PATHINFO_EXTENSION);
			
			//De acordo com o formato da imagem, cria um thumb
            if( $ext == 'jpg' || $ext == 'jpeg' ){
                $image = imagecreatefromjpeg($imgOri);
				imagerotate($image, $angle);
               	imagejpeg($image, $imgOri, $quality);
				imagedestroy($image);
				return true;
            } elseif( $ext == 'gif' ) {
                $image = imagecreatefromgif($imgOri);
				imagerotate($image, $angle);
                imagegif($image, $imgOri, $quality);
				return true;
            } elseif( $ext == 'png' ) {
                $image = imagecreatefrompng($imgOri);
				imagerotate($image, $angle);
                imagepng($image, $imgOri, $quality);
				return true;
            }
			
            return false;
			
		}
		
		function imageMerge($imgOri, $imgMerge) {
			$ext = pathinfo($imgOri, PATHINFO_EXTENSION);
			
			list($width, $height) = getimagesize($imgOri);
			
			$image = imagecreatefromstring(file_get_contents($imgOri));
 			$frame = imagecreatefromstring(file_get_contents($imgMerge));
			
			imagecopymerge($image, $frame, 0, 0, 0, 0, $width, $height, 100);
			
			//De acordo com o formato da imagem, cria um thumb
            if( $ext == 'jpg' || $ext == 'jpeg' ){
                imagejpeg($image, $imgOri, 80);
				imagedestroy($image);
				imagedestroy($frame);
				return true;
            } elseif( $ext == 'gif' ) {
                imagegif($image, $imgOri, 80);
				imagedestroy($image);
				imagedestroy($frame);
				return true;
            } elseif( $ext == 'png' ) {
                imagepng($image, $imgOri, 80);
				imagedestroy($image);
				imagedestroy($frame);
				return true;
            }
			
            return false;
		}
    }
    
?>