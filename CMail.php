<?php
/**
 * CMail class file.
 *
 * Simple mailer
 *
 * @author Inpassor <inpassor@gmail.com>
 * @link https://github.com/Inpassor/yii-CMail
 *
 * @version 0.1.11 (2015.05.21)
 */

class CMail
{
	public $viewPath='application.views.mail';
	public $mailer=null;
	public $from='';
	public $replyTo=true;
	public $to='';
	public $subject='';
	public $body='';
	public $defaultCharset='utf-8';
	public $charset='utf-8';
	public $images=array();
	public $attaches=array();
	public $data=array();

	private $_body=null;
	private $_contentType=null;
	private $_contentTypeBody=null;
	private $_boundary=null;

	private $_mimeTypes=array(
		'aif' =>'audio/x-aiff',
		'aiff'=>'audio/x-aiff',
		'avi' =>'video/avi',
		'bmp' =>'image/bmp',
		'bz2' =>'application/x-bz2',
		'csv' =>'text/csv',
		'dmg' =>'application/x-apple-diskimage',
		'doc' =>'application/msword',
		'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'eml' =>'message/rfc822',
		'aps' =>'application/postscript',
		'exe' =>'application/x-ms-dos-executable',
		'flv' =>'video/x-flv',
		'gif' =>'image/gif',
		'gz'  =>'application/x-gzip',
		'hqx' =>'application/stuffit',
		'htm' =>'text/html',
		'html'=>'text/html',
		'jar' =>'application/x-java-archive',
		'jpeg'=>'image/jpeg',
		'jpg' =>'image/jpeg',
		'm3u' =>'audio/x-mpegurl',
		'm4a' =>'audio/mp4',
		'mdb' =>'application/x-msaccess',
		'mid' =>'audio/midi',
		'midi'=>'audio/midi',
		'mov' =>'video/quicktime',
		'mp3' =>'audio/mpeg',
		'mp4' =>'video/mp4',
		'mpeg'=>'video/mpeg',
		'mpg' =>'video/mpeg',
		'odg' =>'vnd.oasis.opendocument.graphics',
		'odp' =>'vnd.oasis.opendocument.presentation',
		'odt' =>'vnd.oasis.opendocument.text',
		'ods' =>'vnd.oasis.opendocument.spreadsheet',
		'ogg' =>'audio/ogg',
		'pdf' =>'application/pdf',
		'png' =>'image/png',
		'ppt' =>'application/vnd.ms-powerpoint',
		'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'ps'  =>'application/postscript',
		'rar' =>'application/x-rar-compressed',
		'rtf' =>'application/rtf',
		'tar' =>'application/x-tar',
		'sit' =>'application/x-stuffit',
		'svg' =>'image/svg+xml',
		'tif' =>'image/tiff',
		'tiff'=>'image/tiff',
		'ttf' =>'application/x-font-truetype',
		'txt' =>'text/plain',
		'vcf' =>'text/x-vcard',
		'wav' =>'audio/wav',
		'wma' =>'audio/x-ms-wma',
		'wmv' =>'audio/x-ms-wmv',
		'xls' =>'application/excel',
		'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xml' =>'application/xml',
		'zip' =>'application/zip',
	);


	private function _init($from,$to,$subject,$body)
	{
		if (!$this->mailer)
		{
			$this->mailer=Yii::app()->name;
		}
		$this->viewPath=CHelper::getPath($this->viewPath,'application.views.mail');
		$this->charset=strtolower($this->charset);
		if ($from)
		{
			$this->from=$from;
		}
		if ($to)
		{
			$this->to=$to;
		}
		if ($subject)
		{
			$this->subject=$subject;
		}
		if ($body)
		{
			$this->body=$body;
		}
		if (file_exists($this->viewPath.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$this->body.'.php'))
		{
			$this->_body=$this->body;
		}
		elseif (file_exists($this->viewPath.DIRECTORY_SEPARATOR.$this->body.'.php'))
		{
			$this->_body=$this->body;
		}
		if (file_exists($this->viewPath.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$this->_body.'.php'))
		{
			$this->body=Yii::app()->controller->renderFile($this->viewPath.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$this->_body.'.php',$this->data,true);
		}
		elseif (file_exists($this->viewPath.DIRECTORY_SEPARATOR.$this->_body.'.php'))
		{
			$this->body=Yii::app()->controller->renderFile($this->viewPath.DIRECTORY_SEPARATOR.$this->_body.'.php',$this->data,true);
		}
		$this->body=str_replace("\r",'',$this->body);

		$has_html=false;
		if ($this->body!=strip_tags($this->body))
		{
			$has_html=true;
		}
		$has_images=count($this->images);
		$has_attaches=count($this->attaches);

		if ($has_html&&!$has_images&&!$has_attaches)
		{
			$this->_contentType='text/html;charset="'.$this->charset.'"';
		}
		elseif ($has_html&&($has_images||$has_attaches))
		{
			$this->_boundary=md5((is_array($this->to)?implode('-',$this->to):$this->to).time());
			$this->_contentType='multipart/related;boundary="'.$this->_boundary.'"';
			$this->_contentTypeBody='text/html;charset="'.$this->charset.'"';
		}
		elseif (!$has_html&&!$has_images&&!$has_attaches)
		{
			$this->_contentType='text/plain;charset="'.$this->charset.'"';
		}
		elseif (!$has_html&&($has_images||$has_attaches))
		{
			$this->_boundary=md5((is_array($this->to)?implode('-',$this->to):$this->to).time());
			$this->_contentType='multipart/related;boundary="'.$this->_boundary.'"';
			$this->_contentTypeBody='text/plain;charset="'.$this->charset.'"';
		}
	}


