<?php
/**
 * Event trait
 * 
 * Event trait is used to listen to any registered events from any classes.
 */
trait Event{
	protected $listeners = [];

	/**
	 * Add callback and listens to the event. 
	 *
	 * @param string $event
	 * @param callable $fn
	 * @return $this
	 */
	public function on(string $event, callable $fn){
		$this->addListener($event, $fn);
		return $this;
	}

	/**
	 * Add callback and listens to the event once.
	 *
	 * @param string $event
	 * @param callable $fn
	 * @return $this
	 */
	public function once(string $event, callable $fn){
		/** Create function that will execute and discard itself */
		$wrapper = function() use($event, $fn, &$wrapper){
			$this->removeListener($event, $wrapper);
			call_user_func_array($fn, func_get_args());
		};

		$this->on($event, $wrapper);
		return $this;
	}

	/**
	 * Emit event from listeners
	 *
	 * @param string $event
	 * @param mixed ...$params
	 * @return $this
	 */
	protected function emit(string $event, mixed ...$params){
		if(!isset($this->listeners[$event])) return;
		foreach($this->listeners[$event] as $listener){
			call_user_func_array($listener, $params);
		}

		return $this;
	}

	/**
	 * Remove event to the listeners. Anonymous function will not be remove.
	 *
	 * @param string $event
	 * @param callable $fn
	 * @return void
	 */
	public function removeListener(string $event, callable $fn){
		$index = array_search($fn,$this->listeners[$event], true);

		if(isset($index)){
			unset($this->listeners[$event][$index]);
		}

		return $this;
	}

	/**
	 * Add callback and listens to the event.
	 *
	 * @param string $event
	 * @param callable $fn
	 * @return void
	 */
	public function addListener(string $event, callable $fn){
		if(!isset($this->listeners[$event])){
			$this->listeners[$event] = [];
		}
		$this->listeners[$event][] = $fn;

		return $this;
	}
}