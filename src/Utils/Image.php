<?php

namespace flexycms\FlexyUtilsBundle\Utils;


/*
	imagePath, picturePath - Путь к результурующему файлу от корня сайта
	imageFile, pictureFile - Путь к результурующему файлу от корня файловой системы
*/


class Image
{
	private $fileControlTime = 'mtime';	//Время файла, по которому определяется последнее время модификации
	
	public static $cacheDir = '/public/.cache/';
	
	public $_imagePath;	//Путь к результурующему файлу от корня сайта, имеет синоним picturePath
	
	
	private $sourceFiles = array();	//Список файлов, которые принимали участие в формировании картинки (нужно для работы кэша)
	
	private $actions = array();		//Массив действий, которые надо произвести над изображением
	
	
	public function __get($name)
	{
		if ($name == 'imagePath') return $this->_imagePath;
		if ($name == 'picturePath') return $this->_imagePath;
		if ($name == 'imageFile') return $_SERVER['DOCUMENT_ROOT'] . $this->_imagePath;
		if ($name == 'pictureFile') return $_SERVER['DOCUMENT_ROOT'] . $this->_imagePath;
		
		throw new \Exception ("Field «{$name}» absent in class ". __CLASS__ ."");
	}

    public function getImagePath() {
        return $this->_imagePath;
    }
    public function getImageFile() {
        return $_SERVER['DOCUMENT_ROOT'] . $this->_imagePath;
    }





    //Создаёт image с указанными размерами
	// Если планируется работать с файлом, можно не указывать
	public function __construct($width = null, $height = null)
	{
		$this->actions = array();
		
		if ($width !== null && $height !== null) $this->actions[] = array('create', $width, $height);
		return $this;
	}
	private function _create($action, $canvas)
	{
		$canvas = imagecreatetruecolor($action[1], $action[2]);
		$fillColor = imagecolortransparent($canvas);
		imagefill($canvas, 0,0, $fillColor);
		return $canvas;
	}

	

	
	
	
	//Выполняет учтённые действия, возвращает $this, если выполнена успешно, false, если не успешно
	public function run()
	{
		if (count($this->actions) == 0) return $this;

		$this->_imagePath = null;

		//Проверка кэширования
		$hash = md5(serialize($this->actions));
		
		$cat0 = $_SERVER['DOCUMENT_ROOT'] . self::$cacheDir;

		//Особенность браузеров с установленным adBlock - блокируются изображения с частью пути "ad". Обходим.
		$cc = substr($hash, 0, 2);
		if ($cc == 'ad') $cc = 'a_d';
		$cat1 = $cat0 . $cc . '/';
		
		$cc = substr($hash, 2, 2); 
		if ($cc == 'ad') $cc = 'a_d';
		$cat2 = $cat1 . $cc . '/';
		
		
		
		
		
		
		$cacheFile = $cat2 . substr($hash,4) . '.png';
		$needGenerate = true;
		if (file_exists($cat0) && file_exists($cat1) && file_exists($cat2) && file_exists($cacheFile)) {
			$cachetime = stat($cacheFile);
			$needGenerate = false;
			foreach($this->sourceFiles as $source) {
				if (!file_exists($source)) {
					$needGenerate = true;
					break;
				}
				$sourcetime = stat($source);
				
				if ($sourcetime[$this->fileControlTime] > $cachetime[$this->fileControlTime]) {
					$needGenerate = true;
					break;
				}
			}
		}
		if (!$needGenerate) {
			$this->_imagePath = substr($cacheFile, strlen($_SERVER['DOCUMENT_ROOT']));
			return $this;
		}
		$canvas = null;
		foreach($this->actions as $action) {
			$methodName = "_".$action[0];

			if (method_exists($this, $methodName)) $canvas = $this->$methodName($action, $canvas);
		}
		if ($canvas === null) return false;

		if (!file_exists($cat0)) mkdir($cat0);
		if (!file_exists($cat1)) mkdir($cat1);
		if (!file_exists($cat2)) mkdir($cat2);
		
		if (file_exists($cacheFile)) unlink($cacheFile);
		
		imageSaveAlpha($canvas, true);
		imagepng($canvas, $cacheFile);
		
		$this->_imagePath = substr($cacheFile, strlen($_SERVER['DOCUMENT_ROOT']));
		return $this;
	}
	
	
	
	
/**
 * Convert color from imageColorAllocate() to hex in XXXXXX (eg. FFFFFF, 000000, FF0000)
 *
 * name: color2rgb
 * author: Yetty
 * @param $color string from imageColorAllocate()
 * @return string; color in style XXXXXX (eg. FFFFFF, 000000, FF0000)
 */
	public static function color2rgb($color)
	{
		return str_pad(base_convert($color, 10, 16), 6, 0, STR_PAD_LEFT);
	}

/**
 * Convert color from hex in XXXXXX (eg. FFFFFF, 000000, FF0000) to array(R, G, B)
 * of integers (0-255).
 *
 * name: rgb2array
 * author: Yetty
 * @param $color string hex in XXXXXX (eg. FFFFFF, 000000, FF0000)
 * @return array; array(R, G, B) of integers (0-255)
 */
	public static function rgb2array($rgb)
	{
		return array(
			base_convert(substr($rgb, 0, 2), 16, 10),
			base_convert(substr($rgb, 2, 2), 16, 10),
			base_convert(substr($rgb, 4, 2), 16, 10),
		);
	}	
	
	
/**
	Изменяет размер изображения
	Если width или height установлены в null, изменяет пропорционально
*/	
	public function resize($width, $height = null)
	{
		if ($width !== null || $height !== null) $this->actions[] = array('resize', $width, $height);
		return $this;
	}
	private function _resize($action, $canvas)
	{
		$newWidth = $action[1];
		$newHeight = $action[2];
		
		//Определяемся с пропорциями
		if ($newWidth === null)	$newWidth = (int)(imagesx($canvas) * $newHeight / imagesy($canvas));
		if ($newHeight === null)	$newHeight = (int)(imagesy($canvas) * $newWidth / imagesx($canvas));
		
		$newCanvas = imagecreatetruecolor($newWidth, $newHeight);
		
		
		 imagealphablending($newCanvas, false);
		 imagesavealpha($newCanvas,true);
		 $transparent = imagecolorallocatealpha($newCanvas, 255, 255, 255, 127);
		 imagefilledrectangle($newCanvas, 0, 0, $newWidth, $newHeight, $transparent);
		
		
		imagecopyresampled($newCanvas, $canvas, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($canvas), imagesy($canvas));
		
		imagedestroy($canvas);
		$canvas = $newCanvas;
		return $canvas;
	}
	


