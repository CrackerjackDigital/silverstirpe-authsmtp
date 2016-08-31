<?php
class AuthSMTPMailer extends SmtpMailer {
	/**
	 * creates a new phpmailer object with exceptions enabled
	 */
	protected function initMailer() {
		$mail = new PHPMailer(true);
		$mail->AllowEmpty = true;
		$mail->IsSMTP();
		$mail->Host = $this->host;

		if ($this->user) {
			$mail->SMTPAuth = true; // turn on SMTP authentication
			$mail->Username = $this->user;
			$mail->Password = $this->pass;
		}

		if ($this->tls) {
			$mail->SMTPSecure = 'tls';
		}

		if ($this->charset) {
			$mail->CharSet = $this->charset;
		}

		return $mail;
	}

	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
		if (!parent::sendHTML($to, $from, $subject, $htmlContent, $attachedFiles, $customheaders, $plainContent, $inlineImages)) {
			// shouldn't get here as phpmailer should have thrown an exception already
			throw new Exception("AuthSMTPMailer failed to send html email to '$to', from '$from' with subject '$subject'");
		}
		SS_Log::log("AuthSMTPMailer sent html email to '$to', from '$from' with subject '$subject'", SS_Log::INFO);
		return true;
	}
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		if (!parent::sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders)) {
			// shouldn't get here as phpmailer should have thrown an exception already
			throw new Exception("AuthSMTPMailer failed to send plain text email to '$to', from '$from' with subject '$subject'");
		}
		SS_Log::log("AuthSMTPMailer sent plain text email to '$to', from '$from' with subject '$subject'", SS_Log::INFO);
		return true;
	}
}