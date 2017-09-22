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

    /* @var \phpbb\user_loader */
    protected $user_loader;

    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup'                   => 'load_language_on_setup',
            'core.submit_post_end'              => 'update_private_users_and_mods',
            'core.posting_modify_template_vars' => 'inject_posting_template_vars',
        );
    }

    public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db, \phpbb\user_loader $user_loader)
    {
        $this->helper = $helper;
        $this->template = $template;
        $this->request = $request;
        $this->db = $db;
        $this->user_loader = $user_loader;
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

    private function can_see_private_topics($event) {
        $mode = $event['mode'];
        $post_id = $event['data']['post_id'];
        $topic_first_post_id = $event['data']['topic_first_post_id'];

        return $mode == 'post' || ($mode == 'edit' && $topic_first_post_id == $post_id);
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
        if ($this->can_see_private_topics($event)) {
            $topic_id = $event['data']['topic_id'];

            $new_users = $this->request->variable('pt_users', array(''));
            if ($new_users == array('')){
                $new_users = array();  //this request->variable method requires you to type the elements of your default arg, or it will not behave like you want
            }
            $this->update_private_entities($topic_id, $new_users, 'phpbb_private_topic_users');
        
            $new_mods = $this->request->variable('pt_mods', array(''));
            if ($new_mods == array('')){
                $new_mods = array();  //this request->variable method requires you to type the elements of your default arg, or it will not behave like you want
            }
            $this->update_private_entities($topic_id, $new_mods, 'phpbb_topic_mod');

            $is_private = $this->request->variable('topic_privacy', 0);
            $sql = 'UPDATE phpbb_topics SET is_private = ' . $is_private . ' WHERE topic_id = ' . $topic_id;
            $this->db->sql_query($sql);
        }
    }

    public function inject_posting_template_vars($event)
    {
        if ($this->can_see_private_topics($event)) {
            $topic_id = $event['topic_id'];
            
            $sql = 'SELECT user_id
                FROM phpbb_private_topic_users
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
                FROM phpbb_topic_mod
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
                    FROM phpbb_topics
                    WHERE topic_id = ' . $topic_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);

            $this->template->assign_var('IS_PRIVATE', $row['is_private'] == '1');
            $this->db->sql_freeresult($result);
        }
    }
}