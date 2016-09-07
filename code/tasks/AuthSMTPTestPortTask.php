<?php

class AuthSMTPTestPortTask extends BuildTask {
	/**
	 * Tries to open a port as per configuration or using 'port' on query string overrides if you're logged in
	 * @param SS_HTTPRequest $request
	 */
	public function run($request) {
		if (Member::currentUserID()) {
			AuthSMTPService::test_port($request->getVars());
		} else {
			AuthSMTPService::test_port();
		}
	}
}