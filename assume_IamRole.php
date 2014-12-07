<?php
	error_reporting(E_ALL);
	require '../vendor/autoload.php';
	use Aws\Common\Aws;

	header("Content-Type:text/html; charset=utf-8");



	// POST Data
	$RoleArn = $_POST[rolearn];
	$RoleSessionName = $_POST[rolesessionname];
	$Duration = $_POST[duration];

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
