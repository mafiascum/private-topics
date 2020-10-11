<?php

namespace mafiascum\privatetopics\migrations;

class private_topics_permission_type extends \phpbb\db\migration\migration
{

    public function effectively_installed()
    {
        return $this->db_tools->sql_column_exists($this->table_prefix . 'private_topic_users', 'permission_type');
    }
    
    static public function depends_on()
    {
        return array('\mafiascum\privatetopics\migrations\private_topics');
    }

    public function update_schema()
    {
        return array(
            'add_columns' => array(
                $this->table_prefix . 'private_topic_users' => array(
                    'permission_type' => array('UINT:4', 1),
                ),
            ),
        );
    }

    public function revert_schema()
    {
         return array(
            'drop_columns'   => array(
                $this->table_prefix . 'private_topic_users' => array(
                    'permission_type',
                ),
            ),
         );
    }
}