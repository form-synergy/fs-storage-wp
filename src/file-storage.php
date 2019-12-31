<?php
/**
 * Option Storage for SynergyPress WordPress plugin.
 *
 * @link    https://github.com/synergy-press/fs-storage-wp
 * @version 1.5.9
 **/

namespace FormSynergy;

/**
 * Option_Storage Client.
 * This class provides similar tools that of the fs-storage-storage class,
 * It utilizes the wp_option api instead of a file storage.
 *
 * @author     Joseph G. Chamoun <formsynergy@gmail.com>
 * @copyright  2019 FormSynergy.com
 * @licence    https://github.com/synergy-press/fs-storage-wp/blob/dev-master/LICENSE MIT
 */
class Option_Storage {

	/**
	 * @visibility public
	 * @var string $json_error
	 */
	public $json_error = false;

	/**
	 * @visibility public
	 * @var string $package
	 */
	public $package;

	/**
	 * @visibility public
	 * @var string $get
	 */
	public $get = false;

	/**
	 * @visibility public
	 * @var string $sub
	 */
	public $sub = false;

	/**
	 * @visibility public
	 * @var string $file
	 */
	public $file;

	/**
	 * @visibility public
	 * @var string $files
	 */
	public $files;

	/**
	 * @visibility public
	 * @var string $action
	 */
	public $action;

	/**
	 * @visibility public
	 * @var string $find
	 */
	public $find;

	/**
	 * Class constructor.
	 *
	 * @visibility public
	 * @param string $package
	 * @param  string $storage
	 * @return self
	 */
	public function __construct( $package ) {
		$this->package = $package;
		return $this;
	}

	/**
	 * _track_packages
	 *
	 * Will provide a list of packages to remove
	 * during plugin uninstall.
	 *
	 * @visibility public
	 * @param string $action 
	 * @param string $package_name
	 * @return bool
	 */
	public function _track_packages( $action, $package_name ) {
		switch ( $action ) {
			case 'set':
				$packages = get_option( 'fs-packages' );
				if ( $packages ) {
					$packages = json_decode( $packages, true );
					if ( ! in_array( $package_name, $packages ) ) {
						$packages[ count( $packages ) ] = $package_name;
						update_option( 'fs-packages', wp_json_encode( $packages ) );
					}
				} else {
					add_option( 'fs-packages', wp_json_encode( array( $package_name ) ) );
				}
				break;
			case 'delete':
				$packages = get_option( 'fs-packages' );
				if ( $packages ) {
					$packages = json_decode( $packages, true );
					foreach ( $packages as $index => $package ) {
						if ( $package == $package_name ) {
							unset( $packages[ $index ] );
						}
					}
					update_option( 'fs-packages', wp_json_encode( $packages ) );
				}
				break;
		}
	}

	/**
	 * _set_data()
	 *
	 * Helper method, will add store type to the stored data.
	 *
	 * @visibility public
	 * @param string $value
	 * @return mixed
	 */
	public function _set_data( $value = '' ) {
		$store = is_array( $value )
			? array(
				'storeType' => 'array',
				'data'      => wp_json_encode( $value ),
			)
			: is_object( $value )
			? array(
				'storeType' => 'object',
				'data'      => wp_json_encode( $value ),
			)
			: array(
				'storeType' => 'string',
				'data'      => $value,
			);
		return wp_json_encode( $store );
	}

	/**
	 * _get_data()
	 *
	 * Helper method, will retrieve stored data and returns the data in it's original store type.
	 *
	 * @visibility public
	 * @param string $data
	 * @return mixed
	 */
	public function _get_data( $data = '' ) {
		if ( empty( $data ) ) {
			return $data;
		}
		try {
			$value = json_decode( $data );
			return 'array' == $value->storeType
			? json_decode( $value->data, true )
			: 'object' == $value->storeType
			? json_decode( $value->data )
			: $value->data;

			if ( ! $value || empty( $value ) ) {
				$this->json_error = true;
			}
		} catch ( \Exception $e ) {
			$this->json_error = true;
		}
	}

