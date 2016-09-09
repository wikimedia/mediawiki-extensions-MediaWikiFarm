<?php

if( PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

$host = $argv[1];
$sampleSize = count( $argv ) > 2 ? intval($argv[2]) : 100;
$profiles = 2;

if( is_file( dirname( __FILE__ ) . '/results/metadata.php' ) ) {
	$deleted = @unlink( dirname( __FILE__ ) . '/results/metadata.php' );
	if( !$deleted ) {
		echo "Error: cannot delete previous measures.\n";
		exit( 1 );
	}
}
if( is_file( dirname( __FILE__ ) . '/results/LocalSettings.php' ) ) {
	unlink( dirname( __FILE__ ) . '/results/LocalSettings.php' );
}
if( is_file( dirname( __FILE__ ) . '/results/measures-index.php.php' ) ) {
	unlink( dirname( __FILE__ ) . '/results/measures-index.php.php' );
}

for( $i=0; $i<$sampleSize; $i++ ) {
	if( $i > 0 && $i % 100 == 0 ) {
		echo "\n";
	}
	for( $j=0; $j<$profiles; $j++ ) {
		file_get_contents( $host );
		usleep( rand( 3, 20 ) );
	}
	echo '.';
}
echo "\n";

$statistics = include dirname( __FILE__ ) . '/results/measures-index.php.php';
echo "sample size(farm) = " . count($statistics[0]) . "\n";
echo "sample size(classical) = " . count($statistics[1]) . "\n";

$mean = array();
foreach( $statistics as $profile => $statisticsProfile ) {
	$mean[$profile] = array();
	foreach( $statisticsProfile as $unit ) {
		foreach( $unit as $type => $value ) {
			if( !array_key_exists( $type, $mean[$profile] ) ) {
				$mean[$profile][$type] = 0;
			}
			$mean[$profile][$type] += $value;
		}
	}
	foreach( $mean[$profile] as $type => $value ) {
		$mean[$profile][$type] /= count( $statisticsProfile );
		$mean[$profile][$type] *= 1000;
	}
}

echo "bootstrap    config     total     total     config      total\n";
echo "     farm      farm classical      farm difference difference\n";
echo '    '.number_format($mean[0]['bootstrap'],3).' ';
echo '    '.number_format($mean[0]['config'],3).' ';
echo '    '.number_format($mean[1]['config'],3).' ';
echo '    '.number_format($mean[0]['bootstrap']+$mean[0]['config'],3).' ';
echo '     '.number_format($mean[0]['config']-$mean[1]['config'],3).' ';
echo '     '.number_format($mean[0]['bootstrap']+$mean[0]['config']-$mean[1]['config'],3).' ';
echo "\n\n";
var_dump($mean);

unlink( dirname( __FILE__ ) . '/results/profile-index.php.php' );
