<?php
class AuthSMTPLogAdmin extends ModelAdmin {
	private static $managed_models = [
		'AuthSMTPQueueEntryModel' => [
			'title' => 'Sent Emails',
		],
	];

	private static $url_segment = 'auth-smtp';
	private static $menu_title = 'Sent Email';

	public $showImportForm = false;

}