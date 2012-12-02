<?php

	/*
	 * This file is part of the Ariadne Component Library.
	 *
	 * (c) Muze <info@muze.nl>
	 *
	 * For the full copyright and license information, please view the LICENSE
	 * file that was distributed with this source code.
	 */

	namespace arc\url;

	/**
	 *  Query parses any valid url query part and generates array accessible values for it
	 *  - you can use the same name multiple times -- ?name=value1&name=value2
	 *    this will result in name becoming an array, similar arrays will not be encoded with trailing [] behind the name
	 *  - you can use names without values -- ?name&name2&name3
	 *  - names may include any valid character -- ?name+name
	 *	Usage:
	 *	
	 */
   	class Query extends \ArrayObject implements \arc\KeyValueStoreInterface, QueryInterface {

   		public function __construct( $query = '' ) {
			parent::__construct( $this->parse( $query ), \ArrayObject::ARRAY_AS_PROPS );
   		}

   		public function __toString() {
   			return str_replace( array('%7E', '%2B' ), array( '~', '+' ),  // ~ and + are often unnecesarily encoded
   				$this->compile( \arc\tainting::untaint( (array) $this, FILTER_UNSAFE_RAW ) )
   			);
   		}

		/**
		 *	Import a query string or an array of key => value pairs into the UrlQuery.
		 *
		 *	Usage: 
		 *		$query->import( 'foo=bar&bar=1' );
		 *		$query->import( array( 'foo' => 'bar', 'bar' => 1 ) );
		 *
 		 *	@param string|array $values query string or array of values to import into this query
		 */
		public function import( $values ) {
   			$this->exchangeArray( $this->parse( $values ) + $this->getArrayCopy() );
   			return $this;
   		}

   		public function reset() {
			$this->exchangeArray( array() );
			return $this;
   		}

		// === \arc\KeyValueStoreInterface ===

		/**
		 *	@param string $name name of the query parameter
		 *	@return mixed The named query parameter
		 */
		public function getvar( $name ) {
			return $this->offsetGet($name);
		}

		/**
		 *	@param string $name name for the query parameter
		 *	@param mixed $value value of the query parameter
		 */
		public function putvar( $name, $value ) {
			$this->offsetSet($name, $value);
		}



   		protected function compile( $values ) {
   			$result = array();
   			foreach( $values as $name => $value ) {
   				$result[] = $this->encodeValue( $name, $value );
   			}
   			return implode( '&', $result );
   		}

   		protected function parse( $values ) {
			$result = array();
			if ( is_array( $values ) || ( $values instanceof \Traversable ) ) {
				foreach ( $values as $name => $value ) {
					$result[$name] = $value;
				}
			} else if ( is_string( $values ) ) {
				$result = $this->parseQueryString( $values );
			}
			return $result;
		}

		protected function parseQueryString( $queryString ) {
			$result = array();
			if ( $queryString ) {
				$values = preg_split( '/[\&\;]/', $queryString );
				foreach( $values as $queryStringEntry ) {
					list( $name, $value ) = $this->parseQueryStringEntry( $queryStringEntry );
					if ( !$value ) {
						$result[] = $name;
					} else if ( !isset( $result[$name] ) ) {
						$result[ $name ] = $value;
					} else if ( !is_array( $result[$name] ) ) {
						$result[ $name ] = array( $result[$name], $value );
					} else {
						$result[ $name ][] = $value;
					}
				}
			}
			return $result;
		}

		private function parseQueryStringEntry( $queryStringEntry ) {
			$result = explode( '=', $queryStringEntry, 2 ) + array( '' ); // make sure the array always has at least two entries
			return $result;
		}

   		private function encodeValue( $name, $value = null ) {
   			if ( is_array( $value ) ) {
   				$result = array();
   				foreach( $value as $key => $val ) {
   					$result[] = $this->encodeValue( $name, $val );
   				}
   				return implode( '&', $result );
   			} else {
	   			return ( is_numeric( $name ) 
   					? RawUrlEncode( $value ) 
   					: RawUrlEncode( $name ) . ( isset( $value ) ? '=' . RawUrlEncode( (string) $value ) : '' )
   				);
   			}
   		}

   	}