<?php

if( PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

# Default MediaWikiFarm configuration
$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';

# Override default MediaWikiFarm configuration
@include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/config/MediaWikiFarmDirectories.php';

# Arguments
$host = $argv[1];
$sampleSize = count( $argv ) > 2 ? intval($argv[2]) : 100;
$profiles = 2;

function rmdirr( $dir, $deleteDir = true ) {
	if( !is_dir( $dir ) ) {
		return;
	}
	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach( $files as $file ) {
		if( is_dir( $dir . '/' . $file ) ) {
			rmdirr( $dir . '/' . $file );
		} else {
			unlink( $dir . '/' . $file );
		}
	}
	if( $deleteDir ) {
		rmdir( $dir );
	}
}
rmdirr( $wgMediaWikiFarmCacheDir );
rmdirr( 'results', false );
if( is_string( $wgMediaWikiFarmCacheDir ) && is_dir( $wgMediaWikiFarmCacheDir ) ) {
	echo "Error: Unable to delete cache directory '$wgMediaWikiFarmCacheDir'.\n";
	exit( 1 );
}

# Run
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
	if( !is_numeric( $profile ) ) {
		continue;
	}
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

echo "bootstrap    config     total     total     config      total compilation\n";
echo "     mean      mean      mean      mean       mean       mean        unit\n";
echo "     farm      farm classical      farm difference difference        farm\n";
echo '    '.number_format($mean[0]['bootstrap'],3).' ';
echo '    '.number_format($mean[0]['config'],3).' ';
echo '    '.number_format($mean[1]['config'],3).' ';
echo '    '.number_format($mean[0]['bootstrap']+$mean[0]['config'],3).' ';
echo '     '.number_format($mean[0]['config']-$mean[1]['config'],3).' ';
echo '     '.number_format($mean[0]['bootstrap']+$mean[0]['config']-$mean[1]['config'],3).' ';
echo '      '.number_format($mean[0]['compilation']*count($statistics[0]),3).' ';
echo "\n\n";
var_dump($mean);

unlink( dirname( __FILE__ ) . '/results/profile-index.php.php' );
unlink( dirname( __FILE__ ) . '/results/metadata.php' );
