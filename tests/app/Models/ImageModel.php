<?php namespace App\Models;

use CodeIgniter\Model;

class ImageModel extends Model{	
	protected $table 			= 'images';
	protected $primaryKey 		= 'image_id';
	protected $createdField  = 'image_created';
    protected $updatedField  = 'image_modified';
}