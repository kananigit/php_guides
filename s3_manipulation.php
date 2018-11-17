<?php

//use composer to include S3 php sdk
// You must setup your bucket to have the proper permissions. To learn how to do this
	// The PHP SDK:
	// https://github.com/aws/aws-sdk-php
	// https://packagist.org/packages/aws/aws-sdk-php 
	//
	// Run:$ composer require aws/aws-sdk-php
  	require plugin_dir_path( dirname( __FILE__, 1 ) ).'/vendor/autoload.php';
  
 	use Aws\S3\S3Client;
	use Aws\S3\Exception\S3Exception;
	// AWS Info
	$BUCKET_NAME = '';
	$IAM_KEY = '';
	$IAM_SECRET = '';
	// Connect to AWS
	try {
		// You may need to change the region. It will say in the URL when the bucket is open
		// and on creation.
		$s3 = S3Client::factory(
			array(
				'credentials' => array(
					'key' => $IAM_KEY,
					'secret' => $IAM_SECRET
				),
				'version' => 'latest',
				'region'  => 'us-east-2'
			)
		);
	} catch (Exception $e) {
		// We use a die, so if this fails. It stops here. Typically this is a REST call so this would
		// return a json object.
		die("Error: " . $e->getMessage());
	}
  
  
  	// global $results;
 //    $results = $s3->getPaginator('ListObjects', [
 //        'Bucket' => $BUCKET_NAME,
 //        'Prefix' => "clients/kpa/"
 //    ]);
 
 
 
 	$objects = $s3->getIterator('ListObjects', [

		'Bucket' => $BUCKET_NAME
		
	]);

	$path_array=array();
	$size_array=array();
	$link_array=array();


	foreach ($objects as $object) {
		if (!isset($objectarray)) { $objectarray = array(); }
		//var_dump($object);
		$name = $object['Key'];
		$size = $object['Size'];


		if ($object['Size'] != '0') {
			//get the actual file names
			$base = basename($object['Key']);
			//var_dump($base);
			$cmd = $s3->getCommand('GetObject', [
			    'Bucket' => "$BUCKET_NAME",
			    'Key' => "$name",
				'ResponseContentType'           => 'application/octet-stream',
				'ResponseContentDisposition'    => 'attachment; filename="'.$base.'"',
			]);

			$request = $s3->createPresignedRequest($cmd, '+60 minutes');

			$link = (string) $request->getUri();
			$path = 'Home/'.$name;
		
			$path_array[] = $path;
			$size_array[] = $size;
			$link_array[] = $link;
			//var_dump($path_array);
		}
	
	}	