	public function __construct($from='',$to='',$subject='',$body='')
	{
		$this->_init($from,$to,$subject,$body);
	}


	public function send($from='',$to='',$subject='',$body='')
	{
		$this->_init($from,$to,$subject,$body);

		$from=$this->_encMail($this->from);
		if (is_array($this->to))
		{
			$to=array();
			foreach ($this->to as $_to)
			{
				$to[]=$this->_encMail($_to);
			}
			$to=implode(', ',$to);
		}
		else
		{
			$to=$this->_encMail($this->to);
		}
		if (!$from||!$to)
		{
			return false;
		}
		$replyTo=$this->_encMail($this->replyTo);
		if ($replyTo===true)
		{
			$replyTo=$this->from;
		}
		$subject=$this->_encMime($this->subject);

		$headers="From: ".$from."\n"
			.($replyTo?"Reply-To: ".$replyTo."\n":"")
			."MIME-Version: 1.0\n"
			."Content-Type: ".$this->_contentType."\n"
			."X-Mailer: ".$this->mailer."\n";

		$message=($this->_boundary?'--'.$this->_boundary."\nContent-type: ".$this->_contentTypeBody."\nContent-Transfer-Encoding: 8bit\n\n":'').$this->_iconv($this->body)."\n";

		$endBound=false;
		if (count($this->images))
		{
			$mes=$this->_attach($this->images);
			if ($mes)
			{
				$endBound=true;
				$message.="\n".$mes;
			}
		}
		if (count($this->attaches))
		{
			$mes=$this->_attach($this->attaches);
			if ($mes)
			{
				$endBound=true;
				$message.=($endBound?'':"\n").$mes;
			}
		}

		if ($endBound)
		{
			$message.="--".$this->_boundary."--";
		}

		return mail($to,$subject,$message,$headers);
	}


	private function _attach($files)
	{
		$message='';
		foreach ($files as $file)
		{
			$filename=false;
			$fcontents=false;
			$content_type=false;
			if (substr($file,0,1)=='@')
			{
				list($_pref,$filename,$fcontents,$content_type)=explode('||',$file);
			}
			else
			{
				if (file_exists($file))
				{
					$filename=trim(basename($file),'/\\');
					$fcontents=base64_encode(file_get_contents($file));
				}
			}
			if ($filename&&$fcontents)
			{
				if (!$content_type)
				{
					$ext=pathinfo($filename,PATHINFO_EXTENSION);
					if (array_key_exists($ext,$this->_mimeTypes))
					{
						$content_type=$this->_mimeTypes[$ext];
					}
					else
					{
						$content_type='application/binary';
					}
				}
				$message.="--".$this->_boundary."\nContent-Type: ".$content_type.";name=\"".$filename."\"\nContent-Transfer-Encoding: base64\nContent-ID: <".$filename.">\n\n".chunk_split($fcontents)."\n";
			}
		}
		return $message;
	}


	private function _iconv($str)
	{
		if ($this->charset!=$this->defaultCharset)
		{
			return iconv($this->defaultCharset,$this->charset.'//TRANSLIT',$str);
		}
		return $str;
	}


	private function _encMime($str)
	{
		return '=?'.$this->charset.'?B?'.base64_encode($this->_iconv($str)).'?=';
	}


	private function _($_)
	{
		return filter_var($_,FILTER_VALIDATE_EMAIL);
	}


	private function _encMail($mail)
	{
		$tmp=explode('<',$mail);
		if (count($tmp)>=2)
		{
			if (!($email=$this->_(trim($tmp[1],'<>'))))
			{
				return false;
			}
			return $this->_encMime($tmp[0]).' <'.$email.'>';
		}
		else
		{
			return $this->_($mail);
		}
	}

}

?>
