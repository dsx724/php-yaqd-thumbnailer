<?php
class Model {
	const THUMB_SIZE = 200;
	public static function getThumbnail($file,$size){
		if (!is_string($file) || strlen($file) < 5) die('Invalid String');
		if (!is_file($file) || !self::isImage($file)) die('Invalid File: '.htmlspecialchars($file));
		header('Content-Type: image/jpeg');
		$cache = apc_fetch($file);
		if ($cache !== false) die($cache);
		list($width, $height) = getimagesize($file);
		if ($width > $size || $height > $size){
			if ($width > $height){
				$width_new = $size;
				$height_new = round($size * $height / $width);
			} else {
				$height_new = $size;
				$width_new = round($size * $width / $height);
			}
			$thumb = imagecreatetruecolor($width_new, $height_new);
			imagecopyresized($thumb, imagecreatefromjpeg($file), 0, 0, 0, 0, $width_new, $height_new, $width, $height);
			ob_start();
			imagejpeg($thumb);
			apc_store($file,ob_get_flush());
		} else header('X-Accel-Redirect: '.$file);
		exit;
	}
	public static function getFiles($dir){
		if (!is_string($dir) && strlen($dir) < 1) die('Invalid String');
		if (!is_dir($dir)) die('Invalid Directory');
		if (strpos($dir,'..') !== false) die('Invalid Directory');
		if (substr($dir,0,1) == '/') die('Invalid Directory');
		$data = array('files'=>array(),'folders'=>array());
		if ($dir === '.'){
			foreach(scandir($dir) as $file){
				if (substr($file,0,1) == '.') continue;
				if (is_dir($file)) $data['folders'][] = $file.'/';
				else if (self::isImage($file)) $data['files'][] = $file;
			}
		} else {
			if (substr($dir,-1,1) != '/') $dir .= '/';
			foreach(scandir($dir) as $file){
				if (substr($file,0,1) == '.') continue;
				if (is_dir($dir.$file)) $data['folders'][] = $dir.$file.'/';
				else if (self::isImage($file)) $data['files'][] = $dir.$file;
			}
		}
		return $data;
	}
	private static function isImage($file){
		return in_array(strtolower(substr($file,-4,4)),array('.jpg','.png'));
	}
}
class Controller {
	private static $data;
	public static function start(){
		if (isset($_GET['action'])){
			if ($_GET['action'] == 'thumb' && isset($_GET['file'])) Model::getThumbnail($_GET['file'], isset($_GET['size']) && ctype_digit($_GET['size']) && $_GET['size'] > 10 && $_GET['size'] < 1000 ? round($_GET['size']) : Model::THUMB_SIZE);
			else if ($_GET['action'] == 'list' && isset($_GET['dir'])) self::save(Model::getFiles($_GET['dir']));
		} else self::save(Model::getFiles('.'));
	}
	private static function save($data){
		self::$data = $data;
	}
	public static function display(){
		if (!isset(self::$data)) echo 'No data!';
		else {
			if (isset($_GET['dir']) && strlen($_GET['dir']) > 1) echo '<li class="folder"><a href="?action=list&dir='.urlencode(strrev(strstr(substr(strrev($_GET['dir']),1),'/') ?: '.')).'">Back</a></li>';
			echo '<ul id="folders">';
			foreach(self::$data['folders'] as $folder) echo '<li class="folder"><a href="?action=list&dir='.urlencode($folder).'">'.htmlspecialchars($folder).'</a></li>';
			echo '</ul>';
			if (count(self::$data['files']) == 0) echo 'This folder is contains no pictures.';
			echo '<ul id="files">';
			foreach(self::$data['files'] as $file) echo '<li class="image"><a href="'.$file.'" target="_blank"><img src="?action=thumb&file='.urlencode($file).'" alt="'.htmlspecialchars($file).'"/></a></li>';
			echo '</ul>';
			if (isset($_GET['dir']) && strlen($_GET['dir']) > 1) echo '<li class="folder"><a href="?action=list&dir='.urlencode(strrev(strstr(substr(strrev($_GET['dir']),1),'/') ?: '.')).'">Back</a></li>';
		}
	}
}
Controller::start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>YAQD Thumbnailer by Da Xue &copy; 2011</title>
<style>
#folders { padding-left: 0px; }
.folder { display: block; }
.image {
	display: inline-block;
	width: 200px;
	text-align: center;
	vertical-align: middle;
	margin: 5px;
}
</style>
</head>
<body>
<?php Controller::display(); ?>
<br/><a href="https://github.com/dsx724/php-yaqd-thumbnailer">Yet Another Quick and Dirty PHP Thumbnailer</a> by Da Xue &copy; 2012<br/>APC Cacher<br/>GD2 Graphics<br/>One File<br/>100 Line Count
</body>
</html>
