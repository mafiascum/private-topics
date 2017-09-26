<?php
/**
 *
 * @package phpBB Extension - Mafiascum Private Topics
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace mafiascum\privateTopics\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{
    
    /* @var \phpbb\controller\helper */
    protected $helper;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\db\driver\driver */
	protected $db;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\user_loader */
    protected $user_loader;

    /* phpbb\language\language */
    protected $language;

    static public function getSubscribedEvents()
    {
        return array(
            'core.display_forums_modify_row'               => 'replace_accurate_last_posts',
            'core.display_forums_modify_sql'               => 'get_accurate_last_posts',
            'core.modify_posting_auth'                     => 'require_authorized_for_private_topic',
            'core.posting_modify_template_vars'            => 'inject_posting_template_vars',
            'core.search_mysql_author_query_before'        => 'filter_unauthorized_author_search_private_topics',
            'core.search_mysql_keywords_main_query_before' => 'filter_unauthorized_keyword_search_private_topics',
            'core.submit_post_end'                         => 'update_private_users_and_mods',
            'core.viewforum_modify_topics_data'            => 'filter_unauthorized_chosen_private_topics',
            'core.viewtopic_assign_template_vars_before'   => 'add_private_label_to_current_topic',
            'core.viewtopic_before_f_read_check'           => 'require_authorized_for_private_topic',
            'core.user_setup'                              => 'load_language_on_setup',
        );
    }

    public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, $table_prefix)
    {
        $this->helper = $helper;
        $this->template = $template;
        $this->request = $request;
        $this->db = $db;
        $this->user = $user;
        $this->user_loader = $user_loader;
        $this->language = $language;
        $this->table_prefix = $table_prefix;
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'mafiascum/privateTopics',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }

    private function will_configure_private_topics($event) {
        $mode = $event['mode'];
        $post_id = $event['data']['post_id'];
        $topic_first_post_id = $event['data']['topic_first_post_id'];

        return $mode == 'post' || ($mode == 'edit' && $topic_first_post_id == $post_id);
    }

    private function pt_join_clause($user_id, $table_alias = 't') {
        return 'LEFT JOIN ' . $this->table_prefix . 'private_topic_users tu ON ' . $table_alias . '.topic_id = tu.topic_id AND tu.user_id = ' . $user_id . '
                LEFT JOIN ' . $this->table_prefix . 'topic_mod tm ON ' . $table_alias . '.topic_id = tm.topic_id AND tm.user_id = '. $user_id;
    }

    private function pt_where_clause($table_alias = 't') {
        return '(' . $table_alias . '.is_private = 0 OR tu.topic_id IS NOT NULL OR tm.topic_id IS NOT NULL)';
    }

    private function is_user_authorized_for_topic($user_id, $topic_id) {
        $sql = 'SELECT count(*) as cnt
                FROM ' . $this->table_prefix . 'topics t ' . $this->pt_join_clause($user_id) . '
                WHERE t.topic_id = ' . $topic_id . ' AND ' . $this->pt_where_clause();

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $is_authorized = $row['cnt'] > 0;
        $this->db->sql_freeresult($result);
        return $is_authorized;
    }

    private function get_authorized_topics_in_list($user_id, $topic_list)
    {
        $sql = 'SELECT t.topic_id, t.is_private FROM ' . $this->table_prefix . 'topics t ' . $this->pt_join_clause($user_id) . '
                WHERE ' . $this->db->sql_in_set('t.topic_id', $topic_list) . '
                AND ' . $this->pt_where_clause();

        $topics = array();
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result)) {
            $topics[$row['topic_id']] = $row['is_private'];
        }
        $this->db->sql_freeresult($result);
        return $topics;
    }

    private function update_private_entities($topic_id, $new_users, $table_name)
    {
        $old_users = array();

        $sql = 'SELECT user_id FROM ' . $table_name . ' WHERE topic_id=' . $topic_id;
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result)) {
            $old_users[] = $row['user_id'];
        }
        $this->db->sql_freeresult($result);
        
        $private_users_add = array_diff($new_users, $old_users);
        $private_users_remove = array_diff($old_users, $new_users);
        
        if (sizeof($private_users_remove)) {
            $sql = 'DELETE FROM ' . $table_name . ' WHERE topic_id=' . $topic_id . ' AND user_id IN (' . implode(',',$private_users_remove) . ');';
            $this->db->sql_query($sql);
        }
        if (sizeof($private_users_add)) {
            foreach ($private_users_add as $user_id) {
                    $sql = 'INSERT INTO ' . $table_name . ' (user_id, topic_id) VALUES('. $user_id .','. $topic_id .');';
                    $this->db->sql_query($sql);
            }
        }
    }

    public function update_private_users_and_mods($event)
    {
        if ($this->will_configure_private_topics($event)) {
            $topic_id = $event['data']['topic_id'];

            $new_users = $this->request->variable('pt_users', array(''));
            if ($new_users == array('')){
                $new_users = array();  //this request->variable method requires you to type the elements of your default arg, or it will not behave like you want
            }
            $this->update_private_entities($topic_id, $new_users, $this->table_prefix . 'private_topic_users');
        
            $new_mods = $this->request->variable('pt_mods', array(''));
            if ($new_mods == array('')){
                $new_mods = array();  //this request->variable method requires you to type the elements of your default arg, or it will not behave like you want
            }
            $this->update_private_entities($topic_id, $new_mods, $this->table_prefix . 'topic_mod');

            $is_private = $this->request->variable('topic_privacy', 0);
            $sql = 'UPDATE ' . $this->table_prefix . 'topics SET is_private = ' . $is_private . ' WHERE topic_id = ' . $topic_id;
            $this->db->sql_query($sql);
        }
    }

    public function inject_posting_template_vars($event)
    {
        if ($this->will_configure_private_topics($event)) {
            $topic_id = $event['topic_id'];
            
            $sql = 'SELECT user_id
                FROM ' . $this->table_prefix . 'private_topic_users
                WHERE topic_id = ' . $topic_id;
            
            $result = $this->db->sql_query($sql);
            while ($row = $this->db->sql_fetchrow($result))
            {
                $user_id = $row['user_id'];
                $this->user_loader->load_users(array($user_id));
                $username_formatted = $this->user_loader->get_username($user_id, 'username');
                $username_profile = $this->user_loader->get_username($user_id, 'profile');
                
                $this->template->assign_block_vars('PT_USERS', array(
                    'USER_ID'       => $user_id,
                    'USERNAME'      => $username_formatted,
                    'PROFILE'       => $username_profile,
                ));
            }
            $this->db->sql_freeresult($result);
            
            $sql = 'SELECT user_id
                FROM ' . $this->table_prefix . 'topic_mod
                WHERE topic_id = ' . $topic_id;
            
            $result = $this->db->sql_query($sql);
            while ($row = $this->db->sql_fetchrow($result))
            {
                $user_id = $row['user_id'];
                $this->user_loader->load_users(array($user_id));
                $username_formatted = $this->user_loader->get_username($user_id, 'username');
                $username_profile = $this->user_loader->get_username($user_id, 'profile');
                
                $this->template->assign_block_vars('PT_MODS', array(
                    'USER_ID'       => $user_id,
                    'USERNAME'      => $username_formatted,
                    'PROFILE'       => $username_profile,
                ));
            }
            $this->db->sql_freeresult($result);

            $sql = 'SELECT is_private
                    FROM ' . $this->table_prefix . 'topics
                    WHERE topic_id = ' . $topic_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);

            $this->template->assign_var('IS_PRIVATE', $row['is_private'] == '1');
            $this->db->sql_freeresult($result);
        }
    }

    public function require_authorized_for_private_topic($event) {
        $is_pt_authed = $this->is_user_authorized_for_topic(
            $this->user->data['user_id'],
            $event['topic_id']
        );

        // check to see if caller has an auth variable to merge with
        // otherwise, we have to potentially throw our own error
        if (isset($event['is_authed'])) {
            $event['is_authed'] = $event['is_authed'] && $is_pt_authed;
        }
        else {
            if (!$is_pt_authed) {
                trigger_error('SORRY_AUTH_READ');
            }
        }
    }

    public function get_accurate_last_posts($event) {
        $user_id = $this->user->data['user_id'];
        $sql_array = $event['sql_ary'];

        $sql_array['SELECT'] .= ', t.topic_id, t.topic_last_post_id, t.topic_last_post_subject, t.topic_last_post_time,
                                     t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour';
        $sql_array['LEFT_JOIN'][] = array(
            'FROM' => array('(SELECT t.forum_id as t_forum_id, t.topic_id, topic_last_post_id, topic_last_post_subject, topic_last_post_time,
                                     topic_last_poster_id, topic_last_poster_name, topic_last_poster_colour,
                                     ROW_NUMBER() OVER (PARTITION BY forum_id ORDER BY topic_last_post_time desc ) as rank
                              FROM ' . $this->table_prefix . 'topics t ' . $this->pt_join_clause($user_id) . ' WHERE ' . $this->pt_where_clause() . ')' => 't'),
            'ON' => 'f.forum_id = t.t_forum_id AND t.rank = 1'
        );
        $event['sql_ary'] = $sql_array;
    }

    public function replace_accurate_last_posts($event) {
        $row = $event['row'];
        if (!is_null($row['topic_id'])) {
            $row['forum_last_post_id'] = $row['topic_last_post_id'];
            $row['forum_last_post_subject'] = $row['topic_last_post_subject'];
            $row['forum_last_post_time'] = $row['topic_last_post_time'];
            $row['forum_last_poster_id'] = $row['topic_last_poster_id'];
            $row['forum_last_poster_name'] = $row['topic_last_poster_name'];
            $row['forum_last_poster_colour'] = $row['topic_last_poster_colour'];
        } else {
            $row['forum_last_post_id'] = '--';
            $row['forum_last_post_subject'] = '--';
            $row['forum_last_post_time'] = '--';
            $row['forum_last_poster_id'] = '--';
            $row['forum_last_poster_name'] = '--';
            $row['forum_last_poster_colour'] = '--';
        }
        $event['row'] = $row;
    }

    public function filter_unauthorized_chosen_private_topics($event) {
        $authorized_topics = $this->get_authorized_topics_in_list(
             $this->user->data['user_id'],
             $event['topic_list']
        );
        $ordered_authorized_topics = array();

        $rowset = $event['rowset'];
        foreach ($event['topic_list'] as $topic_id) {
            if (array_key_exists($topic_id, $authorized_topics)) {
                $ordered_authorized_topics[] = $topic_id;

                if ($authorized_topics[$topic_id] == '1') {
                    $rowset[$topic_id]['topic_title'] = $this->language->lang('PRIVATE_TOPIC_LABEL') . $rowset[$topic_id]['topic_title'];
                }
            }
        }

        $event['topic_list'] = $ordered_authorized_topics;
        $event['total_topic_count'] = count($event['topic_list']);
        $event['rowset'] = $rowset;
    }

    public function filter_unauthorized_keyword_search_private_topics($event) {
        $user_id = $this->user->data['user_id'];

        $event['sql_match_where'] .= ' AND p.topic_id IN (
            SELECT t1.topic_id FROM ' . $this->table_prefix . 'topics t1 ' . $this->pt_join_clause($user_id, 't1') . '
            WHERE ' . $this->pt_where_clause('t1') . '
        )';
    }

    public function filter_unauthorized_author_search_private_topics($event) {
        $user_id = $this->user->data['user_id'];

        $event['sql_topic_id'] .= ' AND p.topic_id IN (
            SELECT t1.topic_id FROM ' . $this->table_prefix . 'topics t1 ' . $this->pt_join_clause($user_id, 't1') . '
            WHERE ' . $this->pt_where_clause('t1') . '
        )';
    }

    public function add_private_label_to_current_topic($event) {
        $topic_data = $event['topic_data'];

        if ($topic_data['is_private'] == '1') {
            $topic_data['topic_title'] = $this->language->lang('PRIVATE_TOPIC_LABEL') . $topic_data['topic_title'];
        }

        $event['topic_data'] = $topic_data;
    }
}