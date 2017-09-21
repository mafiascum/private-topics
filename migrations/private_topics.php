<?php

namespace mafiascum\privateTopics\migrations;

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
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'topic_mod',
                $this->table_prefix . 'private_topic_users',
            ),
        );
    }
}
?>