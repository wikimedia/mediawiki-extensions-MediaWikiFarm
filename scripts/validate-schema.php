<?php
/**
 * Validate the files 'config/farms.[yml|json|php]' against 'docs/farms-schema.json'.
 * 
 * This files is mostly inspired from the README https://github.com/justinrainbow/json-schema
 */


# Protect against web entry
if( PHP_SAPI !== 'cli' ) exit;

require_once "vendor/autoload.php";

foreach( array( 'config/farms.yml', 'config/farms.json', 'config/farms.php' ) as $filename ) {
	
	// Get the schema and data as objects
	$retriever = new JsonSchema\Uri\UriRetriever;
	$schema = $retriever->retrieve('file://' . realpath('docs/farms-schema.json'));
	
	echo "\n$filename:\n";
	if( preg_match( '/\.yml$/', $filename ) ) {
		
		$dataArray = Symfony\Component\Yaml\Yaml::parse( file_get_contents( $filename ) );
		$dataJSON = preg_replace( '/    /', "\t", json_encode( $dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . "\n";
		file_put_contents( 'config/farms.yml.json', $dataJSON );
		$data = json_decode( $dataJSON );
	}
	else if( preg_match( '/\.json$/', $filename ) ) {
		
		$data = json_decode( file_get_contents( $filename ) );
	}
	else if( preg_match( '/\.php$/', $filename ) ) {
		
		$dataArray = include $filename;
		$dataJSON = preg_replace( '/    /', "\t", json_encode( $dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . "\n";
		file_put_contents( 'config/farms.php.json', $dataJSON );
		$data = json_decode( $dataJSON );
	}
	
	// If you use $ref or if you are unsure, resolve those references here
	// This modifies the $schema object
	$refResolver = new JsonSchema\RefResolver( $retriever );
	$refResolver->resolve( $schema, 'file://' . __DIR__ );
	
	// Validate
	$validator = new JsonSchema\Validator();
	$validator->check( $data, $schema );
	
	if( $validator->isValid() ) {
		echo "The supplied JSON validates against the schema.\n";
		@unlink( $filename.'.json' );
	} else {
		echo "JSON does not validate. Violations:\n";
		foreach( $validator->getErrors() as $error ) {
		    echo sprintf( "[%s] %s\n", $error['property'], $error['message'] );
		}
	}
}
