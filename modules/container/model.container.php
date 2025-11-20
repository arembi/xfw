<?php

namespace Arembi\Xfw\Module;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Html_Content;

class ContainerBaseModel {

	public function getContents()
	{
		return Html_Content::all();
	}


	public function getContentById(int $id)
	{
		return Html_Content::find($id);
	}


	public function addContent(array $hcData)
	{
		$hc = new Html_Content();

		$hc->title = $hcData['title'];
		$hc->content = $hcData['content'];

		return $hc->save();
	}


	public function updateContent(array $hcData)
	{
		$hc = Html_Content::find($hcData['id']);
		if (!$hc) {
			return false;
		}
		$hc->title = $hcData['title'];
		$hc->content = $hcData['content'];

		return $hc->save();
	}


	public function deleteContent($id)
	{
		$hc = Html_Content::find($id);

		return $hc ? $hc->delete() : false;
	}

}