<?php

namespace mafiascum\privateTopics\migrations;

class cron extends \phpbb\db\migration\migration
{
   public function effectively_installed()
   {
      return isset($this->config['mafiascum_privateTopics_autolock_gc']);
   }

   static public function depends_on()
   {
      return array('\phpbb\db\migration\data\v310\dev');
   }

   public function update_data()
   {
      return array(
         array('config.add', array('mafiascum_privateTopics_autolock_last_gc', 0)), // last run
         array('config.add', array('mafiascum_privateTopics_autolock_gc', 60)), // seconds between run; 1 minute
      );
   }
}
?>