<?php

class ApeDispatcher
{
	private $_socks = array();
	private $_socks_write = array();
	private $_timers = array();
	
	public $ticks = 0;
	
	public $active = 0;
	
	public function __construct()
	{
		
	}
	
	private function _process_tick()
	{
		foreach($this->_timers AS &$timer) {
			if (count($timer) && $timer[1]-- == 0) {
				$timer[2]();
				if ($timer[3] && --$timer[4] != 0) {
					$timer[1] = $timer[0];
				} else {
					$timer = array();
				}
			}
		}
	}
	
	public function add_timer($ms, $func, $perio = 0, $num = 0)
	{
		$this->_timers[] = array($ms, $ms, $func, $perio, $num);
	}
	
	public function connect($onconnect, $onread, $ondisco)
	{
		$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$sock) return;
		socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($sock);
		
		@socket_connect($sock, '127.0.0.1', 6969);

		$this->_socks[(int)$sock] = array(1, $sock, $onconnect, $onread, '', $ondisco, '');
		$this->_socks_write[(int)$sock] = $sock;
		return $sock;			
	}
	
	public function write($socket, $data)
	{
		if ($this->_socks[(int)$socket][0] == 1) {
			$this->_socks[(int)$socket][4] .= $data;
			return;
		}
		
		$data = $this->_socks[(int)$socket][4] . $data;
		$len = strlen($data);
		
		$wlen = socket_write($socket, $data, $len);
		
		if ($wlen != $len) {
			$this->_socks[(int)$socket][4] = substr($data, $wlen);
			if (!$this->_socks_write[(int)$socket]) {
				$this->_socks_write[(int)$socket] = $socket;
			}
		} else {
			$this->_socks[(int)$socket][4] = '';
			unset($this->_socks_write[(int)$socket]);
		}
	}
	
	public function start($func_init, $func_perio)
	{
		$func_init($this);
		$mc = microtime(false);
		list($usec_start, $sec_start) = explode(' ', $mc);
		$usec_start = substr($usec_start, 2, -2);
		$lticks = 0;
		
		while (1) {
			
			$read = array();
			$write = array();

			foreach($this->_socks AS $s) {
				$read[] = $s[1];
			}
			foreach($this->_socks_write AS $s) {
				$write[] = $s;
			}
			if (!count($read) && !count($write)) {
				usleep(1000);
			} else {					
				if (($x = socket_select($read, $write, $except = NULL, 0, 1000) > 0)) {
					foreach($write AS $w) {							
						if ($this->_socks[(int)$w][0] == 1 && !socket_get_option($w, SOL_SOCKET, SO_ERROR)) { /* on_connect */
							$this->_socks[(int)$w][0] = 0;
							$this->_socks[(int)$w][2]();
							$this->active++;
						}
						if ($this->_socks[(int)$w][4] != '') {
							$this->write($w, '');
						}
					}
				
					foreach($read AS $r) {
						$data = socket_read($r, 8092, PHP_BINARY_READ);
						if (strlen($data) > 0) {
							$this->_socks[(int)$r][6] .= $data;
							$this->_socks[(int)$r][3]($data);
						} else {
							$this->_socks[(int)$r][5]($this->_socks[(int)$r][6]);
							$this->active--;
							unset($this->_socks[(int)$r][6]);
							unset($this->_socks[(int)$r]);
						}
					}
				}
			}
			$mc = microtime(false);
			list($usec, $sec) = explode(' ', $mc);
			$usec = substr($usec, 2, -2);
			
			$uticks = 1000000 * ($sec - $sec_start);

			
			$uticks += ($usec - $usec_start);
			
			$sec_start = $sec;
			$usec_start = $usec;
			
			$lticks += $uticks;
			
			while ($lticks >= 1000) {
				$lticks -= 1000;
				
				$this->_process_tick();
			}
			
			
			$func_perio($this, ++$this->ticks);
		}
	}
	
}
?>