function &placeInArray(array &$dest, array $path_array, $size, $pathorig,$link) {
    // If we're at the leaf of the tree, just push it to the array and return
	//echo $pathorig;
	//echo $size."<br>";

	global $folders_added;
	$folders_added = array();
    if (count($path_array) === 1) {
        if ($path_array[0] !== '') {
		  $file_array = array();
		  $file_array['name'] = $path_array[0];
		 $file_array['size'] = $size;
		  $file_array['type'] = 'file';
		 $file_array['path'] = $pathorig;
		$file_array['link'] = $link;
            array_push($dest['items'], $file_array);
        }
        return $dest;
    }

    // If not (if multiple elements exist in path_array) then shift off the next path-part...
    // (this removes $path_array's first element off, too)
    $path = array_shift($path_array);

    if ($path) {

		$newpath_temp = explode($path,$pathorig);
		$newpath = $newpath_temp[0].$path.'/';
        // ...make a new sub-array for it...


        //if (!isset($dest['items'][$path])) {
		if(!in_array($newpath,$folders_added,true)) {
            $dest['items'][] = array(

			'name' => $path,
			'type' => 'folder',
			'path' => $newpath,
			'items' => array()	

		  );
		$folders_added[] = $newpath;
		//print_r($folders_added);
        } 
		$count = count($dest['items']);
		$count--;
		//echo $count.'<br>';	
		//print_r($dest['items'][$path]);

        // ...and continue the process on the sub-array
        return placeInArray($dest['items'][$count], $path_array, $size, $pathorig,$link);
    }

    // This is just here to blow past multiple slashes (an empty path-part), like
    // /path///to///thing
    return placeInArray($dest, $path_array, $size, $pathorig,$link);
}


	$output = array();
	$folders_added = array();
	$i=0;
	foreach ($path_array as $path) {
		$size = $size_array[$i];
		$link = $link_array[$i];
	    placeInArray($output, explode('/', $path), $size, $path, $link);
	    //var_dump(explode('/', $path));
	    //var_dump($link);
		$i++;
	}


	//var_dump($output);
	$json_final = json_encode($output['items'][0]);

	var_dump($output);
  
  
  /*	
	// Adding content to S3
	try {
		// Uploaded:
		$file = $_FILES["fileToUpload"]['tmp_name'];
		$s3->putObject(
			array(
				'Bucket'=>$BUCKET_NAME,
				'Key' =>  $keyName,
				'SourceFile' => $file,
				'StorageClass' => 'REDUCED_REDUNDANCY'
			)
		);
	} catch (S3Exception $e) {
		die('Error:' . $e->getMessage());
	} catch (Exception $e) {
		die('Error:' . $e->getMessage());
	}
	echo 'Done';
	// Now that you have it working, I recommend adding some checks on the files.

	


	try{

	    //
	    $result = $s3->getObject(array(
	      'Bucket' => $BUCKET_NAME,
	      'Key'    => $keyPath
	    ));
	    // Display it in the browser
	    header("Content-Type: {$result['ContentType']}");
	    header('Content-Disposition: filename="' . basename($keyPath) . '"');
	    echo $result['Body'];


	}catch(Exception $e){
		die("Error: " . $e->getMessage());
	}

*/	
 
 /*-----Additional functions from a different project that was attempting to make  an s3 browser     -----*/
// 	function getenv_default($name, $default = null) {
// 	  $value = getenv($name);
// 	  return ($value === false) ? $default : $value;
// 	}

// 	$c = array();

// 	$c['bucket-name'] = $BUCKET_NAME;
// 	// Base path to directory the browser is running in. Leave blank if running out of a subdomain (like on Heroku)
// 	$c['base-path'] = $_SERVER['REQUEST_URI'];
// 	// Name of theme to use for display. Themes are found in the themes/ directory.
// 	$c['theme'] = getenv_default('THEME', 'plain');
// 	// Text to use as page header
// 	$c['page-header'] = getenv_default('PAGE_HEADER', 'My Amazon S3 files');

// 	// File size in bytes over which to serve files as torrents
// 	$c['torrent-threshold'] = getenv_default('TORRENT_THRESHOLD', null);


	

// 	// Get current directory from URL
// 	$dir = str_replace($c['base-path'], '', $_SERVER['REQUEST_URI']);
// 	//$dir='clients';
// 	$dir = urldecode($dir);



	

//   /**
//    * Returns list of file objects in the given path
//    *
//    * @param string $path  Directory path
//    * @return array        Directory contents
//    */
//   function getFiles($path = '/') {
//     $tree = getTree();
//     //echo treeOut($tree);
//     //var_dump($tree);
//     //var_dump($tree['clients']);
//     if ($tree === null) {
//       return null;
//     }

//     $path = trim($path, '/');

//     if ($path) {
//       $parts = explode('/', $path);
//           //var_dump($parts);
//       // walk to correct point in tree
//       foreach ($parts as $part) {
//       	//echo $part;
//         if (!isset($tree[$part])) {

//           return array();
//         }
//         $tree = $tree[$part]['files'];
//       }
//     }
    
//     //var_dump($tree);

//     //uasort($tree, array($this, 'sorting'));
//     uasort($tree, 'customSort');
//     return $tree;
//   }
//   /**
//    * Build a tree representing the directory structure of the bucket's
//    * contents.
//    *
//    * @return array
//    */
//  function getTree() {
//  	global $results;

