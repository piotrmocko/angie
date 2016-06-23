<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelWordpressReplacedata extends AngieModelBaseReplacedata
{
	/**
	 * Returns all the database tables which are not part of the WordPress core
	 *
	 * @return array
	 */
	public function getNonCoreTables()
	{
		// Get a list of core tables
		$coreTables = array(
			'#__commentmeta', '#__comments', '#__links', '#__options', '#__postmeta', '#__posts',
			'#__term_relationships', '#__term_taxonomy', '#__terms', '#__usermeta', '#__users',
		);

		$db = $this->getDbo();

		if ($this->isMultisite())
		{
			$additionalTables = array('#__blogs', '#__site', '#__sitemeta');

			/** @var AngieModelWordpressConfiguration $config */
			$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
			$mainBlogId = $config->get('blog_id_current_site', 1);

			$map     = $this->getMultisiteMap($db);
			$siteIds = array_keys($map);

			foreach ($siteIds as $id)
			{
				if ($id == $mainBlogId)
				{
					continue;
				}

				foreach ($coreTables as $table)
				{
					$additionalTables[] = str_replace('#__', '#__' . $id . '_', $table);
				}
			}

			$coreTables = array_merge($coreTables, $additionalTables);
		}

		// Now get a list of non-core tables
		$prefix       = $db->getPrefix();
		$prefixLength = strlen($prefix);
		$allTables    = $db->getTableList();

		$result = array();

		foreach ($allTables as $table)
		{
			if (substr($table, 0, $prefixLength) == $prefix)
			{
				$table = '#__' . substr($table, $prefixLength);
			}

			if (in_array($table, $coreTables))
			{
				continue;
			}

			$result[] = $table;
		}

		return $result;
	}

	/**
	 * Initialises the replacement engine
	 */
	public function initEngine()
	{
		// Get the replacements to be made
		$this->replacements = $this->getReplacements(true);

		// Add the default core tables
		$this->tables = array(
			array(
				'table'  => '#__comments',
				'method' => 'simple', 'fields' => array('comment_author_url', 'comment_content')
			),
			array(
				'table'  => '#__links',
				'method' => 'simple', 'fields' => array('link_url', 'link_image', 'link_rss'),
			),
			array(
				'table'  => '#__posts',
				'method' => 'simple', 'fields' => array('post_content', 'post_excerpt', 'guid'),
			),
			array(
				'table'  => '#__commentmeta',
				'method' => 'serialised', 'fields' => array('meta_value'),
			),
			array(
				'table'  => '#__options',
				'method' => 'serialised', 'fields' => array('option_value'),
			),
			array(
				'table'  => '#__postmeta',
				'method' => 'serialised', 'fields' => array('meta_value'),
			),
			array(
				'table'  => '#__usermeta',
				'method' => 'serialised', 'fields' => array('meta_value'),
			),
		);

		// Add multisite tables if this is a multisite installation
		$db = $this->getDbo();

		if ($this->isMultisite())
		{
			/** @var AngieModelWordpressConfiguration $config */
			$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
			$mainBlogId = $config->get('blog_id_current_site', 1);

			// First add the default core tables which are duplicated for each additional blog in the blog network
			$tables = array_merge($this->tables);
			$map    = $this->getMultisiteMap($db);

			// Run for each site in the blog network with an ID ≠ 1
			foreach ($map as $blogId => $blogPathInfo)
			{
				if ($blogId == $mainBlogId)
				{
					// This is the master site of the network; it doesn't have duplicated tables
					continue;
				}

				$blogPrefix = '#__' . $blogId . '_';

				foreach ($tables as $originalTable)
				{
					// Some tables only exist in the network master installation and must be ignored
					if (in_array($originalTable['table'], array('#__usermeta')))
					{
						continue;
					}

					// Translate the table definition
					$tableDefinition = array(
						'table'  => str_replace('#__', $blogPrefix, $originalTable['table']),
						'method' => $originalTable['method'],
						'fields' => $originalTable['fields']
					);

					// Add it to the table list
					$this->tables[] = $tableDefinition;
				}
			}

			// Finally, add some core tables which are only present in a blog network's master site
			$this->tables[] = array(
				'table'  => '#__site',
				'method' => 'simple', 'fields' => array('domain', 'path')
			);

			$this->tables[] = array(
				'table'  => '#__blogs',
				'method' => 'simple', 'fields' => array('domain', 'path')
			);

			$this->tables[] = array(
				'table'  => '#__sitemeta',
				'method' => 'serialised', 'fields' => array('meta_value'),
			);

		}

		// Get any additional tables
		$extraTables = $this->input->get('extraTables', array(), 'array');

		if ( !empty($extraTables) && is_array($extraTables))
		{
			foreach ($extraTables as $table)
			{
				$this->tables[] = array('table' => $table, 'method' => 'serialised', 'fields' => null);
			}
		}

		// Intialise the engine state
		$this->currentTable = null;
		$this->currentRow   = null;
		$this->fields       = null;
		$this->totalRows    = null;
		$this->batchSize	= $this->input->getInt('batchSize', 100);
		$this->max_exec		= $this->input->getInt('max_exec', 3);

		// Replace keys in #__options which depend on the database table prefix, if the prefix has been changed
		$this->timer = new ATimer($this->max_exec, 75);

		/** @var AngieModelWordpressConfiguration $config */
		$config    = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
		$oldPrefix = $config->get('olddbprefix');
		$newPrefix = $db->getPrefix();

		if ($oldPrefix != $newPrefix)
		{
			$optionsTables = array('#__options');

			if ($this->isMultisite())
			{
				$map     = $this->getMultisiteMap($db);
				$blogIds = array_keys($map);

				/** @var AngieModelWordpressConfiguration $config */
				$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);
				$mainBlogId = $config->get('blog_id_current_site', 1);

				foreach ($blogIds as $id)
				{
					if ($id == $mainBlogId)
					{
						continue;
					}

					$optionsTables[] = '#__' . $id . '_options';
				}
			}

			foreach ($optionsTables as $table)
			{
				$query = $db->getQuery(true)
							->update($db->qn($table))
							->set(
								$db->qn('option_name') . ' = REPLACE(' . $db->qn('option_name') . ', ' . $db->q($oldPrefix) . ', ' . $db->q($newPrefix) . ')'
							)
							->where(
								$db->qn('option_name') . ' LIKE ' . $db->q($oldPrefix . '%')
							)
							->where(
								$db->qn('option_name') . ' != REPLACE(' . $db->qn('option_name') . ', ' . $db->q($oldPrefix) . ', ' . $db->q($newPrefix) . ')'
							);

				try
				{
					$db->setQuery($query)->execute();
				}
				catch (Exception $e)
				{
					// Do nothing if the replacement fails
				}
			}
		}

		// Finally, return and let the replacement engine run
		return array('msg' => AText::_('SETUP_LBL_REPLACEDATA_MSG_INITIALISED'), 'more' => true);
	}

	public function stepEngine()
	{
		$result = parent::stepEngine();

		if (isset($result['more']) && !$result['more'])
		{
			// Am I done with DB replacement? If so let's update some files
			$this->updateFiles();
		}

		return $result;
	}

	/**
	 * Returns the default replacement values
	 *
	 * @return array
	 */
	protected function getDefaultReplacements()
	{
		$replacements = array();

		/** @var AngieModelWordpressConfiguration $config */
		$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

		// Main site's URL
		$newReplacements = $this->getDefaultReplacementsForMainSite($config);
		$replacements    = array_merge($replacements, $newReplacements);

		// Multisite's URLs
		$newReplacements = $this->getDefaultReplacementsForMultisite($config);
		$replacements    = array_merge($replacements, $newReplacements);

		// Database prefix
		$newReplacements = $this->getDefaultReplacementsForDbPrefix($config);
		$replacements    = array_merge($replacements, $newReplacements);

		// All done
		return $replacements;
	}

	/**
	 * Get the map of IDs to blog URLs
	 *
	 * @param   ADatabaseDriver $db The database connection
	 *
	 * @return  array  The map, or an empty array if this is not a multisite installation
	 */
	protected function getMultisiteMap($db)
	{
		static $map = null;

		if (is_null($map))
		{
			/** @var AngieModelWordpressConfiguration $config */
			$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

			// Which site ID should I use?
			$site_id = $config->get('site_id_current_site', 1);

			// Get all of the blogs of this site
			$query = $db->getQuery(true)
						->select(array(
							$db->qn('blog_id'),
							$db->qn('domain'),
							$db->qn('path'),
						))
						->from($db->qn('#__blogs'))
						->where($db->qn('site_id') . ' = ' . $db->q($site_id))
			;

			try
			{
				$map = $db->setQuery($query)->loadAssocList('blog_id');
			}
			catch (Exception $e)
			{
				$map = array();
			}
		}

		return $map;
	}

	/**
	 * Is this a multisite installation?
	 *
	 * @return  bool  True if this is a multisite installation
	 */
	protected function isMultisite()
	{
		/** @var AngieModelWordpressConfiguration $config */
		$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

		return $config->get('multisite', false);
	}

	/**
	 * Internal method to get the default replacements for the main site URL
	 *
	 * @param   AngieModelWordpressConfiguration $config The configuration model
	 *
	 * @return  array  Any replacements to add
	 */
	private function getDefaultReplacementsForMainSite($config)
	{
		$replacements = array();

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		if ($old_url == $new_url)
		{
			return $replacements;
		}

		// Let's get the reference of the previous absolute path
		/** @var AngieModelBaseMain $mainModel */
		$mainModel  = AModel::getAnInstance('Main', 'AngieModel', array(), $this->container);
		$extra_info = $mainModel->getExtraInfo();

		if (isset($extra_info['root']) && $extra_info['root'])
		{
			$old_path = trim($extra_info['root']['current'], '/');
			$new_path = trim(APATH_SITE, '/');

			$replacements[$old_path] = $new_path;
		}

		// Replace the absolute URL to the site
		$replacements[$old_url] = $new_url;

		// If the relative path to the site is different, replace it too.
		$oldUri = new AUri($old_url);
		$newUri = new AUri($new_url);

		$oldPath = $oldUri->getPath();
		$newPath = $newUri->getPath();

		if ($oldPath != $newPath)
		{
			$replacements[$oldPath] = $newPath;

			return $replacements;
		}

		return $replacements;
	}

	/**
	 * Internal method to get the default replacements for multisite's URLs
	 *
	 * @param   AngieModelWordpressConfiguration $config The configuration model
	 *
	 * @return  array  Any replacements to add
	 */
	private function getDefaultReplacementsForMultisite($config)
	{
		$replacements = array();
		$db           = $this->getDbo();

		if ( !$this->isMultisite())
		{
			return $replacements;
		}

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		// If the URL didn't change do nothing
		if ($old_url == $new_url)
		{
			return $replacements;
		}

		// Get the old and new base domain and base path
		$oldUri = new AUri($old_url);
		$newUri = new AUri($new_url);

		$newDomain = $this->removeSubdomain($newUri->getHost());
		$oldDomain = $config->get('domain_current_site', $oldUri->getHost());

		$newPath = $newUri->getPath();
		$oldPath = $config->get('path_current_site', $oldUri->getPath());

		// If the old and new domain are subdomains of the same root domain (e.g. abc.example.com and xyz.example.com),
		// or a subdomain and a root domain (e.g. example.com and abc.example.com) we MUST NOT do domain replacement
		$replaceSubdomains = $this->removeSubdomain($newDomain) != $this->removeSubdomain($oldDomain);

		// If the old and new paths are the same we MUST NOT do path replacement
		$replacePaths = $oldPath != $newPath;

		// Get the multisites information
		$multiSites = $this->getMultisiteMap($db);

		// Get other information
		$mainBlogId = $config->get('blog_id_current_site', 1);
		$useSubdomains = $config->get('subdomain_install', 0);

		// Do I have to replace the domain?
		if ($oldDomain != $newDomain)
		{
			$replacements[$oldDomain] = $newUri->getHost();
		}

		// Maybe I have to do... nothing?
		if ($useSubdomains && !$replaceSubdomains)
		{
			return $replacements;
		}

		if (!$useSubdomains)
		{
			if (!$replacePaths)
			{
				return $replacements;
			}
		}

		// Loop for each multisite
		foreach ($multiSites as $blogId => $info)
		{
			// Skip the first site, it is the same as the main site
			if ($blogId == $mainBlogId)
			{
				continue;
			}

			// Multisites using subdomains?
			if ($useSubdomains)
			{
				// Extract the subdomain
				$subdomain = substr($info['domain'], 0, -strlen($oldDomain));

				// Add a replacement for this domain
				$replacements[$info['domain']] = $subdomain . $newDomain;

				continue;
			}

			// Multisites using subdirectories. Let's check if I have to extract the old path.
			$path = (strpos($info['path'], $oldPath) === 0) ? substr($info['path'], strlen($oldPath)) : $info['path'];

			// Construct the new path and add it to the list of replacements
			$path = trim($path, '/');
			$newMSPath = $newPath . '/' . $path;
			$newMSPath = trim($newMSPath, '/');
			$replacements[$info['path']] = '/' . $newMSPath;
		}

		// Important! We have to change subdomains BEFORE the main domain. And for this, we need to reverse the
		// replacements table. If you're wondering why: old domain example.com, new domain www.example.net. This
		// makes blog1.example.com => blog1.www.example.net instead of blog1.example.net (note the extra www). Oops!
		$replacements = array_reverse($replacements);

		return $replacements;
	}

	/**
	 * Internal method to get the default replacements for the database prefix
	 *
	 * @param   AngieModelWordpressConfiguration $config The configuration model
	 *
	 * @return  array  Any replacements to add
	 */
	private function getDefaultReplacementsForDbPrefix($config)
	{
		$replacements = array();

		// Replace the table prefix if it's different
		$db        = $this->getDbo();
		$oldPrefix = $config->get('olddbprefix');
		$newPrefix = $db->getPrefix();

		if ($oldPrefix != $newPrefix)
		{
			$replacements[$oldPrefix] = $newPrefix;

			return $replacements;
		}

		return $replacements;
	}

	/**
	 * Updates known files that are storing absolute paths inside them
	 */
	private function updateFiles()
	{
		$files = array(
			// I'll try to apply the changes to those files and their "backup" counterpart
			APATH_SITE . '/.htaccess',
			APATH_SITE . '/htaccess.bak',
			APATH_SITE . '/.user.ini.bak',
			APATH_SITE . '/.user.ini',
			APATH_SITE . '/php.ini',
			APATH_SITE . '/php.ini.bak',
			// Wordfence is storing the absolute path inside their file. Because __DIR__ is too mainstream..
			APATH_SITE . '/wordfence-waf.php',
		);

		foreach ($files as $file)
		{
			if (!file_exists($file))
			{
				continue;
			}

			$contents = file_get_contents($file);

			foreach ($this->replacements as $from => $to)
			{
				$contents = str_replace($from, $to, $contents);
			}

			file_put_contents($file, $contents);
		}
	}
}