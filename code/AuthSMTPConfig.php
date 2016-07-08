<?php

/**
 * AuthSMTPConfig class for manual configuration if project doesn't use Injector and SmtpMailer configuration directly to setup AuthSMTPService
 */
class AuthSMTPConfig extends Object {
	private static $host; // = 'mail.authsmtp.com';
	private static $port; // = 2525;
	private static $user;
	private static $password;
	private static $from;
	private static $tls = true;
	private static $charset = 'UTF-8';

	private static $configurable_options = ['host', 'port', 'user', 'password', 'from', 'tls', 'charset'];

	/**
	 * Call this method to configure the SilverStripe Mail class to use SmtpMailer class as it's default Mailer using either provided
	 * options or AuthSMTPConfig.config values.
	 *
	 * @param array $options which override config values
	 * @return array $options used to configure SmtpMailer as figured out from provided and config.
	 */

	public static function configure(array $options = []) {
		$config = static::config();
		$configurableOptions = static::config()->get('configurable_options');

		$options = array_merge(
			array_combine(
				$configurableOptions,
				array_map(
					function($key) use ($config) {
						return $config->get($key);
					},
					$configurableOptions
				)
			),
			$options
		);
		$mailer = new SmtpMailer(
			$options['host'] . ':' . $options['port'],
			$options['user'],
			$options['password'],
			$options['tls'],
			$options['charset']
		);
		Injector::inst()->registerService($mailer, 'Mailer');

		return $options;
	}
}