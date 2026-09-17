<?php
/**
 * THROWAWAY: proves the CI annotations work. Delete with this branch.
 *
 * @package Emposo\Core
 */

namespace Emposo\Core\Probe;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function probe( $value ) {
	if ( $value == 'yes' ) {
		echo $value;
	}

	return $value;
}
