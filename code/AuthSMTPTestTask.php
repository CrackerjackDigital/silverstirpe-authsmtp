<?php

class AuthSMTPTestTask extends BuildTask {
	/**
	 * Tries to send an email with the authsmtp options set as per config and environment to self.Recipient email address.
	 * @param $request
	 */
	public function run($request) {
		AuthSMTPConfig::test_send();
	}
}