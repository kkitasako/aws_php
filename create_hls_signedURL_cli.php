<?php
	define('__ROOT__', dirname(__FILE__));
	error_reporting(E_ALL);
	
	// Load AWS PHP SDK LIBs
	require '../vendor/autoload.php';
	use Aws\Common\Aws;
	use Aws\S3\S3Client;

	// Set Config Parameters to Array
	$init_array = parse_ini_file(__ROOT__."/conf/create_hls_signedURL.conf",true);

	// Create AWS SDK Instance
	$aws = Aws::factory('../vendor/aws/aws-sdk-php/src/Aws/Common/Resources/aws-config.php');

	// HTTP HEADER
	header("Content-Type:text/html; charset=utf-8");


	//---------------------------------------------------------
	// Parameter
	//---------------------------------------------------------
	$s3_bucket = $init_array['S3']['s3_bucket'];
	$s3_file_key = $init_array['S3']['s3_file_key'];
	//$cf_dir_path = "https://d1bwjl0ormyoox.cloudfront.net/hls/";
	$cf_dir_path = $init_array['CloudFront']['cf_dir_path'];

	//$cf_key_pair_id = "APKAIZ4RI4PUMO3SNKLQ";
	$cf_key_pair_id = $init_array['CloudFront']['cf_key_pair_id'];
	//$cf_private_key_file = "pk-APKAIZ4RI4PUMO3SNKLQ.pem";
	$cf_private_key_file = $init_array['CloudFront']['cf_private_key_file'];
	$cf_duration = $init_array['CloudFront']['cf_duration'];
	$cf_expires = time() + $cf_duration;

	//$manifest_file_name = "signed_playlist.m3u8"; 
	$manifest_file_name = $init_array['HLS']['manifest_file_name']; 
	//$manifest_url="http://www.aws-jp.info/aws/aws_php/".$manifest_file_name;
	$manifest_url = $init_array['HLS']['manifest_url']; 


	//---------------------------------------------------------
	// Main
	//---------------------------------------------------------
	// Read manifest file from S3
	$file_line = get_s3_file($aws,$s3_bucket,$s3_file_key);


	// Create CloudFront Singned URL Parameter
	// Policy
	$cf_policy = '{"Statement":[{"Resource":"' . $cf_dir_path . "*" . '","Condition":{"DateLessThan":{"AWS:EpochTime":'. $cf_expires . '}}}]}';
	$cf_query_url = get_custom_policy_url($cf_private_key_file, $cf_key_pair_id, $cf_policy);


	// Append CloudFront Signed URL parameter to TS URL
	$i = 0;
	foreach ($file_line as $line) {
		// Change only TS URL line	
		if (substr($line, 0, 1) <> '#' and $line <> "") {
			$manifest_array[$i] = $cf_dir_path.$line.$cf_query_url;
		}else{
			$manifest_array[$i] = $line;
		}
		$i++;
	}

	$manifest = join("\n", $manifest_array);

	// Output to manifest file
	$fh = fopen($manifest_file_name,"w");
	fwrite($fh, $manifest);
	fclose($fh);

	// Demonstration Manifest file TEXT
	$fh1 = fopen($manifest_file_name.".txt","w");
	fwrite($fh1, $manifest);
	fclose($fh1);

	//Return SignedURL Manifest file URL
	print $manifest_url;	

	//---------------------------------------------------------
	// Get Manifest file from S3
	//---------------------------------------------------------
	function get_s3_file($aws, $bucket, $key) {
	
		// Initialize S3 Client Object
		$client = $aws->get('S3');
	
	
		// Get Manifest File from S3
		$res = $client->getObject(array(
			'Bucket' => $bucket,
			'Key' => $key
		));
	
		// Sprit file body to Array
		$line_array = preg_split("/\R/", $res['Body']);
		
		return $line_array;
	
	}


	//---------------------------------------------------------
	// Create CloudFront Custom SignedURL Query Strings
	//---------------------------------------------------------
	function get_custom_policy_url($private_key_file, $key_pair_id, $policy) {
		// Policy Base64 Encode
		$encode_policy = url_base64_encode($policy);
		// Sign Policy with Private Key
		$signature = rsa_sha1_sign($policy, $private_key_file);
		$encode_signature = url_base64_encode($signature);

		// Create Query Strings
		$query_url = "?Signature=".$encode_signature."&Policy=".$encode_policy."&Key-Pair-Id=".$key_pair_id;
		return $query_url;


	}


	// RSA SHA1 Hahing
	function rsa_sha1_sign($policy, $private_key_filename) {
	        $signature = "";
	
	        // load the private key
	        $fp = fopen($private_key_filename, "r");
	        $priv_key = fread($fp, 8192);
	        fclose($fp);
	        $pkeyid = openssl_get_privatekey($priv_key);

	        // compute signature
	        openssl_sign($policy, $signature, $pkeyid);

	        // free the key from memory
	        openssl_free_key($pkeyid);

	        return $signature;
	}


	// Base64 Encoding
	function url_base64_encode($value) {
	        $encoded = base64_encode($value);
	        // replace unsafe characters +, = and / with
	        // the safe characters -, _ and ~
	        return str_replace(
                        array('+', '=', '/'),
                        array('-', '_', '~'),
                        $encoded);
	}

?>
