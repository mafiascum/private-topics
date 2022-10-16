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
	'USERS_VIEW_PT'                   => 'Users that can view this topic:',
    'ADD_USER'                        => 'Add User',
    'USERS_MOD_PT'                    => 'Users that can <em>moderate</em> this topic:',
    'ADD_MODERATOR'                   => 'Add Moderator',
    'CHANGE_PRIVACY'                  => 'Change topic privacy to:',
    'PRIVATE'                         => 'Private',
    'PUBLIC'                          => 'Public',
    'PRIVATE_TOPIC_LABEL'             => 'PRIVATE TOPIC: ',
    'TOPIC_MODERATORS'                => 'Topic Moderators',
    'REMOVE_SELECTED'                 => 'Remove Selected',
    'QUICK_MOD'                       => 'Quick-mod tools',
    'AUTOLOCK_LABEL'                  => 'Auto-lock topic at',
    'AUTOLOCK_REMAINING_LABEL'        => 'Topic will auto-lock in approximately',
    'AUTOLOCK_PLACEHOLDER'		      => 'Ex: 2015-09-30 15:30',
	'AUTOLOCK_FORMAT'		          => 'Valid format is: YYYY-MM-DD HH:MM (ex: 2015-09-30 15:30 will lock Sep 30 2015 at 3:30PM)',
    'TOPIC_AUTHOR_MODERATION'         => 'Enable Topic Author Moderation',
    'TOPIC_AUTHOR_MODERATION_EXPLAIN' => 'If set to yes authors of topics in this forum will have moderator edit permission in their own topics.',
    'LOCK_TOPIC_CONFIRM'		      => 'Are you sure you want to lock this topic?',
    'UNLOCK_TOPIC_CONFIRM'            => 'Are you sure you want to unlock this topic?',
    'TOPIC_LOCKED_SUCCESS'		      => 'The selected topic has been locked.',
    'TOPIC_UNLOCKED_SUCCESS'	      => 'The selected topic has been unlocked.',

));
