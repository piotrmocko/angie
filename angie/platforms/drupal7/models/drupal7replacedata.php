<?php
/**
 * @package   angi4j
 * @copyright Copyright (C) 2009-2016 Nicholas K. Dionysopoulos. All rights reserved.
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieModelDrupal7Replacedata extends AngieModelBaseReplacedata
{

	public function getNonCoreTables()
	{
		$coreTables = array(
			"#__actions", "#__authmap", "#__batch", "#__block", "#__block_custom", "#__block_node_type",
			"#__block_role", "#__blocked_ips", "#__cache", "#__cache_block", "#__cache_bootstrap",
			"#__cache_field", "#__cache_filter", "#__cache_form", "#__cache_image", "#__cache_menu",
			"#__cache_page", "#__cache_path", "#__comment", "#__date_format_locale", "#__date_format_type",
			"#__date_formats", "#__field_config", "#__field_config_instance", "#__field_data_body",
			"#__field_data_comment_body", "#__field_data_field_image", "#__field_data_field_tags",
			"#__field_revision_body", "#__field_revision_comment_body", "#__field_revision_field_image",
			"#__field_revision_field_tags", "#__file_managed", "#__file_usage", "#__filter", "#__filter_format",
			"#__flood", "#__history", "#__image_effects", "#__image_styles", "#__menu_custom", "#__menu_links",
			"#__menu_router", "#__node", "#__node_access", "#__node_comment_statistics",
			"#__node_revision", "#__node_type", "#__queue", "#__rdf_mapping", "#__registry",
			"#__registry_file", "#__role", "#__role_permission", "#__search_dataset", "#__search_index",
			"#__search_node_links", "#__search_total", "#__semaphore", "#__sequences", "#__sessions",
			"#__shortcut_set", "#__shortcut_set_users", "#__system", "#__taxonomy_index", "#__taxonomy_term_data",
			"#__taxonomy_term_hierarchy", "#__taxonomy_vocabulary", "#__url_alias", "#__users", "#__users_roles",
			"#__variable", "#__watchdog"
		);

		$db = $this->getDbo();

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

	public function initEngine()
	{
		// Get the replacements to be made
		$this->replacements = $this->getReplacements(true);

		// Add the default core tables
		$this->tables = array(
			/*array(
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
			),*/
		);

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

		// Finally, return and let the replacement engine run
		return array('msg' => AText::_('SETUP_LBL_REPLACEDATA_MSG_INITIALISED'), 'more' => true);
	}

	protected function getDefaultReplacements()
	{
		/** @var AngieModelDrupal7Configuration $config */
		$config = AModel::getAnInstance('Configuration', 'AngieModel', array(), $this->container);

		$replacements = array();

		// These values are stored inside the session, after the setup step
		$old_url = $config->get('oldurl');
		$new_url = $config->get('homeurl');

		if ($old_url == $new_url)
		{
			return $replacements;
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
}