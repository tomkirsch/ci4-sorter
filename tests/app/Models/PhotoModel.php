<?php

namespace App\Models;

use CodeIgniter\Model;

class PhotoModel extends Model
{
	protected $table 			= 'photos';
	protected $primaryKey 		= 'photo_id';
	protected $createdField  = 'photo_created';
	protected $updatedField  = 'photo_modified';
}