    /**
     *
     * 	Пропорционально вписывает изображение в прямоугольник с указанными размерами так, чтобы оно заполняло его весь. Непоместившееся - отрезается
     *
     * @param int $width ширина прямоугольника
     * @param int $height высота прямоугольника
     * @param int $horizontal как расположить изображение в случае отреза по горизонтали: -1 - прижать к левому краю, отрезать правый, +1 - прижать к правому краю, отрезать левый, 0 - расположить по центру, обрезать по обоим сторонам
     * @param int $vertical как расположить изображение в случае отреза по вертикали: -1 - прижать к верху, отрезать низ, +1 - прижать к низу, отрезать верх, 0 - расположить по центру, обрезать и сверху, и снизу.
     * @return $this
     */
	public function cover($width, $height, $horizontal = 0, $vertical = 0)
	{
		if ($width !== null && $height !== null) $this->actions[] = array('cover', $width, $height, $horizontal, $vertical);
		return $this;
	}
	private function _cover($action, $canvas)
	{
		$newWidth = $action[1];
		$newHeight = $action[2];
		$horizontal = $action[3];
		$vertical = $action[4];
		
		$width = imagesx($canvas);
		$height = imagesy($canvas);
		
		//Определяем размеры изображения, котрое будет накладывать на прямоугольник
		$resizeScale = min( ((double)$height / (double)$newHeight), ((double)$width / (double)$newWidth) );
		$coverWidth = (int)($width / $resizeScale);
		$coverHeight = (int)($height / $resizeScale);
		
		//Определяем координаты, в которые будет копировать изображение
		$left = $top = 0;
		
		if ($horizontal == -1) $left = 0;
		if ($horizontal == 0) $left = (int)(($newWidth - $coverWidth) / 2);
		if ($horizontal == 1) $left = (int)($newWidth - $coverWidth);
		
		if ($vertical == -1) $top = 0;
		if ($vertical == 0) $top = (int)(($newHeight - $coverHeight) / 2);
		if ($vertical == 1) $top = (int)($newHeight - $coverHeight);
		

		$newCanvas = imagecreatetruecolor($newWidth, $newHeight);
		imagealphablending($newCanvas, false);
		imagesavealpha($newCanvas,true);
		$transparent = imagecolorallocatealpha($newCanvas, 255, 255, 255, 127);
		imagefilledrectangle($newCanvas, 0, 0, $newWidth, $newHeight, $transparent);

	
		imagecopyresampled($newCanvas, $canvas, $left, $top, 0, 0, $coverWidth, $coverHeight, imagesx($canvas), imagesy($canvas));
		
		imagedestroy($canvas);
		$canvas = $newCanvas;
		return $canvas;
	}
	
	





/**
	Пропорционально вписывает изображение в прямоугольник с указанными размерами так, чтобы оно в него поместилось. Оставшееся место заполняется прозрачным цветом

* @param int $width ширина прямоугольника
* @param int $height высота прямоугольника
* @param int $horizontal как расположить изображение в случае отреза по горизонтали: -1 - прижать к левому краю, +1 - прижать к правому краю, 0 - расположить по центру
* @param int $vertical как расположить изображение в случае отреза по вертикали: -1 - прижать к верху, +1 - прижать к низу, 0 - расположить по центру.

*/	
	public function contain($width, $height, $horizontal = 0, $vertical = 0)
	{
		if ($width !== null && $height !== null) $this->actions[] = array('contain', $width, $height, $horizontal, $vertical);
		return $this;
	}
	private function _contain($action, $canvas)
	{
		$newWidth = $action[1];
		$newHeight = $action[2];
		$horizontal = $action[3];
		$vertical = $action[4];
		
		$width = imagesx($canvas);
		$height = imagesy($canvas);
		
		//Определяем размеры изображения, котрое будет вписывать в прямоугольник
		$resizeScale = max( ((double)$height / (double)$newHeight), ((double)$width / (double)$newWidth) );
		$coverWidth = (int)($width / $resizeScale);
		$coverHeight = (int)($height / $resizeScale);
		
		//Определяем координаты, в которые будет копировать изображение
		$left = $top = 0;
		
		if ($horizontal == -1) $left = 0;
		if ($horizontal == 0) $left = (int)(($newWidth - $coverWidth) / 2);
		if ($horizontal == 1) $left = (int)($newWidth - $coverWidth);
		
		if ($vertical == -1) $top = 0;
		if ($vertical == 0) $top = (int)(($newHeight - $coverHeight) / 2);
		if ($vertical == 1) $top = (int)($newHeight - $coverHeight);
		

		$newCanvas = imagecreatetruecolor($newWidth, $newHeight);
		imagealphablending($newCanvas, false);
		imagesavealpha($newCanvas,true);
		$transparent = imagecolorallocatealpha($newCanvas, 255, 255, 255, 127);
		imagefilledrectangle($newCanvas, 0, 0, $newWidth, $newHeight, $transparent);

	
		imagecopyresampled($newCanvas, $canvas, $left, $top, 0, 0, $coverWidth, $coverHeight, imagesx($canvas), imagesy($canvas));
		
		imagedestroy($canvas);
		$canvas = $newCanvas;
		return $canvas;
	}
	
	




	
	
	
	
	
	
	
	
	
	
