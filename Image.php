<?php

namespace Mirage;

/**
 * Класс для обработки (ресайза) изображений
 *
 * @author n2j7
 * @author Galich Vitaliy
 * @version 0.4
 */
class Image {

	var $file;
	var $error;
	var $size;
	var $format;
	var $outputQuality = 90;
	var $createFuncName;
	var $outputFuncName;

	function __construct($gFile='',$outFunc=''){
		if ($gFile!='') $this->file = $gFile;

		$this->size = getimagesize($this->file);
		if ($this->size === false){
			$this->error = "Can't find source image or read size information";
			return false;
		}

		$this->format = strtolower(substr($this->size['mime'], strpos($this->size['mime'], '/')+1));
		$this->createFuncName = "imagecreatefrom" . $this->format;
		if (!function_exists($this->createFuncName)){
			$this->error = 'Can\'t read image with function: '.$this->createFuncName;
			return false;
		}

		$this->setExtension($outFunc);

		return true;
	}

	function setExtension($ext = "") {
		$ext = ($ext=='jpg') ? "jpeg" : $ext;
		$this->outputFuncName = "image" . (($ext=='') ? $this->format : $ext);
		$this->format = ($ext=='') ? $this->format : $ext;
		if (!function_exists($this->outputFuncName)){
			$this->error = "Can't create image with function: ".$this->outputFuncName;
			return false;
		}
	}

