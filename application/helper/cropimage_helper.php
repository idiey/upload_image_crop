<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

function resizeImg($arr){
	
	// name of the file here
	$date = md5(time());
	
	// upload image and resize
	$uploaddir 	= $arr['uploaddir'];
	$tempdir	= $arr['tempdir'];
	
	$temp_name 	= $_FILES['photo']['tmp_name'];
	
	$img_parts 	= pathinfo($_FILES['photo']['name']);
	$new_name 	= strtolower($date.'.'.$img_parts['extension']);
	
	$ext = strtolower($img_parts['extension']);
	
	$allowed_ext = array('gif','jpg','jpeg','png');
	if(!in_array($ext,$allowed_ext)){
		echo '<p class="uperror">'.$arr['fileError'].'</p>';
		exit;
	}
	
	$temp_uploadfile = $tempdir . $new_name;
	$new_uploadfile = $uploaddir . $new_name;
	
	// less than 3MB default
	if($_FILES['photo']['size'] < $arr['maxfilesize']){
		if (move_uploaded_file($temp_name, $temp_uploadfile)) {

		/****** Check EXIF ******/
		// reading the image orientation
		$arr['orientation']=checkExifOrientation($temp_uploadfile);
		/****** Check EXIF END ******/

		// add key value to arr
		$arr['temp_uploadfile'] = $temp_uploadfile;
		$arr['new_uploadfile'] = $new_uploadfile;

		asidoImg($arr);

		unlink($temp_uploadfile);
		exit;
		}
	}
	else
	{
		echo '<p class="uperror">'.$arr['sizeError'].'</p>';
		exit;
	}

}


function resizeThumb($arr){
	$date = md5(time());	
	$arr['temp_uploadfile'] = $arr['img_src'];
	$arr['new_uploadfile'] = $arr['uploaddir'].strtolower($date).'.jpg';
    
    // ADD BY HAEZAL ON 16/10/2013 11:35AM
    // INSERT RECORD INTO DATABASE
	asidoImg($arr);
    $CI =& get_instance(); // get the CodeIgniter object
    $CI->load->model('Pemohon_model');
    $CI->load->library('authentication');
    $id = $CI->authentication->getId(); // get user id
    $value['AMP_GAMBAR_DIR'] = $arr['new_uploadfile'];
    $value['AMP_GAMBAR_NAMA'] = strtolower($date).'.jpg';
    $CI->Pemohon_model->update_image($id, $value);
	exit;
}

/****** Check the EXIF orientation tag ******/
function checkExifOrientation($target){
	$exif = exif_read_data($target);
	if($exif['Orientation']!=''){return $exif['Orientation'];}
	else {return 1;}
}

function asidoImg($arr){
		
//	include('asido/class.asido.php');
    require_once APPPATH.'third_party/asido/class.asido.php';
	asido::driver('gd');
	
	$height		= $arr['height'];
	$width		= $arr['width'];
	$x			= $arr['x'];
	$y			= $arr['y'];				
		
	// process
	$i1 = asido::image($arr['temp_uploadfile'], $arr['new_uploadfile']);	
	// fit and add white frame										
	if(isset($arr['thumb']) === true){
		Asido::Crop($i1, $x, $y, $width, $height);
	}
	else{
		// rotate the image if it is portrait
		switch($arr['orientation'])
		{
			case 1: // nothing
			break;
			case 2: // horizontal flip
			break;
			case 3: // 180 rotate left
				Asido::Rotate($i1,180);
			break;
			case 4: // vertical flip
			break;
			case 5: // vertical flip + 90 rotate right
			break;
			case 6: // 90 rotate right
				Asido::Rotate($i1,90);
			break;
			case 7: // horizontal flip + 90 rotate right
			break;
			case 8:    // 90 rotate left
				Asido::Rotate($i1,-90);
			break;
		}
		Asido::Frame($i1, $width, $height, Asido::Color($arr['canvasbg']['r'],$arr['canvasbg']['b'],$arr['canvasbg']['g']));		
	}

	// always convert to jpg	
	Asido::convert($i1,'image/jpg');

	$i1->Save(ASIDO_OVERWRITE_ENABLED);
		$data = array(
		'photo'=> $arr['new_uploadfile']
	);
		// echo $user_id;
	// delete old file
	echo $data['photo'];

}
?>