	// Загрузить файл
	// Если при создании были установлены высота и ширина, они будут сброшены
	public function load($file)
	{
		
		if (mb_substr($file, 0, 1, "utf-8") == '/' && (!file_exists($file))) $file = $_SERVER['DOCUMENT_ROOT']. $file;
		if ( $file instanceof Photo ) $file = $_SERVER['DOCUMENT_ROOT']. $file->pictureFile;

		$this->actions[] = array('load', $file);
		$this->sourceFiles[] = $file;
		return $this;
	}
	private function _load($action, $canvas)
	{
		$file = $action[1];
		$canvas = null;

		if (!file_exists($file)) throw new \Exception("Class Image: file «{$file}» absent!");
		if (!is_file($file)) throw new  \Exception("Class Image: «{$file}» is not a file!");
		
		$partsPath = pathinfo($file);
		$imageDir = $partsPath['dirname'];
		$imageFile = $partsPath['filename'];
		$imageExt = $partsPath['extension'];
		
		if (strtolower($imageExt) == 'jpg' || strtolower($imageExt) == 'jpeg') $canvas = imagecreatefromjpeg($file);
		if (strtolower($imageExt) == 'png') $canvas = imagecreatefrompng($file);
		if (strtolower($imageExt) == 'gif') $canvas = imagecreatefromgif($file);

		return $canvas;
	}
	
	
	// Залить цветом
	// Если при создании были установлены высота и ширина, они будут сброшены
	public function fill($color)
	{
		$this->actions[] = array('fill', $color);
		return $this;
	}
	private function _fill($action, $canvas)
	{
		$rgbC = self::rgb2array($action[1]);
		$fillColor = imagecolorallocate($canvas, $rgbC[0],$rgbC[1],$rgbC[2]);
		imagefill($canvas, 0,0, $fillColor);
		return $canvas;
	}
	

}
