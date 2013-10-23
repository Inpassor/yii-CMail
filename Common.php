<?php
/**
 * Common class file.
 *
 * Common methods.
 *
 * @author Inpassor <inpassor@gmail.com>
 * @link https://github.com/Inpassor/yii-CMail
 *
 * @version 0.1 (2013.10.23)
 */

class Common
{

	private static function _chkDir($dir,$create)
	{
		if (($alias=YiiBase::getPathOfAlias($dir)))
		{
			return $alias;
		}
		if (!file_exists($dir)&&$create)
		{
			mkdir($dir,0777,true);
		}
		if (file_exists($dir))
		{
			return $dir;
		}
		return false;
	}

	public static function getPath($path,$default=null,$createDir=false)
	{
		if ($default===null)
		{
			$default=dirname(__FILE__);
		}
		if ($path===null)
		{
			$path=$default;
		}
		if (!($ret=self::_chkDir($path,$createDir)))
		{
			$ret=self::_chkDir($default,$createDir);
		}
		return $ret;
	}

}

?>
