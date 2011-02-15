<?php

require(dirname(__FILE__) . '/Ape.class.php');
require(dirname(__FILE__) . '/ApeDispatcher.class.php');

class ApeTask
{
	public $_client_every = 1;
	public $_num = 1;
	public $_check = 8;
	
	public function __construct()
	{
		
	}
	
	public function setNewClientEvery($ms)
	{
		$this->_client_every = (int)$ms;
	}
	
	public function setNumClients($num)
	{
		$this->_num = (int)$num;
	}
	
	public function setCheckInterval($sec)
	{
		$this->_check = (int)$sec;
	}
	
	public function start()
	{
		$self = &$this;
		
		$disp = new ApeDispatcher();
		$disp->start(function($disp) use (&$self) {
			
			$pipe = 0;

			$disp->add_timer(50, function() use (&$disp, &$pipe) {
				
				$s = '|/☻\\';

				$pipe++;
				
				$left = $pipe%40;
				$left2 = $pipe%4;
				if ($left > 20) {
					$left2 = 3-$left2;
					$left = 40-$left;
				}
				
				$s = '['.str_pad($s[$left2], $left+1, ' ', STR_PAD_LEFT).str_repeat(' ', 20-$left).']';										
				echo "     " . $s . ' Total active : ' . $disp->active . "\r";
			}, 1);
			
			$disp->add_timer($self->_client_every, function() use (&$disp, &$self) {				
				$ape = new Ape($disp);	
				
				$ape->connect()->addEvent('LOGIN', function($obj, $data) use (&$disp, &$self) {
					$obj->cmd('CHECK');
					$disp->add_timer($self->_check*1000, function() use (&$obj) {
						$obj->cmd('CHECK');
					}, 1);
				});
				
				$ape->addEvent("FOO",  function($obj, $data) use (&$disp, &$self) {
					
					$obj->cmd('CHECK');

				});
				$ape->addEvent("BAR",  function($obj, $data) use (&$disp, &$self) {

					$obj->cmd('CHECK');

				});				
			}, 1, $self->_num);
			
			$disp->add_timer(1, function() use (&$disp) {
				$ape = new Ape($disp);
				
				$ape->cmd('push');
				
			}, 100);

	
		}, function($disp, $ticks) {
			pcntl_signal_dispatch();
		});				
	}
}

?>
