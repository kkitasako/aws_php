<?php
	define('__ROOT__', dirname(__FILE__));
	error_reporting(E_ALL);
	
	// Load AWS PHP SDK LIBs
	require '../vendor/autoload.php';
	use Aws\Common\Aws;

	// Set Config Parameters to Array
	$init_array = parse_ini_file(__ROOT__."/conf/create_signedCookie.conf",true);

	// Create AWS SDK Instance
	$aws = Aws::factory('../vendor/aws/aws-sdk-php/src/Aws/Common/Resources/aws-config.php');

	// HTTP HEADER
	header("Content-Type:text/html; charset=utf-8");


	//---------------------------------------------------------
	// Parameter
	//---------------------------------------------------------
	$cf_domain = $_POST[cf_domain];
	$cf_cname = $_POST[cf_cname];
	$cf_path = $_POST[cf_path];
	$cf_key_pair_id = $init_array['CloudFront']['cf_key_pair_id'];
	$cf_private_key_file = $init_array['CloudFront']['cf_private_key_file'];
	$cf_duration = $_POST[cf_duration];
	$cf_expires = time() + $cf_duration;



	//---------------------------------------------------------
	// Main
	//---------------------------------------------------------


	// Create CloudFront Singned URL Parameter
	// Policy
	$cf_policy = '{"Statement":[{"Resource":"' . $cf_cname . $cf_path . "*" . '","Condition":{"DateLessThan":{"AWS:EpochTime":'. $cf_expires . '}}}]}';

	$cf_encode_policy = encode_base64($cf_policy);
	$cf_signature = encode_base64(rsa_sha1_sign($cf_policy, $cf_private_key_file));
	


	// Create Signed Cookie
	// Set-Cookie CloudFront-Policy
	// setcookie (Name, Value, expire, path, domain, secure)
	setcookie("CloudFront-Policy","$cf_encode_policy", 0, "$cf_path", "$cf_domain");
	
	// Set-Cookie CloudFront-Signature
	setcookie("CloudFront-Signature","$cf_signature",0,"$cf_path","$cf_domain");
	
	// Set-Cookie CloudFront-Key-Pair-Id
	setcookie("CloudFront-Key-Pair-Id","$cf_key_pair_id",0, "$cf_path","$cf_domain");



	//---------------------------------------------------------
	// Functions
	//---------------------------------------------------------

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
	function encode_base64($value) {
	        $encoded = base64_encode($value);
	        // replace unsafe characters +, = and / with
	        // the safe characters -, _ and ~
	        return str_replace(
                        array('+', '=', '/'),
                        array('-', '_', '~'),
                        $encoded);
	}

?>
