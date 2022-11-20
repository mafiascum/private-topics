<?php

namespace mafiascum\privatetopics\controller;

require_once(dirname(__FILE__) . "/../utils.php");

use mafiascum\privatetopics\Utils;

class lock
{
    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\db\driver\driver */
    protected $db;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\auth\auth */
    protected $auth;

    /* @var \phpbb\log\log */
    protected $phpbb_log;

    /* @var \phpbb\event\dispatcher */
    protected $phpbb_dispatcher;
    
    public function __construct(\phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\log\log $log, \phpbb\event\dispatcher $dispatcher, $table_prefix, $root_path, $php_ext)
    {
        $this->request = $request;
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->phpbb_log = $log;
        $this->phpbb_dispatcher = $dispatcher;
        $this->table_prefix = $table_prefix;
        $this->phpbb_root_path = $root_path;
        $this->php_ext = $php_ext;
    }
    
    // adapted from /phpBB/includes/mcp/mcp_main.php
    // That code seems inseparable from the permissions check, which we have already made via our looser constraints
    private function lock_unlock($action, $ids) {
        $redirect = $this->request->variable('redirect', build_url(array('action', 'quickmod')));
        $redirect = reapply_sid($redirect);
        $sql_id = 'topic_id';
        $l_prefix = 'TOPIC';

        $s_hidden_fields = build_hidden_fields(array(
            'topic_id_list'	    => $ids,
            'action'			=> $action,
            'redirect'			=> $redirect)
        );
        
        if (confirm_box(true)) {
            $sql = 'UPDATE ' . $this->table_prefix . 'topics
                SET topic_status = ' . ($action == 'lock' ? ITEM_LOCKED : ITEM_UNLOCKED) . '
                WHERE ' . $this->db->sql_in_set($sql_id, $ids);
            $this->db->sql_query($sql);

            if (!function_exists('phpbb_get_topic_data')) {
                include($this->phpbb_root_path . 'includes/functions_mcp.' . $this->php_ext);
            }

            $data = phpbb_get_topic_data($ids);

            foreach ($data as $id => $row)
            {
                $this->phpbb_log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_' . strtoupper($action), false, array(
                    'forum_id' => $row['forum_id'],
                    'topic_id' => $row['topic_id'],
                    'post_id'  => isset($row['post_id']) ? $row['post_id'] : 0,
                    $row['topic_title']
                ));
            }

            /**
             * Perform additional actions after locking/unlocking posts/topics
             *
             * @event core.mcp_lock_unlock_after
             * @var	string	action				Variable containing the action we perform on the posts/topics ('lock', 'unlock', 'lock_post' or 'unlock_post')
             * @var	array	ids					Array containing the post/topic IDs that have been locked/unlocked
             * @var	array	data				Array containing posts/topics data
             * @since 3.1.7-RC1
             */
            $vars = array(
                'action',
                'ids',
                'data',
            );
            extract($this->phpbb_dispatcher->trigger_event('core.mcp_lock_unlock_after', compact($vars)));

            $success_msg = $l_prefix . ((sizeof($ids) == 1) ? '' : 'S') . '_' . (($action == 'lock' || $action == 'lock_post') ? 'LOCKED' : 'UNLOCKED') . '_SUCCESS';

            meta_refresh(2, $redirect);
            $message = $this->user->lang[$success_msg];

            if (!$this->request->is_ajax())
            {
                $message .= '<br /><br />' . $this->user->lang('RETURN_PAGE', '<a href="' . $redirect . '">', '</a>');
            }
            trigger_error($message);
        } else {
            confirm_box(false, strtoupper($action) . '_' . $l_prefix . ((sizeof($ids) == 1) ? '' : 'S'), $s_hidden_fields);
        }

        redirect($redirect);
    }
    
    public function handle()
    {        
        $topic_id = $this->request->variable('t', 0);
        
        if (!$topic_id) {
            trigger_error('NO_TOPIC');
        }

        $action = $this->request->variable('action', '');

        if (!$action) {
            trigger_error('NO_ACTION'); 
        }

        $sql = 'SELECT forum_id, topic_poster FROM ' . $this->table_prefix . 'topics
                WHERE topic_id = ' . $topic_id;

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);

        $forum_id = $row['forum_id'];
        $topic_poster = $row['topic_poster'];

        if (!$forum_id) {
            trigger_error('NO_TOPIC');
        }

        $has_lock_permissions = Utils::is_moderator_by_permissions('lock', $this->auth, $this->user, $forum_id);

        $lock_allowed = $has_lock_permissions || Utils::is_moderator_by_topic_moderation(
            $this->db,
            $this->table_prefix,
            $this->user->data['user_id'],
            $forum_id,
            $topic_id,
            $topic_poster,
            null
        );

        self::lock_unlock($action, array($topic_id));
    }
}