	/**
	 * _set()
	 *
	 * Will store data using wp_option API
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed
	 */
	public function _set( $name, $value ) {
		$this->files[ $name ] = $value;
		$option               = get_option( $name );
		if ( 'store' == $this->action ) {
			add_option( $name, $this->_set_data( $value ) );
			$this->_track_packages( 'set', $name );
		} else {
			update_option( $name, $this->_set_data( $value ), true );
		}
	}

	/**
	 * _get()
	 *
	 * Will retrieve data using wp_option API
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function _get( $name ) {
		$option = get_option( $name );
		return $option
			? $this->_get_data( $option )
			: false;
	}

	/**
	 * Delete()
	 *
	 * Will delete option using wp_option API
	 *
	 * @param string $name
	 * @return void
	 */
	public function Delete( $name ) {
		$file = $this->package;
		if ( ! is_null( $name ) ) {
			$file .= '-' . $name;
		}
		$option = get_option( $file );
		if ( $option ) {
			$this->_track_packages( 'delete', $file );
			delete_option( $file );
		}
	}

	/**
	 * DeleteAll()
	 *
	 * Will delete option using wp_option API
	 *
	 * @return void
	 */
	public function delete_all() {
		$fs_packages = get_option( 'fs-packages' );
		if ( $fs_packages ) {
			foreach ( json_decode( $fs_packages ) as $package ) {
				delete_option( $package );
			}
		}
		delete_option( 'fs-packages' );
	}

	/**
	 * Data()
	 *
	 * The data that needs to be stored.
	 *
	 * @visibility public
	 * @param array $data
	 * @return void
	 */
	public function Data( $data ) {
		$stored = $this->_get( $this->file );
		switch ( true ) {
			case ( ! $stored ):
				$this->action               = 'store';
				$data                       = $this->_set( $this->file, $data );
				$this->files[ $this->file ] = (object) $data;
				break;
			case ( $stored ):
				$replace = false;
				$replace = $this->json_error
							? $data
							: array_replace( (array) $stored, (array) $data );

				if ( $replace ) {
					$this->_set( $this->file, $replace );
					$this->files[ $this->file ] = (object) $replace;
				}
				break;
		}
	}

	/**
	 * Store()
	 *
	 * Will store retrieved responses in json format.
	 *
	 * @visibility public
	 * @param string $name
	 * @return self
	 */
	public function Store( $name ) {
		$file = $this->package;
		if ( ! is_null( $name ) ) {
			$file .= '-' . $name;
		}
		$this->file = $file;
		return $this;
	}

	/**
	 * Update()
	 *
	 * Will update a previously stored response.
	 *
	 * @visibility public
	 * @param string $name
	 * @return self
	 */
	public function Update( $name ) {
		$file = $this->package;
		if ( ! is_null( $name ) ) {
			$file .= '-' . $name;
		}
		$this->file = $file;
		return $this;
	}

	/**
	 * Get()
	 *
	 * Will retrieve stored data with $name.
	 *
	 * @visibility public
	 * @param string $name
	 * @return array $data
	 */
	public function Get( $name, $array = false ) {
		$file  = $this->package;
		$file .= '-' . $name;
		return $array
			? $this->_to_array( $this->_get( $file ) )
			: $this->_get( $file );
	}

	/**
	 * Find()
	 *
	 * Will find key and sub within the retrieved data.
	 *
	 * @visibility public
	 * @see self::In()
	 * @param string $key
	 * @param string $sub
	 * @return array
	 */
	public function Find( $key, $sub = null ) {
		if ( ! $this->get ) {
			if ( ! is_null( $sub ) ) {
				$this->sub = $sub;
			}
			$this->find = $key;
			return $this;
		}
		if ( $this->get && isset( $this->get[ $key ] ) ) {
			return $this->get[ $key ];
		}
		return false;
	}

	/**
	 * In()
	 *
	 * Will load the stored data by name
	 *
	 * @visibility public
	 * @param string $name
	 * @return array
	 */
	public function In( $name ) {
		if ( $this->find ) {
			$data   = $this->Get( $name );
			$return = $data && isset( $data->{$this->find} )
				? $data->{$this->find}
				: false;
			if ( $this->sub && $return ) {
				return isset( $return->{$this->sub} )
					? $return->{$this->sub}
					: false;
			}
			return $return;
		}
	}

	public function _to_array( $data ) {
		return json_decode( wp_json_encode( $data ), true );
	}
}
