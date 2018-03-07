<?php
/**
 *
 * @package phpBB Extension - Mafiascum Private Topics
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace mafiascum\privateTopics;

class Utils {
    public static function is_user_authorized_for_topic($db, $auth, $user_id, $forum_id, $topic_id) {
        global $table_prefix;
        
        if (!$auth->acl_get('f_read', $forum_id)) {
            return false;
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

        return 'LEFT JOIN ' . $table_prefix . 'private_topic_users tu ON ' . $table_alias . '.topic_id = tu.topic_id AND tu.user_id = ' . $user_id . '
                LEFT JOIN ' . $table_prefix . 'topic_mod tm ON ' . $table_alias . '.topic_id = tm.topic_id AND tm.user_id = '. $user_id;
    }

    public static function pt_where_clause($table_alias = 't') {
        return '(' . $table_alias . '.is_private = 0 OR tu.topic_id IS NOT NULL OR tm.topic_id IS NOT NULL)';
    }
}