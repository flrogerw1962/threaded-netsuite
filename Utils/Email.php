<?php

include 'Mail.php';
include 'Mail/mime.php' ;



final class Utils_Email{

	public static function sendEmail( $sSubject, $sReceipients, $sFrom, $sBody, $sAttachmentPath = null ){

		$text = strip_tags( $sBody );
		$html = $sBody;

		if( $sAttachmentPath != null ){
			$file = $sAttachmentPath;
		}

		$crlf = "\n";
		$hdrs = array(
				'From'    => $sFrom,
				'Subject' => $sSubject
		);

		$mime = new Mail_mime(array('eol' => $crlf));

		$mime->setTXTBody($text);
		$mime->setHTMLBody($html);
		$mime->addAttachment($file, 'text/plain');

		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		$mail =& Mail::factory('mail');
		$mail->send( $sReceipients, $hdrs, $body );
	}
}