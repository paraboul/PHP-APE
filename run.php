<?php	

gc_enable();

require(dirname(__FILE__) . '/lib/ApeTask.class.php');

$task = new ApeTask();

$task->setNewClientEvery(1);
$task->setNumClients(250);
$task->setCheckInterval(8);

$task->start();
		
?>
