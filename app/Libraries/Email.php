<?php
namespace App\Libraries;

require_once "Mail.php";
require_once "Mail/mime.php";
error_reporting(E_ERROR | E_PARSE);

class Email
{
	public $to = '';
	public $subject = '';
	public $html = '';
	public $from = '';
	public $images = [];
	public $attachments = [];

	private $smtp_host 		= 'smtp.gmail.com';
	private $smtp_username 	= 'noreply@makeadiff.in';
	private $smtp_password 	= 'noreplygonemad';

	function send() {
		$headers = [
			'From'      => $this->from,
			'To'        => $this->to,
			'Subject'   => $this->subject
		];

		$mime = new \Mail_mime(array('eol' => "\n"));
		$mime->setHTMLBody($this->html);

		foreach($this->images as $image) {
			$name = basename($image);
			$mime->addHTMLImage($image, mime_content_type($image),'',true, $name);
		}

        foreach ($this->attachments as $attachment_file) {
            if($attachment_file and file_exists($attachment_file)) {
                $mime->addAttachment($attachment_file, mime_content_type($attachment_file));
            }
        }

		$smtp = \Mail::factory('smtp',
			array ( 'host'     => $this->smtp_host,
					'auth'     => true,
					'username' => $this->smtp_username,
					'password' => $this->smtp_password));

		$body = $mime->get();
		$headers = $mime->headers($headers);

		$mail = $smtp->send($this->to, $headers, $body);

		if (\PEAR::isError($mail)) {
			//echo("<p>" . $mail->getMessage() . "</p>");
			return false;
		}

		return true;
	}

	public function queue() {
		$message = [
			'type'		=> 'email',
			'to'		=> $this->to,
			'from'		=> $this->from,
			'subject'	=> $this->subject,
			'body'		=> $this->html,
			'images'	=>json_encode($this->images),
			'attachments'=>json_encode($this->attachments),
			'added_on'	=> date('Y-m-d H:i:s'),
			'status'	=> 'pending'
		];
		app('db')->table('Message_Queue')->insert($message);

		return true;
	}
}