	/**
	 * Обрезает изображение помещая уменьшенную копию в заданный размер.
	 * Оставшееся место заливает цветом.
	 * @param $dest Путь к результату преобразования
	 * @param $width Новая ширина
	 * @param $height Новая высота
	 * @param $rgb Шестнадцатиричный индекс цвета фона заливки
	 * @return unknown_type
	 */
	function fillResize($dest, $width, $height, $rgb=0xFFFFFF){
		if ($this->error!='') exit($this->error);
		$x_ratio = $width / $this->size[0]; // масштаб по х = необходимая ширина / реальный размер по х
		$y_ratio = $height / $this->size[1]; // масштаб по у = необходимая высота / реальный размер по у

		$ratio       = min($x_ratio, $y_ratio); // выбираем минимальный из масштабов чтобы не обрезать изображение
		$use_x_ratio = ($x_ratio == $ratio); // использовать ли масштаб по х: true - использовать, false - использовать по у

		$new_width   = $use_x_ratio  ? $width  : floor($this->size[0] * $ratio); // новая ширина = ширине установленной юзером если используется масштабирование по х или высчитывается в противном случае
		$new_height  = !$use_x_ratio ? $height : floor($this->size[1] * $ratio); // новая высота = высоте установленной юзером если не используется масштабирование по х или высчитывается в противном случае

		$new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2); // новое изображение - по центру контейнера
		$new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2); // новое изображение - по центру контейнера

		$f = $this->createFuncName;
		$isrc = $f($this->file);
		$idest = imagecreatetruecolor($width, $height);
		//imageAlphaBlending($idest, false);

		imagefill($idest, 0, 0, $rgb);
		imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0,$new_width, $new_height, $this->size[0], $this->size[1]);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $dest, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $dest);
		}

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}

	/**
	 * Обрезает изображение сохраняя пропорции исходного файла.
	 * (Вписывает изображение в размеры заданного блока сохраняя пропорции)
	 * @param $dest Путь к результату преобразования
	 * @param $width Новая ширина
	 * @param $height Новая высота
	 * @return unknown_type
	 */
	function limitBoxResize($dest, $width, $height){
		if ($this->error!='') exit($this->error);
		$x_ratio = $width / $this->size[0]; // масштаб по х = необходимая ширина / реальный размер по х
		$y_ratio = $height / $this->size[1]; // масштаб по у = необходимая высота / реальный размер по у

		$ratio       = min($x_ratio, $y_ratio); // выбираем минимальный из масштабов чтобы не обрезать изображение
		$use_x_ratio = ($x_ratio == $ratio); // использовать ли масштаб по х: true - использовать, false - использовать по у

		$new_width   = $use_x_ratio  ? $width  : floor($this->size[0] * $ratio); // новая ширина = ширине установленной юзером если используется масштабирование по х или высчитывается в противном случае
		$new_height  = !$use_x_ratio ? $height : floor($this->size[1] * $ratio); // новая высота = высоте установленной юзером если не используется масштабирование по х или высчитывается в противном случае

		$f = $this->createFuncName;
		$isrc = $f($this->file);
		$idest = imagecreatetruecolor($new_width, $new_height);

		imageAlphaBlending($idest, false);
		imagecopyresampled($idest, $isrc, 0, 0, 0, 0,$new_width, $new_height, $this->size[0], $this->size[1]);
		imageSaveAlpha($idest, true);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $dest, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $dest);
		}

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}

	/**
	 * Обрезает изображение максимально вмещая его в заданный прямоугольник
	 * акцентируя область выделения на центр изображения
	 * @author void
	 * @param $dest
	 * @param $width
	 * @param $height
	 * @return unknown_type
	 */
	function centerResize($dest, $width, $height){
		if ($this->error!='') exit($this->error);
		$a1_a=$width / $this->size[0];
		$b1_b = $height / $this->size[1];

		if($a1_a > $b1_b){
			$new_width = $this->size[0];
			$new_height = $new_width*$height/$width;
			$left=0;
			$top=($this->size[1]-$new_height)/2;
		}
		else{
			$new_height = $this->size[1];
			$new_width = $new_height*$width/$height;
			$left=($this->size[0]-$new_width)/2;
			$top=0;
		}

		$f = $this->createFuncName;
		$isrc = $f($this->file);

		$idest = imagecreatetruecolor($width, $height);

		imageAlphaBlending($idest, false);
		imagecopyresampled($idest, $isrc, 0, 0, $left, $top, $width, $height, $new_width, $new_height);
		imageSaveAlpha($idest, true);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $dest, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $dest);
		}

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}


	/**
	 * Ресайзит картинку таким образом чтобы высота результирующей равнялась заданному значению
	 * а ширина автоматически подогналась с сохранением пропорций оригинала
	 *
	 * @param $dest
	 * @param $height
	 * @return unknown_type
	 */
	function heightRestriction($dest,$height){
		if ($this->error!='') exit($this->error);

		$new_height  = $height; // новая высота = высоте установленной юзером
		$new_width = $this->size[0]*$new_height/$this->size[1]; // расчитываем новую ширину с сохранением пропорций

		$f = $this->createFuncName;
		$isrc = $f($this->file);
		$idest = imagecreatetruecolor($new_width, $new_height);

		imageAlphaBlending($idest, false);
		imagecopyresampled($idest, $isrc, 0, 0, 0, 0,$new_width, $new_height, $this->size[0], $this->size[1]);
		imageSaveAlpha($idest, true);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $dest, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $dest);
		}

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}


	/**
	 * Ресайзит картинку таким образом чтобы ширина результирующей равнялась заданному значению
	 * а высота автоматически подогналась с сохранением пропорций оригинала
	 *
	 * @param $dest
	 * @param $width
	 * @return unknown_type
	 */
	function widthRestriction($dest,$width){
		if ($this->error!='') exit($this->error);

		$new_width = $width;// новая ширина = ширине установленной юзером
		$new_height = $this->size[1]*$new_width/$this->size[0]; // расчитываем новую высоту с сохранением пропорций

		$f = $this->createFuncName;
		$isrc = $f($this->file);
		$idest = imagecreatetruecolor($new_width, $new_height);

		imageAlphaBlending($idest, false);
		imagecopyresampled($idest, $isrc, 0, 0, 0, 0,$new_width, $new_height, $this->size[0], $this->size[1]);
		imageSaveAlpha($idest, true);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $dest, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $dest);
		}

		imagedestroy($isrc);
		imagedestroy($idest);

		return true;
	}

	function waterMark($original, $watermark = 'watermark.png', $placement = 'bottom=10,right=10', $destination = null) {
		$info_o = @getImageSize($original);
		if (!$info_o)
			return false;
		$info_w = @getImageSize($watermark);
		if (!$info_w)
			return false;

		list($vertical, $horizontal) = explode(',', $placement);
		list($vertical, $sy) = explode('=', trim($vertical));
		list($horizontal, $sx) = explode('=', trim($horizontal));

		switch (trim($vertical)) {
			case 'bottom':
				$y = $info_o[1] - $info_w[1] - (int)$sy;
				break;
			case 'middle':
				$y = ceil($info_o[1]/2) - ceil($info_w[1]/2) + (int)$sy;
				break;
			default:
				$y = (int)$sy;
				break;
		}

		switch (trim($horizontal)) {
			case 'right':
				$x = $info_o[0] - $info_w[0] - (int)$sx;
				break;
			case 'center':
				$x = ceil($info_o[0]/2) - ceil($info_w[0]/2) + (int)$sx;
				break;
			default:
				$x = (int)$sx;
				break;
		}

		//header("Content-Type: ".$info_o['mime']);

		$f = $this->createFuncName;
		$isrc = $f($this->file);
		$idest = imagecreatetruecolor($info_o[0], $info_o[1]);

		$iWatermark = @imageCreateFromString(file_get_contents($watermark));
		$iOriginal = @imageCreateFromString(file_get_contents($original));

		imageAlphaBlending($idest, true);
		imageSaveAlpha($idest, true);

		imageCopy($idest, $iOriginal, 0, 0, 0, 0, $info_o[0], $info_o[1]);
		imageCopy($idest, $iWatermark, $x, $y, 0, 0, $info_w[0], $info_w[1]);

		if ($this->format == 'jpeg'){
			$f = $this->outputFuncName;
			$f($idest, $original, $this->outputQuality);
		}
		else{
			$f = $this->outputFuncName;
			$f($idest, $original);
		}

		imagedestroy($iOriginal);
		imagedestroy($idest);
		imageDestroy($iWatermark);

		return true;
	}

}