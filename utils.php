<?php
/**
 *
 * @package phpBB Extension - Mafiascum Private Topics
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace mafiascum\privatetopics;

class Utils {
    public static function is_user_authorized_for_topic($db, $auth, $user_id, $forum_id, $topic_id) {
        global $table_prefix;
        
        if (!$auth->acl_get('f_read', $forum_id)) {
            return false;
        } else if ($auth->acl_get('m_edit', $forum_id)) { 
            return true;
        } else {
            $sql = 'SELECT count(*) as cnt
                FROM ' . $table_prefix . 'topics t ' . self::pt_join_clause($user_id) . '
                WHERE t.topic_id = ' . $topic_id . ' AND ' . self::pt_where_clause();
            
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $is_authorized = $row['cnt'] > 0;
            $db->sql_freeresult($result);
            return $is_authorized;
        }
    }

    public static function pt_join_clause($user_id, $table_alias = 't') {
        global $table_prefix;

        return 'LEFT JOIN ' . $table_prefix . 'private_topic_users tu ON (' . $table_alias . '.topic_id = tu.topic_id AND tu.user_id = ' . $user_id . ')
                LEFT JOIN ' . $table_prefix . 'topic_mod tm ON (' . $table_alias . '.topic_id = tm.topic_id AND tm.user_id = '. $user_id . ')';
	}
	
	public static function pt_append_join_clause(&$left_join, $user_id, $table_alias = 't') {
		global $table_prefix;

		$left_join[] = array(
			'FROM' => array(
				$table_prefix . 'private_topic_users' => 'tu'
			),
			'ON' => $table_alias . '.topic_id = tu.topic_id AND tu.user_id = ' . $user_id
		);

		$left_join[] = array(
			'FROM' => array(
				$table_prefix . 'topic_mod' => 'tm'
			),
			'ON' => $table_alias . '.topic_id = tm.topic_id AND tm.user_id = '. $user_id
		);
	}

    public static function pt_where_clause($table_alias = 't') {
        return '(' . $table_alias . '.is_private = 0 OR tu.topic_id IS NOT NULL OR tm.topic_id IS NOT NULL)';
    }

    public static function is_moderator_by_permissions($action, $auth, $user, $forum_id) {  
        if ($action === 'lock') {
            // Can lock topics in this forum
            if ($auth->acl_get('m_lock', $forum_id)) {
                return true;
            }

            // Has permission to lock own topics, and this is my own topic
            if ($auth->acl_get('f_user_lock', $forum_id) && 
                $user->data['is_registered'] &&
                !empty($post_data['topic_poster']) && 
                $user->data['user_id'] == $post_data['topic_poster']) {
                return true;
            }
        } else if ($action == 'edit') {
            if ($auth->acl_get('m_edit', $forum_id)) {
                return true;
            } 
        } else if ($action == 'delete') {
            if ($auth->acl_get('m_delete', $forum_id)) {
                return true;
            } 
        }

        return false;
    }

    public static function is_moderator_by_topic_moderation($db, $table_prefix, $user_id, $forum_id, $topic_id, $topic_poster, $topic_author_moderation) {
        // topic author moderation hasn't been queried yet because we're on some postback that doesn't have post data on it
        if ($topic_author_moderation === null) {
            $sql = 'SELECT topic_author_moderation FROM ' . $table_prefix . 'forums' . ' WHERE forum_id = ' . $forum_id;
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $topic_author_moderation = $row['topic_author_moderation'] === 1;
        }

        // Topic moderation is not enabled and user has no other permission
        if (!$topic_author_moderation) {
            return false;
        }

        // Topic moderation is enabled and user is the original author
        if ($topic_poster && $user_id == $topic_poster) {
            return true;
        }

        // otherwise, see if extra permission has been given via the moderation widget
        $sql = 'SELECT count(*) cnt FROM ' . $table_prefix . 'topic_mod' . ' WHERE user_id = ' . $user_id . ' AND topic_id = ' . $topic_id;
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        return $row['cnt'] > 0;
    }
}