<?php

//
// A PHP Defcon Prometheus Endpoint
//
// Get the current Defcon Value from BK

// Constants
define( 'APIURL', 'https://www.mi5.gov.uk/UKThreatLevel/UKThreatLevel.xml' );
define( 'APPNAME', 'Mi5 Defcon Exporter' );
define( 'VERSION', '0.1' );

$raw_data = null;
$data = null;
$errors = null;
$output = null;

if( ! extension_loaded( 'simplexml' ) ) {
  echo "This Exporter needs simpleXML, please install and try again.\n";
  echo "sudo apt install php-simplexml";
  exit;
}

$raw_data = file_get_contents( APIURL );
if( ! isset( $raw_data ) || $raw_data === null || $raw_data === "" ) {
  $errors = array(
    'code'    => 1,
    'message' => 'Unable to read Content from ' . APIURL
  );
}

// convert xml to array
$data = new SimpleXMLElement( $raw_data );
if( ! isset( $data ) || $data != null || ! is_array( $data ) ) {
  $errors = array(
    'code'    => 2,
    'message' => 'Unable to parse result into array'
  );
}

// get current defcon Level (String)
$defconString = strtolower( str_replace( 'Current Threat Level: ', '', $data->channel->item->title ) );
$defconLevel = 0;
switch( $defconString ) {
  case 'moderate':
    $defconLevel = 4;
    break;
  case 'substantial':
    $defconLevel = 3;
    break;
  case 'severe':
    $defconLevel = 2;
    break;
  case 'critical':
    $defconLevel = 1;
    break;
  default:
    // $defconString === 'low'
    $defconLevel = 5;
    break;
}

// Info Metric
$output .= "# HELP defcon_info " . APPNAME . " Info Metric with constant value 1\n";
$output .= "# TYPE defcon_info gauge\n";
$output .= "defcon_info{version=\"" . VERSION . "\",nodename=\"" . $_SERVER['HTTP_HOST'] . "\"} 1\n";

// Error Metric
if( isset( $error ) && $error != null && is_array( $error ) ) {
  foreach( $error as $key => $value ) {
    $tmp_output .= "defcon_error{code=\"" . $value['code'] . "\",message=\"" . $value['code'] . "\"} 1\n";
  }
  if( isset( $tmp_output ) && $tmp_output != null && $tmp_output != "" ) {
    $output .= "# HELP defcon_error " . APPNAME . " Error Metric\n";
    $output .= "# TYPE defcon_error gauge\n";
    $output .= $tmp_output;
  }
} else {
  $output .= "# HELP defcon_error " . APPNAME . " Error Metric\n";
  $output .= "# TYPE defcon_error gauge\n";
  $output .= "defcon_error{code=\"0\",message=\"No Errors\"} 0\n";
}

// Defcon Level Metric
$output .= "# HELP defcon_level " . APPNAME . " Error Metric\n";
$output .= "# TYPE defcon_level gauge\n";
if( isset( $defconString ) && $defconString != null && $defconString != ""
     && isset( $defconLevel ) && $defconLevel != null ) {
  $output .= "defcon_level{string=\"" . $defconString . "\"} " . $defconLevel . "\n";
} else {
  $output .= "defcon_level{string=\"\"} " . $defconLevel . "\n";
}

// Build Date
$lastBuildDate = $data->channel->lastBuildDate;
if( isset( $lastBuildDate ) && $lastBuildDate != null && $lastBuildDate != "" ) {
  $output .= "# HELP defcon_changed Last updated, Metric shows Time in Millis since last Update\n";
  $output .= "# TYPE defcon_changed gauge\n";
  $output .= "defcon_changed{date=\"" . $lastBuildDate . "\"} " . ( time() - strtotime( $lastBuildDate ) ) . "\n";
}

// Output for Prometheus (simple plain text)
header("Content-type: text/plain; charset=utf-8");
http_response_code( 200 );
print_r( $output );

exit;

?>