<?php
/**
 * Name       : MW WP Form Session
 * Description: 永続的にデータを保存するためのクラス。Transient API を使用
 * Version    : 2.1.0
 * Author     : Takashi Kitajima
 * Author URI : https://2inc.org
 * Created    : July 17, 2012
 * Modified   : December 31, 2014
 * License    : GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_WP_Form_Session {

	/**
	 * セッション名
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $session_id;

	/**
	 * Transient の生存時間
	 * @var int
	 */
	protected $expiration = 1440;

	/**
	 * @param string $name 識別子
	 */
	public function __construct( $name ) {
		$this->name = MWF_Config::NAME . '_session_' . $name;

		if ( isset( $_COOKIE[ $this->name ] ) ) {
			$session_id = $_COOKIE[ $this->name ];
		} else {
			$session_id = sha1( wp_create_nonce( $this->name ) . ip2long( $this->get_remote_addr() ) . uniqid() );
			$secure = apply_filters( 'mwform_secure_cookie', is_ssl() );
			try {
				set_error_handler( array( 'self', 'error_handler' ) );
				setcookie( $this->name, $session_id, 0, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
			} catch ( ErrorException $e ) {
			}
		}

		$this->session_id = $session_id;
	}

	public static function error_handler( $errno, $errstr, $errfile, $errline ) {
	}

	/**
	 * セッション変数にセット
	 *
	 * @param array $data
	 */
	public function save( array $data ) {
		$transient_data = get_transient( $this->session_id );
		if ( ! is_array( $transient_data ) ) {
			$transient_data = array();
		}

		foreach ( $data as $key => $value ) {
			$transient_data[ $key ] = $value;
		}
		set_transient( $this->session_id, $transient_data, $this->expiration );
	}

	/**
	 * セッション変数にセット
	 *
	 * @param string $key キー
	 * @param mixed $value 値
	 */
	public function set( $key, $value ) {
		$transient_data = get_transient( $this->session_id );
		if ( ! is_array( $transient_data ) ) {
			$transient_data = array();
		}

		$transient_data[ $key ] = $value;
		set_transient( $this->session_id, $transient_data, $this->expiration );
	}

	/**
	 * セッション変数にセット
	 *
	 * @param string $key キー
	 * @param mixed $value 値
	 */
	public function push( $key, $value ) {
		$transient_data = get_transient( $this->session_id );
		if ( ! is_array( $transient_data ) ) {
			$transient_data = array();
		}

		if ( ! isset( $transient_data[ $key ] ) ) {
			$transient_data[ $key ] = array( $value );
		} else {
			if ( is_array( $transient_data[ $key ] ) ) {
				$transient_data[ $key ][] = $value;
			} else {
				$transient_data[ $key ] = array( $transient_data[ $key ] );
				$transient_data[ $key ][] = $value;
			}
		}
		set_transient( $this->session_id, $transient_data, $this->expiration );
	}

	/**
	 * セッション変数から取得
	 *
	 * @param string $key キー
	 * @return mixed セッション値
	 */
	public function get( $key ) {
		$transient_data = get_transient( $this->session_id );
		if ( is_array( $transient_data ) && isset( $transient_data[ $key ] ) ) {
			return $transient_data[ $key ];
		}
	}

	/**
	 * セッション変数から取得
	 *
	 * @return array セッション値
	 */
	public function gets() {
		$transient_data = get_transient( $this->session_id );
		if ( is_array( $transient_data ) ) {
			return $transient_data;
		}
		return array();
	}

	/**
	 * セッション変数を空に
	 *
	 * @param string $key キー
	 */
	public function clear_value( $key ) {
		$transient_data = get_transient( $this->session_id );
		if ( is_array( $transient_data ) && isset( $transient_data[ $key ] ) ) {
			unset( $transient_data[ $key ] );
			set_transient( $this->session_id, $transient_data, $this->expiration );
		}
	}

	/**
	 * セッション変数を空に
	 */
	public function clear_values() {
		delete_transient( $this->session_id );
	}

	/**
	 * $_SERVER['REMOTE_ADDR'] を取得
	 */
	protected function get_remote_addr() {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}
		return '127.0.0.1';
	}
}
