<?php
/**
*
* @package phpBB Extension - MafiaScum Private Topics
* @copyright (c) 2017 mafiascum.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'USERS_VIEW_PT'  => 'Users that can view this topic:',
    'ADD_USER'       => 'Add User',
    'USERS_MOD_PT'   => 'Users that can <em>moderate</em> this topic:',
    'ADD_MODERATOR'  => 'Add Moderator',
    'CHANGE_PRIVACY' => 'Change topic privacy to:',
    'PRIVATE'        => 'Private',
    'PUBLIC'         => 'Public',
));
