<?php
class Ape
{
	private $_fd;
	private $_opt;
	private $_chl;
	public $sessid;
	public $dispatch;
	public $sub = 0;
	
	public function __construct($dispatch, $opt = array())
	{

		$this->_chl = 0;
		$this->sessid = false;
		$this->dispatch = $dispatch;
		
		$this->sub = rand(0, 50);
		
		if (count($opt)) {
			foreach($opt AS $name => $val) {
				$this->setopt($name, $val);
			}
		}

		$this->addEvent('LOGIN', function($obj, $data) {
			$obj->sessid = $data->sessid;
		});
		
		$this->addEvent('ERR', function($obj, $data) {
			echo 'ERREUR' . "\n";
			print_r($data);
		});
		
	}
	
	public function connect($opt = array())
	{
		$this->cmd('CONNECT', $opt);
		
		return $this;
	}
	
	public function setopt($name, $val)
	{
		$this->_opt[$name] = $val;
	}
	
	public function cmd($name, $params = array())
	{
		$object = array(
			'cmd' => $name,
			'chl' => ++$this->_chl
		);
		
		if ($this->sessid)
			$object['sessid'] = $this->sessid;
		
		if (count($params))
			$object['params'] = $params;
		
		return $this->_request(array($object));
	}
	
	private function _request($obj)
	{
		$cmd = json_encode($obj);
		$data = $read = '';
		$self = &$this;
		
		$this->_fd = $this->dispatch->connect(function(){}, function($data) use (&$self) {
		
			
		}, function($read) use (&$self) {

			$content = explode("\r\n\r\n", $read, 2);
			$content = $content[1];
			
			$obj = json_decode($content);
			
			if (!is_array($obj)) {
				echo $content . "\n";
			}
			
			foreach($obj AS $val) {
			
				foreach($self->_events AS $v) {
					if ($v[0] == $val->raw) 
						$v[1]($self, $val->data);
				}
			}

		});
		
		switch($this->_opt['method']) {
			case 'GET':
				$data .= 'GET /0/?'.$cmd.' HTTP/1.1' . "\r\n";
				$data .= 'Host: '.$this->sub.'.ape.com' . "\r\n\r\n";
				break;
			case 'POST':
			default:
				$data .= 'POST /0/ HTTP/1.1' . "\r\n";
				$data .= 'Host: '.$this->sub.'.ape.com' . "\r\n";
				$data .= 'content-length: ' . strlen($cmd) . "\n\n";
				$data .= $cmd;
				break;
		}
		if (!$this->_fd) return;
		$this->dispatch->write($this->_fd, $data);

	}
	
	public function addEvent($name, $func)
	{
		$this->_events[] = array($name, $func);
	}
}
?>