//  	global $objects;

//     $tree = array();

//     $contents = $objects;
//     if ($contents === null) {
//       return null;
//     }
    
//     foreach ($contents as $key => $data) {

//       $isFolder = false;
//       // S3Hub and S3Fox append this suffix to folders
//       if (substr($data['Key'], -9) == '_$folder$') {
//         $key = substr($data['Key'], 0, -9);
//         $isFolder = true;
//       }
//       // Assume any key ending with / is a folder
//       else if (substr($data['Key'], -1) == '/') {
//         $key = substr($data['Key'], 0, -1);
//         $isFolder = true;
//       }
      
//       //$parts = explode('/', $key);
//       $parts = explode('/', $key); //$data['Key']
//       // add to tree
//       $cur = &$tree;
//       $numParts = count($parts);
//       for ($i = 0; $i < $numParts; $i++) {
//         $part = $parts[$i];
//         // file
//         if (!$isFolder && $i == $numParts-1 && !isset($cur[$part])) {
//           $cur[$part] = $data;
//           $cur[$part]['hsize'] = formatSize($data['Size']);
//           $cur[$part]['path'] = $cur[$part]['Key']; //name
//           $cur[$part]['name'] = $part;
//         }
//         // directory
//         else {
//           if (!isset($cur[$part])) {
//             $path = implode('/', array_slice($parts, 0, $i+1));
//             $cur[$part] = array(
//               'path' => $path,
//               'name' => $part,
//               'files' => array());
//           }
//           $cur = &$cur[$part]['files'];
//         }        
//       }
//     }

//     //var_dump($tree);
    
//     return $tree;
//   }

//   /**
//    * Takes a size in bytes and converts it to a more human-readable format
//    *
//    * @param string $bytes   Size in bytes
//    * @return string
//    */
//  function formatSize($bytes) {
//     $size = (int)$bytes;
//     $units = array("B", "K", "M", "G", "T", "P");
//     $unit = 0;
//     while ($size >= 1024) {
//       $unit++;
//       $size = $size/1024;
//     }
//     return number_format($size, ($unit ? 2 : 0)).''.$units[$unit];
//   }


// /**
//    * Returns directory data for all levels of the given path to be used when
//    * displaying a breadcrumb.
//    *
//    * @param string $path
//    * @return array
//    */
//  function getBreadcrumb($path = '/') {
//     if ($path == '/')
//       return array('/' => '');
    
//     $path = trim($path, '/'); // so we don't get nulls when exploding
//     $parts = explode('/', $path);
//     $crumbs = array('/' => '');
//     for ($i = 0; $i < count($parts); $i++) {
//       $crumbs[$parts[$i]] = implode('/', array_slice($parts, 0, $i+1)).'/';
//     }
    
//     return $crumbs;
//   }
//   /**
//    * Returns parent directory 
//    *
//    * @param string $path
//    * @return array
//    */
//  function getParent($path = '/') {
//     $crumbs = getBreadcrumb($path);
    
//     $current = array_pop($crumbs);
//     $parent = array_pop($crumbs);
//     return $parent;
//   }

//   // Sort with dirs first, then alphabetical ascending
//  function customSort($a, $b) {
//     $a_is_dir =isset($a['files']);
//     $b_is_dir = isset($b['files']);
//     // dir > file
//     if ($a_is_dir && !$b_is_dir) {
//       return -1;
//     } else if (!$a_is_dir && $b_is_dir) {
//       return 1;
//     }
//     return strcasecmp($a['name'], $b['name']);
//   }




// function treeOut($tree){
// 	$markup ='';
// 	foreach($tree as $branch => $twig){
// 		$markup .= '<li>'.((is_array($twig)) ? $branch . treeOut($twig): $twig).'</li>';
// 	}
// 	return '<ul>'. $markup .'</ul>';
// }

// */
 
