<?php
/* Copyright (c) 2017 Daniel Weise <daniel.weise@concepts-and-training.de>, Extended GPL, see LICENSE */

namespace CaT\Ilse\App;

use CaT\Ilse\Action;
use CaT\Ilse\Aux;

use Pimple\Container;
use Symfony\Component\Console\Application;

/**
 * Do the main initializing
 */
class App extends Application
{
	const I_P_GLOBAL_CONFIG 	= ".ilse/ilias-configs";
	const I_F_CONFIG_REPOS 		= ".ilse/config_repos.yaml";
	const I_P_GLOBAL 			= ".ilse";
	const I_F_CONFIG			= "ilse_config.yaml";
	const I_R_BRANCH			= "master";

	/**
	 * Initialize the dependency injection container.
	 *
	 * @return Container
	 */
	public function getDIC() {
		$container = new Container();

		// Actions

		$container["action.deleteILIAS"] = function($c) {
			$config = $container["config.ilias"];
			return new Action\DeleteILIAS
						( $config->database()
						, $config->server()
						, $config->client()
						, $config->log()
						, $c["aux.filesystem"]
						, $c["aux.task_logger"]
						);
		};

		$container["action.installILIAS"] = function($c) {
			return new Action\InstallILIAS
						( $c["config.ilias"]
						, $c["setup.core_installer_factory"]
						, $c["aux.task_logger"]
						);
		};

		// Configs

		$container["config.ilias"] = function($c) {
			throw new \RuntimeException("Expected command to initialized ILIAS config.");
		};
		$container["config.ilse"] = function($c) {
			throw new \RuntimeException("Don't know how to build");
		};

		// Auxiliary Services

		$container["aux.filesystem"] = function($c) {
			return new Aux\FilesystemImpl();
		};
		$container["aux.task_logger"] = function($c) {
			throw new \RuntimeException("Expected command to initialize task logger.");
		};

		// Setup

		$container["setup.core_installer_factory"] = function($c) {
			return new CoreInstallerFactory();
		};
	}

	/**
	 * Initialize commands and add them to the app.
	 *
	 * @return	void
	 */
	protected function initCommands()
	{
		$this->add(new Command\UpdateCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\DeleteCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\UpdatePluginsCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\ReinstallCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\InstallCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\ConfigCommand($path, $merger, $checker, $git, $repos));
		$this->add(new Command\ExampleConfigCommand());
	}

	/**
	 * Checks whether the app folder exists otherwise create one
	 *
	 * @param string 		$path
	 */
	protected function initAppFolder($path)
	{
		assert('is_string($path)');

		if(!is_dir($path->getHomeDir() . "/" . self::I_P_GLOBAL))
		{
			mkdir($path->getHomeDir() . "/" . self::I_P_GLOBAL, 0755);
		}
	}

	/**
	 * Initialize the config repo in ~/.ilias-installer/config
	 *
	 * @param string 				$path
	 * @param Git\Git 		$gw
	 * @param Interfaces\Parser 	$parser
	 * @param string 				$repos
	 * @param GitExecuter 			$ge
	 */
	protected function initConfigRepo($path, Git\Git $gw, Interfaces\Parser $parser, $repos, GitExecuter $ge)
	{
		$name = "";
		$path = $path->getHomeDir() . "/" . self::I_P_GLOBAL_CONFIG;

		foreach ($repos as $repo)
		{
			$dir = $this->getUniqueDirName($path, $repo);
			if(!is_dir($dir))
			{
				mkdir($dir, 0755, true);
			}
			else
			{
				$name = basename($repo, '.git');
			}
			$ge->cloneGitTo($repo,
							self::I_R_BRANCH,
							$dir,
							$name
							);
		}
	}

	/**
	 * Read app config file
	 *
	 * @param string 				$path
	 * @param Interfaces\Parser 	$parser
	 *
	 * @return string
	 */
	protected function readAppConfigFile($path, $parser)
	{
		if(!is_file($path->getHomeDir() . "/" . self::I_F_CONFIG_REPOS))
		{
			throw new \Exception("File not found at " . self::I_F_CONFIG_REPOS);
		}

		return $parser->read($path->getHomeDir() . "/" . self::I_F_CONFIG_REPOS);
	}

	/**
	 * Get the config repos
	 *
	 * @param string 				$path
	 * @param Git\Git 		$gw
	 * @param Interfaces\Parser 	$parser
	 *
	 * @return string
	 */
	protected function getConfigRepos($path, Git\Git $gw, Interfaces\Parser $parser)
	{
		assert('is_string($path)');

		$result = array();
		foreach($this->readAppConfigFile($path, $parser)['repos'] as $repo)
		{
			if($gw->gitIsRemoteGitRepo($repo) === 0)
			{
				$result[] = $repo;
			}
		}
		return $result;
	}

	/**
	 * Get a name from md5 hash of path + url
	 *
	 * @param string 		$path
	 * @param string 		$url
	 *
	 * @return string
	 */
	protected function getUniqueDirName($path, $url)
	{
		assert('is_string($path)');
		assert('is_string($url)');

		$hash 	= md5($url);
		$dir 	= $path . "/" . $hash;

		return $dir;
	}

}