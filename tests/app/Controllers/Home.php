<?php

namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		$sorter = service('Sorter');
		$sorter->addTable('photos', 'photo_id', 'asc');
		$model = model('PhotoModel');
		$photos = $model
			->select('photos.*')
			->select('(RAND() * 10000) - 5000 AS numbertest', FALSE)
			->orderBy($sorter->getSort())->paginate(25);
		return view('welcome_message', [
			'photos' => $photos,
			'sorter' => $sorter,
			'pager' => $model->pager,
		]);
	}
}
