<?php namespace CodeIgniter\CLI;

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	CodeIgniter Dev Team
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */

use CodeIgniter\Controller;

class CommandRunner extends Controller
{
	/**
	 * Stores the info about found Commands.
	 *
	 * @var array
	 */
	protected $commands = [];

	//--------------------------------------------------------------------

	/**
	 * We map all un-routed CLI methods through this function
	 * so we have the chance to look for a Command first.
	 *
	 * @param       $method
	 * @param array ...$params
	 */
	public function _remap($method, ...$params)
	{
		// The first param is usually empty, so scrap it.
		if (empty($params[0]))
		{
			array_shift($params);
		}

		$this->index($params);
	}

	//--------------------------------------------------------------------

	public function index(array $params)
	{
		$command = array_shift($params);

		$this->createCommandList($command);

		if (is_null($command))
		{
			$command = 'help';
		}

		return $this->runCommand($command, $params);
	}

	//--------------------------------------------------------------------

	/**
	 * Actually runs the command.
	 *
	 * @param string $command
	 */
	protected function runCommand(string $command, array $params)
	{
		if (! isset($this->commands[$command]))
		{
			CLI::error('Command \''.$command.'\' not found');
			CLI::newLine();
			return;
		}

		// The file would have already been loaded during the
		// createCommandList function...
		$className = $this->commands[$command]['class'];
		$class = new $className($this->logger, $this);

		return $class->run($params);
	}

	//--------------------------------------------------------------------

	/**
	 * Scans all Commands directories and prepares a list
	 * of each command with it's group and file.
	 *
	 * @return null|void
	 */
	protected function createCommandList()
	{
		$files = service('locator')->listFiles("Commands/");

		// If no matching command files were found, bail
		if (empty($files))
		{
			return;
		}

		// Loop over each file checking to see if a command with that
		// alias exists in the class. If so, return it. Otherwise, try the next.
		foreach ($files as $file)
		{
			$className = service('locator')->findQualifiedNameFromPath($file);

			if (empty($className) || ! class_exists($className))
			{
				continue;
			}

			$class = new $className($this->logger, $this);

			// Store it!
			if ($class->group !== null)
			{
				$this->commands[$class->name] = [
					'class' => $className,
					'file' => $file,
					'group' => $class->group,
					'description' => $class->description
				];
			}

			$class = null;
			unset($class);
		}

		asort($this->commands);
	}

	//--------------------------------------------------------------------

	/**
	 * Allows access to the current commands that have been found.
	 *
	 * @return array
	 */
	public function getCommands()
	{
		return $this->commands;
	}

	//--------------------------------------------------------------------
}