<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);
		$configurator->addStaticParameters([
			'databaseDsn' => self::env('STUDIOCORE_DB_DSN'),
			'databaseUser' => self::env('STUDIOCORE_DB_USER'),
			'databasePassword' => self::env('STUDIOCORE_DB_PASSWORD'),
			'bulkgateApplicationId' => (int) self::env('STUDIOCORE_BULKGATE_APPLICATION_ID', '0'),
			'bulkgateApplicationToken' => self::env('STUDIOCORE_BULKGATE_APPLICATION_TOKEN'),
		]);

		//$configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$configurator->enableTracy($appDir . '/log');

                // produkce
                //$configurator->setDebugMode(false);

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/common.neon');
		$configurator->addConfig($appDir . '/config/services.neon');
		$configurator->addConfig($appDir . '/config/local.neon');

                // vytvořit DI kontejner
                $container = $configurator->createContainer();

                // start session
	                /** @var Nette\Http\Session $session */
	                $session = $container->getByType(\Nette\Http\Session::class);
	                $session->start();

		return $configurator;
	}


	private static function env(string $name, string $default = ''): string
	{
		$value = getenv($name);
		return $value === false ? $default : $value;
	}
}
