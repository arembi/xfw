<?php

namespace Arembi\Xfw\Core;
use Arembi\Xfw\Core\Models\Session;

class SessionModel extends Model {

	public function readData(int|string $id)
	{
		$session = Session::find($id);

		return $session->data ?? '';
	}


	public function writeData(int|string $id, string $data)
	{
    	$session = Session::find($id) ?? new Session;

		$session->id = $id;
		$session->access = time();
		$session->data = $data;

		return $session->save();

	}


	public function destroy(int|string $id)
	{
		$session = Session::find($id);
		
		return $session->delete();
	}


	/*
	 * Garbage collector
	 * */
	public function gc(int $max)
	{
		// Calculate what is to be deemed old
		$old = time() - $max;

	    return Session::where('access','<',$old)->delete();
	}
}
