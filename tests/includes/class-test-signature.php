<?php
/**
 * Test file for Activitypub Signature.
 *
 * @package Activitypub
 */

namespace Activitypub\Tests;

use Activitypub\Signature;
use Activitypub\Collection\Actors;

/**
 * Test class for Signature.
 *
 * @coversDefaultClass \Activitypub\Signature
 */
class Test_Signature extends \WP_UnitTestCase {

	/**
	 * The public key in PKCS#1 format.
	 *
	 * @var string
	 */
	private $pkcs1_key = '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAtAVnFFbWG+6NBFKhMZdt59Gx2/vKxWxbxOAYyi/ypZ/9aDY6C/UB
Rei8SqnhKcKXQaiSwme/wpqgCdkrf53H85OioBitCEvKNA6uDxkCtcdgtQ3X55QD
XmatWd32ln6elRmKG45U9R386j82OHzff8Ju65QxGL1LlyCKQ/XFx/pgvblF3cGj
shk0dhNcyGAztODN5HFp9Qzf9d7+gi+xdKeGNhXBAulXoaDzx8FvLEXNfPJb3jUM
1Ug0STFsiICcf7VxmQow6N6d0+HtWxrdtjUBdXrPxz998Ns/cu9jjg06d+XV3TcS
U+AOldmGLJuB/AWV/+F9c9DlczqmnXqd1QIDAQAB
-----END RSA PUBLIC KEY-----
';

	/**
	 * The public key in X.509 format.
	 *
	 * @var string
	 */
	private $x509_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA19218d19uYisOYUZ3oqN
wSRyixAX8V1JHJSngbjAjZr1vYcwMte8CPqqELbNwtQWAMy42UnQpyIqgvLpOaVr
vQWjUuR+7i8wETrVNJq8JQNNCiQ+8+I4TPcGyZDBclHkLtKiCoBtjUH0itVh4Sg0
KQLSb8ZHu9lGh8TJMcLXVUdVkvkUjqHl6I5BoftMVDSKQF+V4X8Qyk7qP7wU8mpE
+O6RuhUpZ3QXM+dBIalyey8NKLf2yN6CmKyW1220wdNupOYHbc8DSYEq6NDQZfZb
yP2KLHN3rdNwsnlAP02Ws1qroBivHSV71KLebQUDU2KpDLKQF2Ix6X47IBFOXnb9
FwIDAQAB
-----END PUBLIC KEY-----
';

	/**
	 * The public key in EC format.
	 *
	 * @var string
	 */
	private $ec_key = '-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE/jw3kftaHGIB2OTKTYFUTTqyzDs0
eWKe+6k1Kh6HSrinXriBLbIhMPY9pQsvqkeT6wW975NDn7+8awb8kHRmIg==
-----END PUBLIC KEY-----
';

	/**
	 * The public key in PKCS#8 format.
	 *
	 * @var string
	 */
	private $pkcs8_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy8dfWmTltr09m49uyESj
x6UnQ9G/iVq+3dJbUdCdVEPR256UD6DLHE8uM4DgXhtoLVrBcvTAl9h0nRGX4uVN
5jE+pTh47B9IUim0bVw2sOBNwPCTUuKbMVx3Cso/6UxJsot41q7+FHIxcAurDxfR
xfJkf+1ecYSb5czoeOG+NUcTEQv1LQntAOJ1ngrmjKyL4UlKZgcs2TfueqlK1v2t
Gw4ylFOQYRx1Nj5YttQAuXc+VpGfztyRK90R74WkE/N6miOoDHcvc+7AeW4zyWsh
ZfLXCbngI45TVhUr3ljxWs1Ykc8d4Xt3JrtcUzltbc6nWS0vstcUmxTLTRURn3SX
4wIDAQAB
-----END PUBLIC KEY-----
';

