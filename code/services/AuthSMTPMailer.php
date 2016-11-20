<?php

class AuthSMTPMailer extends SmtpMailer {

	private static $log_level = SS_Log::WARN;

	/**
	 * creates a new phpmailer object with exceptions enabled
	 */
	protected function initMailer() {
		$mail = new PHPMailer(AuthSMTPService::exceptions_on_error());

		$mail->AllowEmpty = AuthSMTPService::allow_empty_body();
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

	/**
	 * Call through to get the current AuthSMTPService config.log_level.
	 * @return int
	 */
	public static function log_level() {
		return AuthSMTPService::log_level();
	}

	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
		$sent = false;
		try {
			if ($sent = parent::sendHTML($to, $from, $subject, $htmlContent, $attachedFiles, $customheaders, $plainContent, $inlineImages)) {

				SS_Log::log("AuthSMTPMailer sent html email to '$to', from '$from' with subject '$subject'", static::log_level());

			} else {

				throw new Exception("AuthSMTPMailer failed to send html email to '$to', from '$from' with subject '$subject'");

			}
		} catch (Exception $e) {

			AuthSMTPService::error("AuthSMTPMailer failed to send html email to '$to', from '$from' with subject '$subject': " . $e->getMessage());

		}
		return $sent;
	}

	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		$sent = false;

		try {
			if ($sent = parent::sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders)) {

				SS_Log::log("AuthSMTPMailer sent plain text email to '$to', from '$from' with subject '$subject'", static::log_level());

			} else {

				throw new Exception("AuthSMTPMailer failed to send plain text email to '$to', from '$from' with subject '$subject'");

			}

		} catch (Exception $e) {

			AuthSMTPService::error("AuthSMTPMailer failed to send plain text email to '$to', from '$from' with subject '$subject'");

		}
		return $sent;
	}
}