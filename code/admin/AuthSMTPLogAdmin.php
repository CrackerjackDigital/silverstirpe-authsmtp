<?php
class AuthSMTPLogAdmin extends ModelAdmin {
	private static $managed_models = [
		'AuthSMTPQueueModel' => [
			'title' => 'Email Logs',
		],
	];

	private static $url_segment = 'auth-smtp';
	private static $menu_title = 'SMTP Logs';

	public $showImportForm = false;

}