	/**
	 * Tear down.
	 */
	public function tear_down() {
		parent::tear_down();
		\delete_option( 'activitypub_keypair_for_0' );
		\delete_option( 'activitypub_keypair_for_-1' );
		\delete_option( 'activitypub_keypair_for_admin' );
		\delete_option( 'activitypub_blog_user_public_key' );
		\delete_option( 'activitypub_blog_user_private_key' );
		\delete_option( 'activitypub_application_user_public_key' );
		\delete_option( 'activitypub_application_user_private_key' );
		\delete_option( 'activitypub_actor_mode' );
		\delete_user_meta( 1, 'magic_sig_public_key' );
		\delete_user_meta( 1, 'magic_sig_private_key' );
	}

	/**
	 * Test signature creation.
	 *
	 * @covers ::get_keypair_for
	 * @covers ::get_public_key_for
	 * @covers ::get_private_key_for
	 */
	public function test_signature_creation() {
		$user = Actors::get_by_id( 1 );

		$key_pair    = Signature::get_keypair_for( $user->get__id() );
		$public_key  = Signature::get_public_key_for( $user->get__id() );
		$private_key = Signature::get_private_key_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );
	}

	/**
	 * Test signature legacy.
	 *
	 * @covers ::get_keypair_for
	 */
	public function test_signature_legacy() {
		// Check user.
		$user = Actors::get_by_id( 1 );

		\delete_option( 'activitypub_keypair_for_admin' );

		$public_key  = 'public key ' . $user->get__id();
		$private_key = 'private key ' . $user->get__id();

		update_user_meta( $user->get__id(), 'magic_sig_public_key', $public_key );
		update_user_meta( $user->get__id(), 'magic_sig_private_key', $private_key );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );

		// Check application user.
		$user = Actors::get_by_id( Actors::APPLICATION_USER_ID );

		\delete_option( 'activitypub_keypair_for_-1' );

		$public_key  = 'public key ' . $user->get__id();
		$private_key = 'private key ' . $user->get__id();

		\add_option( 'activitypub_application_user_public_key', $public_key );
		\add_option( 'activitypub_application_user_private_key', $private_key );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );

		// Check blog user.
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_ACTOR_AND_BLOG_MODE );
		$user = Actors::get_by_id( Actors::BLOG_USER_ID );
		\delete_option( 'activitypub_actor_mode' );

		$public_key  = 'public key ' . $user->get__id();
		$private_key = 'private key ' . $user->get__id();

		\delete_option( 'activitypub_keypair_for_0' );

		\add_option( 'activitypub_blog_user_public_key', $public_key );
		\add_option( 'activitypub_blog_user_private_key', $private_key );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );
	}

	/**
	 * Test signature consistency.
	 *
	 * @covers ::get_keypair_for
	 */
	public function test_signature_consistency() {
		// Check user.
		$user = Actors::get_by_id( 1 );

		$public_key  = 'public key ' . $user->get__id();
		$private_key = 'private key ' . $user->get__id();

		update_user_meta( $user->get__id(), 'magic_sig_public_key', $public_key );
		update_user_meta( $user->get__id(), 'magic_sig_private_key', $private_key );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );

		update_user_meta( $user->get__id(), 'magic_sig_public_key', $public_key . '-update' );
		update_user_meta( $user->get__id(), 'magic_sig_private_key', $private_key . '-update' );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );
	}

	/**
	 * Test signature consistancy 2.
	 *
	 * @covers ::get_keypair_for
	 */
	public function test_signature_consistency2() {
		$user = Actors::get_by_id( 1 );

		$key_pair    = Signature::get_keypair_for( $user->get__id() );
		$public_key  = Signature::get_public_key_for( $user->get__id() );
		$private_key = Signature::get_private_key_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );

		update_user_meta( $user->get__id(), 'magic_sig_public_key', 'test' );
		update_user_meta( $user->get__id(), 'magic_sig_private_key', 'test' );

		$key_pair = Signature::get_keypair_for( $user->get__id() );

		$this->assertNotEmpty( $key_pair );
		$this->assertEquals( $key_pair['public_key'], $public_key );
		$this->assertEquals( $key_pair['private_key'], $private_key );
	}

	/**
	 * Test handling of different public key formats.
	 *
	 * @covers ::get_remote_key
	 */
	public function test_key_format_handling() {
		$expected = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtAVnFFbWG+6NBFKhMZdt
59Gx2/vKxWxbxOAYyi/ypZ/9aDY6C/UBRei8SqnhKcKXQaiSwme/wpqgCdkrf53H
85OioBitCEvKNA6uDxkCtcdgtQ3X55QDXmatWd32ln6elRmKG45U9R386j82OHzf
f8Ju65QxGL1LlyCKQ/XFx/pgvblF3cGjshk0dhNcyGAztODN5HFp9Qzf9d7+gi+x
dKeGNhXBAulXoaDzx8FvLEXNfPJb3jUM1Ug0STFsiICcf7VxmQow6N6d0+HtWxrd
tjUBdXrPxz998Ns/cu9jjg06d+XV3TcSU+AOldmGLJuB/AWV/+F9c9DlczqmnXqd
1QIDAQAB
-----END PUBLIC KEY-----
';

		\add_filter( 'pre_get_remote_metadata_by_actor', array( $this, 'pre_get_remote_metadata_by_actor' ), 10, 2 );

		// X.509 key should remain unchanged.
		$result       = Signature::get_remote_key( 'https://example.com/author/x509' );
		$key_resource = \openssl_pkey_get_details( $result );
		$this->assertNotFalse( $key_resource );
		$this->assertSame( $this->x509_key, $key_resource['key'] );

		// PKCS#1 key should be converted to X.509 format.
		$result       = Signature::get_remote_key( 'https://example.com/author/pkcs1' );
		$key_resource = \openssl_pkey_get_details( $result );
		$this->assertNotFalse( $key_resource );
		$this->assertSame( $expected, $key_resource['key'] );

		// EC key should be handled correctly.
		$result       = Signature::get_remote_key( 'https://example.com/author/ec' );
		$key_resource = \openssl_pkey_get_details( $result );
		$this->assertNotFalse( $key_resource );

		// PKCS#8 key should be handled correctly.
		$result       = Signature::get_remote_key( 'https://example.com/author/pkcs8' );
		$key_resource = \openssl_pkey_get_details( $result );
		$this->assertNotFalse( $key_resource );

		// Test with invalid key.
		$result = Signature::get_remote_key( 'https://example.com/author/invalid' );
		$this->assertWPError( $result );

		\remove_filter( 'pre_get_remote_metadata_by_actor', array( $this, 'pre_get_remote_metadata_by_actor' ) );
	}

	/**
	 * Data provider for signature algorithm tests.
	 *
	 * @return string[][] Test data.
	 */
	public function signature_algorithm_provider() {
		return array(
			'hs2019 algorithm'      => array(
				array( 'algorithm' => 'hs2019' ),
				'sha512',
				'hs2019 algorithm should return sha512.',
			),
			'rsa-sha256 algorithm'  => array(
				array( 'algorithm' => 'rsa-sha256' ),
				'sha256',
				'rsa-sha256 algorithm should return sha256.',
			),
			'unknown algorithm'     => array(
				array( 'algorithm' => 'unknown-algorithm' ),
				'sha256',
				'Unknown algorithm should return sha256.',
			),
			'empty algorithm'       => array(
				array( 'algorithm' => '' ),
				false,
				'Empty algorithm should return false.',
			),
			'missing algorithm key' => array(
				array(),
				false,
				'Missing algorithm key should return false.',
			),
		);
	}

	/**
	 * Test signature algorithm detection.
	 *
	 * @covers ::get_signature_algorithm
	 * @dataProvider signature_algorithm_provider
	 *
	 * @param array        $signature_block The signature block to test.
	 * @param string|false $expected        The expected result.
	 * @param string       $message         The assertion message.
	 */
	public function test_get_signature_algorithm( $signature_block, $expected, $message ) {
		$this->assertEquals( $expected, Signature::get_signature_algorithm( $signature_block ), $message );
	}

	/**
	 * Test full signature verification with hs2019 algorithm.
	 *
	 * @covers ::verify_http_signature
	 * @covers ::get_signature_algorithm
	 * @covers ::parse_signature_header
	 * @covers ::get_signed_data
	 */
	public function test_verify_signature_with_hs2019() {
		// Mock a request with hs2019 algorithm signature.
		$key = openssl_pkey_new(
			array(
				'digest_alg'       => 'sha512',
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			)
		);

		// Extract the public key.
		$key_details = openssl_pkey_get_details( $key );
		$public_key  = $key_details['key'];

		// Create a string to sign.
		$date           = gmdate( 'D, d M Y H:i:s T' );
		$string_to_sign = "(request-target): post /wp-json/activitypub/1.0/inbox\nhost: example.org\ndate: {$date}";

		// Sign the string.
		$signature = '';
		openssl_sign( $string_to_sign, $signature, $key, OPENSSL_ALGO_SHA512 );

		// Create the mock request as a $_SERVER-like array.
		// This will be passed through format_server_request().
		$request = array(
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI'    => '/wp-json/activitypub/1.0/inbox',
			'HTTP_HOST'      => 'example.org',
			'HTTP_DATE'      => $date,
			'HTTP_SIGNATURE' => sprintf(
				'keyId="https://example.com/users/test#main-key",algorithm="hs2019",headers="(request-target) host date",signature="%s"',
				base64_encode( $signature ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			),
		);

		// Add filter to mock the remote key retrieval.
		\add_filter(
			'pre_get_remote_metadata_by_actor',
			function () use ( $public_key ) {
				return array(
					'name'      => 'Test User',
					'url'       => 'https://example.com/users/test',
					'publicKey' => array(
						'id'           => 'https://example.com/users/test#main-key',
						'owner'        => 'https://example.com/users/test',
						'publicKeyPem' => $public_key,
					),
				);
			}
		);

		// Verify the signature.
		$this->assertTrue( Signature::verify_http_signature( $request ) );

		// Remove the filter.
		\remove_all_filters( 'pre_get_remote_metadata_by_actor' );
	}

	/**
	 * Pre get remote metadata by actor.
	 *
	 * @param mixed  $value The value.
	 * @param string $url   The URL.
	 * @return array|\WP_Error
	 */
	public function pre_get_remote_metadata_by_actor( $value, $url ) {
		if ( 'https://example.com/author/x509' === $url ) {
			return array(
				'name'      => 'Test Actor',
				'url'       => 'https://example.com/author/x509',
				'publicKey' => array(
					'id'           => 'https://example.com/author#main-key',
					'owner'        => 'https://example.com/author',
					'publicKeyPem' => $this->x509_key,
				),
			);
		}

		if ( 'https://example.com/author/pkcs1' === $url ) {
			return array(
				'name'      => 'Test Actor',
				'url'       => 'https://example.com/author/pkcs1',
				'publicKey' => array(
					'id'           => 'https://example.com/author#main-key',
					'owner'        => 'https://example.com/author',
					'publicKeyPem' => $this->pkcs1_key,
				),
			);
		}

		if ( 'https://example.com/author/ec' === $url ) {
			return array(
				'name'      => 'Test Actor',
				'url'       => 'https://example.com/author/ec',
				'publicKey' => array(
					'id'           => 'https://example.com/author#main-key',
					'owner'        => 'https://example.com/author',
					'publicKeyPem' => $this->ec_key,
				),
			);
		}

		if ( 'https://example.com/author/pkcs8' === $url ) {
			return array(
				'name'      => 'Test Actor',
				'url'       => 'https://example.com/author/pkcs8',
				'publicKey' => array(
					'id'           => 'https://example.com/author#main-key',
					'owner'        => 'https://example.com/author',
					'publicKeyPem' => $this->pkcs8_key,
				),
			);
		}

		if ( 'https://example.com/author/invalid' === $url ) {
			return array(
				'name'      => 'Test Actor',
				'url'       => 'https://example.com/author/invalid',
				'publicKey' => array(
					'id'           => 'https://example.com/author#main-key',
					'owner'        => 'https://example.com/author',
					'publicKeyPem' => 'INVALID KEY DATA',
				),
			);
		}

		return new \WP_Error( 'invalid_url', $url );
	}
}
