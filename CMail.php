<?php

class CMail
{
	public $viewPath='application.views.mail';
	public $mailer=null;
	public $from='';
	public $replyTo=true;
	public $to='';
	public $subject='';
	public $body='';
	public $charset='utf-8';
	public $images=array();
	public $attaches=array();
	public $data=array();

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
			$this->mailer=Yii::app()->name.' '.Yii::app()->getModule('game')->version;
		}
		$this->viewPath=Common::getPath($this->viewPath,'application.views.mail');
		$this->charset=strtolower($this->charset);
		if ($from)
		{
			$this->from=$from;
		}
		$this->from=$this->_encMail($this->from);
		$this->replyTo=$this->_encMail($this->replyTo);
		if ($this->replyTo===true)
		{
			$this->replyTo=$this->from;
		}
		if ($to)
		{
			$this->to=$to;
		}
		$this->to=$this->_encMail($this->to);
		if ($subject)
		{
			$this->subject=$subject;
		}
		$this->subject=$this->_encMime($this->subject);
		if ($body)
		{
			$this->body=$body;
		}
		if (file_exists($this->viewPath.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$this->body.'.php'))
		{
			$this->body=Yii::app()->controller->renderFile($this->viewPath.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$this->body.'.php',$this->data,true);
		}
		elseif (file_exists($this->viewPath.DIRECTORY_SEPARATOR.$this->body.'.php'))
		{
			$this->body=Yii::app()->controller->renderFile($this->viewPath.DIRECTORY_SEPARATOR.$this->body.'.php',$this->data,true);
		}

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
			$this->_boundary=md5($this->to.time());
			$this->_contentType='multipart/related;boundary="'.$this->_boundary.'"';
			$this->_contentTypeBody='text/html;charset="'.$this->charset.'"';
		}
		elseif (!$has_html&&!$has_images&&!$has_attaches)
		{
			$this->_contentType='text/plain;charset="'.$this->charset.'"';
		}
		elseif (!$has_html&&($has_images||$has_attaches))
		{
			$this->_boundary=md5($this->to.time());
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

		$headers="From: ".$this->from."\n"
			.($this->replyTo?"Reply-To: ".$this->replyTo."\n":"")
			."MIME-Version: 1.0\n"
			."Content-Type: ".$this->_contentType."\n"
			."X-Mailer: ".$this->mailer."\n";

		$message=($this->_boundary?'--'.$this->_boundary."\nContent-type: ".$this->_contentTypeBody."\nContent-Transfer-Encoding: 8bit\n\n":'').$this->_iconv(str_replace("\r",'',$this->body))."\n";

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

		return mail($this->to,$this->subject,$message,$headers);
	}


	private function _attach($files)
	{
		$message='';
		foreach ($files as $file)
		{
			$filename=basename($file);
			$ext=pathinfo($file,PATHINFO_EXTENSION);
			if (array_key_exists($ext,$this->_mimeTypes))
			{
				$message.="--".$this->_boundary."\nContent-Type: ".$this->_mimeTypes[$ext].";name=\"".$filename."\"\nContent-Transfer-Encoding: base64\nContent-ID: <".$filename.">\n\n".chunk_split(base64_encode(file_get_contents($file)))."\n";
			}
		}
		return $message;
	}


	private function _iconv($str)
	{
		if ($this->charset!='utf-8')
		{
			return iconv('utf-8',$this->charset.'//TRANSLIT',$str);
		}
		return $str;
	}


	private function _encMime($str)
	{
		return '=?'.$this->charset.'?B?'.base64_encode($this->_iconv($str)).'?=';
	}


	private function _encMail($mail)
	{
		if (is_array($mail))
		{
			return $this->_encMime($mail[key($mail)]).' <'.key($mail).'>';
		}
		else
		{
			return $mail;
		}
	}

}

?>
