<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
		$sorter = service('Sorter');
		$sorter->addTable('images', 'image_id', 'asc');
		$im = model('ImageModel');
		$images = $im
			->select('images.*')
			->select('(RAND() * 10000) - 5000 AS numbertest', FALSE)
			->orderBy($sorter->getSort())->paginate(25)
		;
        return view('welcome_message', [
			'images'=>$images,
			'sorter'=>$sorter,
			'pager'=>$im->pager,
		]);
    }
}
