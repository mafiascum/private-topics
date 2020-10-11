<?php
namespace mafiascum\privatetopics\task;

class mafiascum_privatetopics_autolock extends \phpbb\cron\task\base {
    protected $config;
    
    /* @var \phpbb\db\driver\driver */
    protected $db;

    public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db) {
        $this->config = $config;
        $this->db = $db;
    }

    public function run() {
        global $phpEx, $phpbb_root_path;
        include_once($phpbb_root_path . 'common.' . $phpEx);
        include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

        $update_arr = array(
            'topic_status'		=> ITEM_LOCKED,
            'autolock_time'		=> 0,
            'autolock_input'	=> ''
        );

        $sql = 'UPDATE ' . TOPICS_TABLE . '
		SET
			topic_status=IF(topic_status=' . ITEM_UNLOCKED . ',' . ITEM_LOCKED . ',topic_status),
			autolock_time=0,
			autolock_input=""
		WHERE autolock_time <= ' . time() . '
		AND autolock_time != 0';
        
        $this->db->sql_query($sql);

        $this->config->set('mafiascum_privatetopics_autolock_last_gc', time());
    }

    public function should_run() {
        return $this->config['mafiascum_privatetopics_autolock_last_gc'] < time() - $this->config['mafiascum_privatetopics_autolock_gc'];
    }
    
}
?>