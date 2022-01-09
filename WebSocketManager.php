<?php
/**
 * WebSocket Manager
 * 
 * Maintains and controls all incoming Client WebSockets.
 */

class WebSocketManager{
	/**
	 * Address name
	 * @var string 
	 */
	public string $address;

	/**
	 * Address port to listen to
	 * @var int
	 */
	public int $port;

	/**
	 * Master server socket
	 *
	 * @var WebSocket
	 */
	public WebSocket $master;

	/**
	 * All WebSockets from clients and master
	 *
	 * @var SplObjectStorage
	 */
	public SplObjectStorage $sockets;

	public function __construct(string $address = 'localhost', int $port = 4000){
		$this->address = $address;
		$this->port = $port;
		$this->sockets = new SplObjectStorage;
		$this->master = new WebSocket($address, $port);
		echo "Socket created at $address:$port\n";
		$this->master->start();
		$this->sockets[$this->master->socket] = $this->master;
	}

	public function run(){
		$null = null;

		while(1){
			$all_sockets = iterator_to_array($this->sockets);
			socket_select($all_sockets, $null, $null, 0, 10);

			foreach($all_sockets as $socket){
				/**@var WebSocket */
				$websocket = $this->sockets[$socket];

				if($websocket === $this->master){
					$accepted_socket = $websocket->accept();
					$accepted_socket->handshake();
					echo "Socket accepted\n";
					$this->sockets[$accepted_socket->socket] = $accepted_socket;
				}
				else{
					$data = $websocket->read();

					if($data['op_code'] === 8){
						echo "Socket closed. Reason: ".$data['reason']." [".$data['code']."]\n";
						$websocket->close($data['code'], $data['reason']);
						$this->sockets->detach($websocket->socket);
					}
					else{
						echo 'Message From Client: '.$data['data']."\n";
					}
				}
			}
		}
	}
}