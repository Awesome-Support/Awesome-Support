<?php
/**
 * Encryption Middleware
 *
 * Allow or sessions to be encrypted at rest by wrapping all reads/writes with
 * an openssl-powered layer of encryption. A passkey must be supplied when the
 * handler is instantiated and will be normalized and used for all data storage.
 *
 * @package Sessionz
 * @subpackage Handlers
 * @since 1.0.0
 */

namespace EAMann\Sessionz\Handlers;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Extend the standard "noop" handler so that normal functionality for managing
 * sessions passes through unchanged to other layers in the stack. However, be sure
 * to intercept all reads/writes to transparently decrypt/encrypt data as it
 * passes between various storage handlers and the application itself.
 */
class EncryptionHandler extends NoopHandler  {

    private $key;

    public function __construct( $key )
    {
        $this->key = Key::loadFromAsciiSafeString($key);
    }

	/**
	 * Attempt to decrypt the ciphertext passed in given the key supplied by the
	 * constructor.
	 *
	 * @param string $ciphertext
	 *
	 * @return string
	 */
    protected function decrypt($ciphertext)
    {
	    return Crypto::decrypt($ciphertext, $this->key);
    }

	/**
	 * Use the Crypto object from Defuse's library to encrypt the plain text with the key
	 * supplied in the constructor.
	 *
	 * @param string $plaintext
	 *
	 * @return string
	 */
    protected function encrypt($plaintext)
    {
	    return Crypto::encrypt($plaintext, $this->key);
    }

    /**
     * Read all data from farther down the stack (i.e. earlier-added handlers)
     * and then decrypt the data given specified keys.
     *
     * @param string   $id   ID of the session to read
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return string
     */
    public function read($id, $next)
    {
        $encrypted = $next( $id );

        return empty( $encrypted ) ? $encrypted : $this->decrypt( $next( $id ) );
    }

    /**
     * Encrypt the incoming data payload, then pass it along to the next handler
     * in the stack.
     *
     * @param string   $id   ID of the session to write
     * @param string   $data Data to be written
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function write($id, $data, $next)
    {
        $return = empty( $data ) ? $data : $this->encrypt( $data );
        return $next( $id, $return );
    }
}
