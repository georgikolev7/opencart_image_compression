<?php

require_once('config.php');

$dir = DIR_IMAGE . 'catalog/';

	/*  Изпращане на POST заявка към
	 *  сървъра за компресия на NitroSmush
	*/

	function _file_api($args) {
		
		$source = $args['source'];
        $quality = $args['quality'];

        $data = ""; 

        $boundary = "---------------------" . substr(md5(mt_rand(0, 32000)), 0, 10);

        $data .= "--" . $boundary . "\n";

        $data .= "Content-Disposition: form-data; name=\"quality\"\n\n"; 
        $data .= $quality."\n";
        $data .= "--" . $boundary . "\n";

        $fileContents = file_get_contents($source); 

        $data .= "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($source) . "\"\n"; 
        $data .= "Content-Type: " . _get_mime($source) . "\n"; 
        $data .= "Content-Transfer-Encoding: binary\n\n"; 
        $data .= $fileContents."\n";

        $data .= "--" . $boundary . "\n";

        $params = array('http' => array( 
            'method' => 'POST', 
            'header' => 'Content-Type: multipart/form-data; boundary='.$boundary, 
            'content' => $data 
        ));

        $url = 'http://nitrosmush.com/api.php';

        $ctx = stream_context_create($params); 
        $fp = fopen($url, 'rb', false, $ctx); 

        if (!$fp) { 
            throw new Exception("There was a problem with $url");
        } 

        $json = @stream_get_contents($fp);

        fclose($fp);

        if ($json === false) { 
            throw new Exception("Problem reading data from $url"); 
        }

        $response = json_decode($json, true);

        if (!empty($response['error'])) {
            throw new Exception($response['error']);
        }

        return $response['result_file'];
		
	}
	
	/* Извличане на MIME тип на текущото изображение */
	
	function _get_mime($file) {
        if (preg_match('~\.gif$~i', $file)) {
            return 'image/gif';
        } else if (preg_match('~\.jpe?g$~i', $file)) {
            return 'image/jpeg';
        } else if (preg_match('~\.png$~i', $file)) {
            return 'image/png';
        } else {
            throw new Exception("Invalid extension of " . $file);
        }
    }
	
	
	/* Начало на сканирането на директорията и обработване на изображенията */
		
	
	$files = glob($dir . '*.{jpeg,gif,png}', GLOB_BRACE);
	
	foreach ($files as $file)
	{
		$array = array(
			'source' => $file,
			'quality' => 100
		);
		
		$result = _file_api($array);
		
		file_put_contents($file, file_get_contents($result));
		
	}
	
