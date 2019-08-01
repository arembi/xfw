<?php

namespace Arembi\Xfw\Core;
use Arembi\Xfw\Core\Models\Session;

class SessionModel {

  public function readData($id)
	{
    $session = Session::find($id);

    return $session->data ?? '';
	}



  public function writeData($id, $data)
	{
    $session = Session::find($id) ?? new Session;

		$session->id = $id;
		$session->access = time();
		$session->data = $data;

		return $session->save();

	}



  public function destroy($id)
	{
    return Session::destroy($id);
	}



	/*
	 * Garbage collector
	 * */
	public function gc($max)
	{
		// Calculate what is to be deemed old
		$old = time() - $max;

    return Session::where('access','<',$old)->delete();
	}
}
