<?php
	
/**
 * Class to define an object to administrate php process, the  most important utility is kill and keep tracked
 * the php process to save memory and evade cpu issues
 * @author Diego Cardona <cardona.root@gmail.com>
 */
class PidsManager{
	
	private $pids = array();

/**
 * function set
 *
 * Setter that allow int var or int array, to add to the collection
 * 
 * @access public
 * @param pid, int var or int array that represents the process identifier
 */
	public function set( $pid ){

		if( is_array( $pid ) ){
			foreach ($pid as $_pid) {
				$this->add_pid( $_pid );	
			}
		}else{
			$this->add_pid( $pid );
		}
	}

/**
 * function remove
 *
 * Delete an element
 * 
 * @access public
 * @param pid, int var  that represents the process identifier to remove
 */
	public function remove( $pid ){

		try{
			$pointer = array_search( $pid, $this->pids );
			if( !$pointer ){
				return FALSE;
			}
			unset( $this->pids[$pointer] );
			return TRUE;
		}catch( Exception $e ){
			return FALSE;
		}
	}

/**
 * function get
 *
 * Getter that that returns the full collection
 * 
 * @access public
 */
	public function get(){

		return $this->pids();
	}

/**
 * function view
 *
 * Show the information of an especific process
 * 
 * @access public
 * @param pointer, the pid key in the collection
 */
	public function view( $pointer ){

		$pid = $this->pids[$pointer];
		return exec( "ps -p ". $pid );
	}

/**
 * function kill
 *
 * Pids killer, kill a single process
 * 
 * @access public
 * @param pointer, the pid key in the collection
 */
	public function kill( $pointer ){

		if( isset( $this->pids[ $pointer ] ) ){
			return $this->kill_pid( $this->pids[ $pointer ] );
		}else{
			return FALSE;
		}
	}

/**
 * function kill_all
 *
 * Pids killer, kill all process saved in the collection
 * 
 * @access public
 */
	public function kill_all(){

		foreach ($this->pids as $key => $value) {
			$this->kill( $key );
		}
		return TRUE;
	}

/**
 * function kill_pid
 *
 * Pids killer, kill a single process, based in their pid
 * 
 * @access private
 * @param pid, the process id
 */
	private function kill_pid( $pid ){
		
		if( !is_null( $pid ) && $pid != '' && !is_nan( $pid ) ){
			try{
				system( "kill -9 ". $pid );
				$this->remove( $pid );
			}catch( Exception $e ){
				return FALSE;
			}
			return TRUE;
		}
		else{
			return FALSE;
		}
	}

/**
 * function kill_pid
 *
 * add a single pid to the collection
 * 
 * @access private
 * @param pid, the process id
 */
	private function add_pid( $pid ){

		if( !is_nan( $pid ) ){
			array_push( $this->pids, $pid);
			$pointer = array_search( $pid, $this->pids );
			$info = $this->view( $pointer );
			if( empty( $info ) || is_null( $info ) ){
				unset( $this->pids[$pid] );
			}
		}
	}

}

?>