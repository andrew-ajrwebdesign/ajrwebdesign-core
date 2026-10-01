<?php
/**
 * Minimal stand-in for WP_Post, which several classes type-check against (`instanceof \WP_Post`).
 *
 * Loaded only when WordPress itself is not, so a real install's class always wins. Tests set what they
 * need on the object; nothing here pretends to behave like WordPress beyond holding data. Same shape as
 * ajr-forms' stub.
 *
 * @package AJR\SiteCore
 */

declare( strict_types=1 );

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * WP_Post stand-in.
	 */
	class WP_Post {

		/** @var int */
		public $ID = 0;

		/** @var string */
		public $post_type = 'post';

		/** @var string */
		public $post_status = 'publish';

		/** @var int|string */
		public $post_author = 0;

		/** @var string */
		public $post_content = '';

		/** @var string */
		public $post_password = '';

		/**
		 * Build from properties.
		 *
		 * @param array<string,mixed> $props Properties.
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * WP_Error stand-in: enough to tell a refusal from a success and read its reason.
	 */
	class WP_Error {

		/** @var string */
		protected $code;

		/** @var string */
		protected $message;

		/**
		 * Build.
		 *
		 * @param string $code    Error code.
		 * @param string $message Human-readable reason.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
		}

		/**
		 * The error code.
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * The error message.
		 */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * WP_REST_Request stand-in: a method and a route, which is all the route guards read.
	 */
	class WP_REST_Request {

		/** @var string */
		protected $method;

		/** @var string */
		protected $route;

		/**
		 * Build.
		 *
		 * @param string $method HTTP method.
		 * @param string $route  Route, e.g. /wp/v2/users.
		 */
		public function __construct( $method = '', $route = '' ) {
			$this->method = (string) $method;
			$this->route  = (string) $route;
		}

		/**
		 * Parameters, for the handlers that read one (e.g. `context`).
		 *
		 * @var array<string,mixed>
		 */
		protected $params = [];

		/**
		 * The route.
		 */
		public function get_route(): string {
			return $this->route;
		}

		/**
		 * Set a parameter.
		 *
		 * @param string $key   Name.
		 * @param mixed  $value Value.
		 */
		public function set_param( $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * Read a parameter.
		 *
		 * @param string $key Name.
		 * @return mixed
		 */
		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * WP_REST_Response stand-in: the data a response carries.
	 */
	class WP_REST_Response {

		/** @var mixed */
		protected $data;

		/**
		 * Build.
		 *
		 * @param mixed $data Response data.
		 */
		public function __construct( $data = null ) {
			$this->data = $data;
		}

		/**
		 * The data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Replace the data.
		 *
		 * @param mixed $data Response data.
		 */
		public function set_data( $data ): void {
			$this->data = $data;
		}

		/**
		 * Headers set on the response, by name.
		 *
		 * @var array<string,string>
		 */
		protected $headers = [];

		/**
		 * Set a header.
		 *
		 * @param string $key   Name.
		 * @param string $value Value.
		 */
		public function header( $key, $value ): void {
			$this->headers[ (string) $key ] = (string) $value;
		}

		/**
		 * The headers set.
		 *
		 * @return array<string,string>
		 */
		public function get_headers(): array {
			return $this->headers;
		}
	}
}

if ( ! class_exists( 'WP_Block' ) ) {
	/**
	 * WP_Block stand-in: name, attributes, context and child instances, which is all
	 * CaseStudies\PrivateTagQuery (and its test) reads. render() returns $rendered, the HTML a
	 * test says this block produces; $renders counts calls, so a test can prove a block is
	 * rendered once.
	 */
	class WP_Block {

		/** @var string */
		public $name = '';

		/** @var array<string,mixed> */
		public $attributes = [];

		/** @var array<string,mixed> */
		public $parsed_block = [];

		/** @var array<string,mixed> */
		public $context = [];

		/** @var array<int,WP_Block> */
		public $inner_blocks = [];

		/** @var string */
		public $rendered = '';

		/** @var int */
		public $renders = 0;

		/**
		 * The HTML this block renders to.
		 */
		public function render(): string {
			++$this->renders;
			return $this->rendered;
		}
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	/**
	 * WP_User stand-in: an ID is all the code under test reads from it (0.14.0).
	 */
	class WP_User {

		/** @var int */
		public $ID = 0;

		/**
		 * Build.
		 *
		 * @param int $id User ID.
		 */
		public function __construct( $id = 0 ) {
			$this->ID = (int) $id;
		}
	}
}

if ( ! class_exists( 'WP_REST_Users_Controller' ) ) {
	/**
	 * WP_REST_Users_Controller stand-in: only its identity matters to the users guard.
	 */
	class WP_REST_Users_Controller {}
}

if ( ! class_exists( 'WP' ) ) {
	/**
	 * WP stand-in: only the parsed query variables matter (Security\Hardening reads `embed`).
	 */
	class WP {

		/** @var array<string,mixed> */
		public $query_vars = [];
	}
}

if ( ! class_exists( 'WP_Hook' ) ) {
	/**
	 * WP_Hook stand-in: only the priority being run matters (Google\Tags asks where on wp_head
	 * it is being called from).
	 */
	class WP_Hook {

		/** @var int|false */
		public $priority = false;

		/**
		 * The priority currently being run, or false when the hook is not running.
		 *
		 * @return int|false
		 */
		public function current_priority() {
			return $this->priority;
		}
	}
}
