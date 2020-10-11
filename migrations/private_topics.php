<?php

namespace mafiascum\privatetopics\migrations;

class private_topics extends \phpbb\db\migration\migration
{

    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'private_topic_users');
    }
    
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }
    
    public function update_schema()
    {
        return array(
            'add_tables'    => array(
                $this->table_prefix . 'private_topic_users' => array(
                    'COLUMNS' => array(
                        'topic_id'             => array('UINT:10', 0),
                        'user_id'              => array('UINT:10', 0),
                    ),
                    'PRIMARY_KEY' => array('topic_id', 'user_id'),
                ),
                $this->table_prefix . 'topic_mod' => array(
                    'COLUMNS' => array(
                        'topic_id'            => array('UINT:10', 0),
                        'user_id'             => array('UINT:10', 0),
                    ),
                    'PRIMARY_KEY' => array('topic_id', 'user_id'),
                ),
            ),
            'add_columns' => array(
                $this->table_prefix . 'topics' => array(
                    'is_private' => array('UINT:4', 0),
                    'autolock_time' => array('UINT:11', 0),
                    'autolock_input' => array('VCHAR:32', ''),
                ),
                $this->table_prefix . 'forums' => array(
                    'topic_author_moderation' => array('INT:8', 0),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'topic_mod',
                $this->table_prefix . 'private_topic_users',
            ),
            'drop_columns'   => array(
                $this->table_prefix . 'topics' => array(
                    'is_private',
                    'autolock_time',
                    'autolock_input',
                ),
                $this->table_prefix . 'forums' => array(
                    'topic_author_moderation',
                ),
            ),
        );
    }
}
?>