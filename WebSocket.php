<?php
/**
 * WebSocket class
 * 
 * Websocket performs all WebSocket operation and decode/encode data from client to server
 * and vice-versa. 
 */
class WebSocket{
	use Event;
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
	 * The header of the request
	 * @var array $headers;
	 */
	public array $headers = [];

	/**
	 * Socket created by socket_accept or socket_create
	 */
	public \Socket $socket;

	/**
	 * Has Handshake been initiated 
	 */
	public bool $handshaked = false;

	/**
	 * The max length of read 
	 */
	public const MAX_READ_LENGTH = 1024;

	public function __construct(string $address, int $port, \Socket|null $socket = null){
		$this->address = $address;
		$this->port = $port;
		$this->socket = isset($socket) ? 
			$socket : 
			socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	}

	/**
	 * Connect to WebSocket server
	 *
	 * @return void
	 */
	public function connect(){
		$this->emit('connect');
		socket_connect($this->socket, $this->address, $this->port);
		return $this;
	}

	/**
	 * Send message to client side
	 *
	 * @param string $text
	 * @param array $config
	 * @param boolean $ignore_encode
	 * @return $this
	 */
	public function write(string $text, array $config = [], bool $ignore_encode = false){
		if($ignore_encode)
			socket_write($this->socket, $text, strlen($text));
		else{
			socket_write($this->socket, $text = Frame::encode($text, $config), strlen($text));
		}
		return $this;
	}

	/**
	 * Read message from client
	 *
	 * @return array|string
	 */
	public function read(){
		if(!$this->handshaked)
			return socket_read($this->socket, self::MAX_READ_LENGTH);
		$text = Frame::decode(socket_read($this->socket, self::MAX_READ_LENGTH));
		$this->emit('message', $text);
		return $text;
	}

	/**
	 * Accept socket from the client
	 *
	 * @return WebSocket|null
	 */
	public function accept(){
		if($socket = socket_accept($this->socket)){
			$socket = new self($this->address, $this->port, $socket);
			$this->emit('accept', $socket);
			return $socket;
		}
		return false;
	}

	/**
	 * Start WebSocket as a server
	 *
	 * @return $this
	 */
	public function start(){
		socket_bind($this->socket, $this->address, $this->port);
		socket_listen($this->socket);
		return $this;
	}

	/**
	 * Close socket connection
	 *
	 * @param integer $code
	 * @param string $msg
	 * @return void
	 */
	public function close(int $code, string $msg = ''){
		$this->write(Frame::close($code, $msg), [], true);
		$this->emit('close', $msg, $code);
		socket_close($this->socket);
	}

	/**
	 * Perform handshake with the client 
	 *
	 * @return $this
	 */
	public function handshake(){
		$data = $this->read();
		$magic_key = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		preg_match_all('/(?<key>[^:\n]+): (?<value>[^\n]+)\r\n?/', $data, $matches);
		$this->headers = array_combine($matches['key'], $matches['value']);
		$token = base64_encode(pack('H*', sha1($this->headers['Sec-WebSocket-Key'].$magic_key)));
		$header = implode("\r\n", [
			'HTTP/1.1 101 Web Socket Protocol Handshake',
			'Upgrade: websocket',
			'Connection: Upgrade',
			'Sec-WebSocket-Accept: '.$token,
			'',
			''
		]);

		socket_write($this->socket, $header, strlen($header));
		$this->handshaked = true;
		return $this;
	}
}
