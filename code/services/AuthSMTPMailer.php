<?php
class AuthSMTPMailer extends SmtpMailer {
	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
		if (!parent::sendHTML($to, $from, $subject, $htmlContent, $attachedFiles, $customheaders, $plainContent, $inlineImages)) {
			AuthSMTPService::error("AuthSMTPMailer failed to send html email to '$to', from '$from' with subject '$subject'", null);
			return false;
		}
		SS_Log::log("AuthSMTPMailer sent html email to '$to', from '$from' with subject '$subject'", SS_Log::INFO);
		return true;
	}
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		if (!parent::sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders)) {
			AuthSMTPService::error("AuthSMTPMailer failed to send plain text email to '$to', from '$from' with subject '$subject'", null);
			return false;
		}
		SS_Log::log("AuthSMTPMailer sent plain text email to '$to', from '$from' with subject '$subject'", SS_Log::INFO);
		return true;
	}
}