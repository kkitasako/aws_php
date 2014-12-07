<?php
	error_reporting(E_ALL);
	require '../vendor/autoload.php';
	use Aws\Common\Aws;


	// POST Data
	$RoleArn = $argv[1];
	$RoleSessionName = "s3access";
	$Duration = 3600;

	// Create AWS SDK Instance
        $aws = Aws::factory('../vendor/aws/aws-sdk-php/src/Aws/Common/Resources/aws-config.php');

	// Execute AssumeRole
	$client = $aws->get('Sts');
	$result = $client->assumeRole(array(
		'RoleArn' => $RoleArn,
		'RoleSessionName' => $RoleSessionName,
		'Duration' => $Duration
	));
	
	$res_data = array('AccessKey' => $result['Credentials']['AccessKeyId'], 'SecretKey' => $result['Credentials']['SecretAccessKey'], 'Token' => $result['Credentials']['SessionToken'] ); 

	// Return Data as JSON Format.
	echo json_encode($res_data);

?>
