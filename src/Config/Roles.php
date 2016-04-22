<?php
namespace CaT\InstILIAS\Config;

/**
 * Configuration for Roles.
 *
 * @method string getString()
 */
class Roles extends Base {
	/**
	 * @inheritdocs
	 */
	public static function fields() {
		return array
			( "roles"			=> array(array("\\CaT\\InstILIAS\\Config\\Role"), false)
			);
	}
}