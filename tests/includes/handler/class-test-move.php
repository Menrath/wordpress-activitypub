<?php
/**
 * Test file for Activitypub Move Handler.
 *
 * @package Activitypub
 */

namespace Activitypub\Tests\Handler;

use Activitypub\Collection\Followers;
use Activitypub\Model\Follower;
use Activitypub\Handler\Move;
use Activitypub\Http;

/**
 * Test class for the Move handler.
 *
 * @coversDefaultClass \Activitypub\Handler\Move
 */
class Test_Move extends \WP_UnitTestCase {
	/**
	 * The user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * The second user ID.
	 *
	 * @var int
	 */
	private $user_id_2;

	/**
	 * Setup the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id   = $this->factory->user->create();
		$this->user_id_2 = $this->factory->user->create();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_delete_user( $this->user_id );
		wp_delete_user( $this->user_id_2 );
	}

	/**
	 * Test the handle_move method with a target and origin.
	 */
	public function test_handle_move_with_target_and_origin() {
		$target = 'https://example.com/new-profile';
		$origin = 'https://example.com/old-profile';

		// Mock the HTTP response for the origin object.
		$origin_object = array(
			'type'    => 'Person',
			'id'      => $origin,
			'url'     => $origin,
			'name'    => 'Old Profile',
			'inbox'   => 'https://example.com/old-profile/inbox',
			'movedTo' => $target,
		);

		// Mock the HTTP response for the target object.
		$target_object = array(
			'type'          => 'Person',
			'id'            => $target,
			'url'           => $target,
			'name'          => 'New Profile',
			'inbox'         => 'https://example.com/new-profile/inbox',
			'also_known_as' => array(
				$origin,
			),
		);

		// Create a follower for the origin.
		$origin_follower = new Follower();
		$origin_follower->set_inbox( 'https://example.com/old-profile/inbox' );
		$origin_follower->set_name( 'Old Profile' );
		$origin_follower->set_type( 'Person' );
		$origin_follower->set_id( $origin );
		$origin_follower->set_url( $origin );
		$id = $origin_follower->upsert();

		// Add the user ID meta value.
		add_post_meta( $id, '_activitypub_user_id', $this->user_id );

		$filter = function ( $preempt, $args, $url ) use ( $target, $target_object, $origin, $origin_object ) {
			if ( $url === $target ) {
				return array(
					'body'     => wp_json_encode( $target_object ),
					'response' => array( 'code' => 200 ),
				);
			}
			if ( $url === $origin ) {
				return array(
					'body'     => wp_json_encode( $origin_object ),
					'response' => array( 'code' => 200 ),
				);
			}
			return $preempt;
		};

		// Mock the HTTP request.
		add_filter(
			'pre_http_request',
			$filter,
			10,
			3
		);

		$activity = array(
			'type'   => 'Move',
			'actor'  => $origin,
			'object' => $target,
		);

		Move::handle_move( $activity );

		// Check if the origin follower was updated.
		$updated_follower = Followers::get_follower( $this->user_id, $target );

		$this->assertNotNull( $updated_follower );
		$this->assertEquals( $target, $updated_follower->get_id() );
		$this->assertEquals( 'https://example.com/new-profile/inbox', $updated_follower->get_inbox() );

		$updated_follower->delete();

		remove_filter( 'pre_http_request', $filter, 10 );
	}

	/**
	 * Test the handle_move method with an invalid target.
	 *
	 * @covers ::verify_move
	 */
	public function test_handle_move_with_invalid_target() {
		$target = 'https://example.com/new-profile';
		$origin = 'https://example.com/old-profile';

		// Create a follower for the origin.
		$origin_follower = new Follower();
		$origin_follower->set_inbox( 'https://example.com/old-profile/inbox' );
		$origin_follower->set_name( 'Old Profile' );
		$origin_follower->set_type( 'Person' );
		$origin_follower->set_id( $origin );
		$origin_follower->set_url( $origin );
		$id = $origin_follower->upsert();

		// Add the user ID meta value.
		add_post_meta( $id, '_activitypub_user_id', $this->user_id );

		$filter = function () {
			return array(
				'body'     => wp_json_encode( array( 'type' => 'Invalid' ) ),
				'response' => array( 'code' => 200 ),
			);
		};

		// Mock HTTP request to return invalid data.
		add_filter(
			'pre_http_request',
			$filter
		);

		$activity = array(
			'type'   => 'Move',
			'actor'  => $origin,
			'object' => $target,
		);

		Move::handle_move( $activity );

		// Assert that the original follower still exists and wasn't modified.
		$existing_follower = Followers::get_follower( $this->user_id, $origin );
		$this->assertNotNull( $existing_follower );
		$this->assertEquals( $origin, $existing_follower->get_id() );

		// Assert that no new follower was created for the target.
		$target_follower = Followers::get_follower( $this->user_id, $target );
		$this->assertNull( $target_follower );

		// Cleanup.
		$origin_follower->delete();
		remove_filter( 'pre_http_request', $filter );
	}

	/**
	 * Test the handle_move method without a target or origin.
	 *
	 * @covers ::verify_move
	 */
	public function test_handle_move_without_target_or_origin() {
		// Create a test follower to ensure it's not affected.
		$test_follower = new Follower();
		$test_follower->set_inbox( 'https://example.com/test/inbox' );
		$test_follower->set_name( 'Test Profile' );
		$test_follower->set_type( 'Person' );
		$test_follower->set_id( 'https://example.com/test-profile' );
		$test_follower->set_url( 'https://example.com/test-profile' );
		$id = $test_follower->upsert();

		// Add the user ID meta value.
		add_post_meta( $id, '_activitypub_user_id', $this->user_id );

		// Store initial followers count.
		$initial_followers = Followers::get_followers( $this->user_id );
		$initial_count     = count( $initial_followers );

		$activity = array(
			'type' => 'Move',
		);

		Move::handle_move( $activity );

		// Verify that no followers were added or removed.
		$final_followers = Followers::get_followers( $this->user_id );
		$this->assertEquals( $initial_count, count( $final_followers ) );

		// Verify that our test follower remains unchanged.
		$existing_follower = Followers::get_follower( $this->user_id, 'https://example.com/test-profile' );
		$this->assertNotNull( $existing_follower );
		$this->assertEquals( 'https://example.com/test-profile', $existing_follower->get_id() );
		$this->assertEquals( 'https://example.com/test/inbox', $existing_follower->get_inbox() );

		// Cleanup.
		$test_follower->delete();
	}

	/**
	 * Test the handle_move method with an existing target and origin.
	 */
	public function test_handle_move_with_existing_target_and_origin() {
		$target = 'https://example.com/new-profile';
		$origin = 'https://example.com/old-profile';

		// Create followers for target and origin.
		$target_follower = new Follower();
		$target_follower->set_inbox( 'https://example.com/new-profile/inbox' );
		$target_follower->set_type( 'Person' );
		$target_follower->set_id( $target );
		$target_follower->set_url( $target );
		$target_id = $target_follower->upsert();

		$origin_follower = new Follower();
		$origin_follower->set_inbox( 'https://example.com/old-profile/inbox' );
		$origin_follower->set_type( 'Person' );
		$origin_follower->set_id( $origin );
		$origin_follower->set_url( $origin );
		$origin_id = $origin_follower->upsert();

		// Add user IDs.
		\add_post_meta( $origin_id, '_activitypub_user_id', $this->user_id );
		\add_post_meta( $origin_id, '_activitypub_user_id', $this->user_id_2 );
		\add_post_meta( $target_id, '_activitypub_user_id', $this->user_id );

		// Clear the cache.
		\wp_cache_delete( $origin_id, 'posts' );
		\wp_cache_delete( $target_id, 'posts' );

		$filter = function ( $preempt, $args, $url ) use ( $target, $origin ) {
			if ( $url === $target ) {
				return array(
					'body'     => wp_json_encode(
						array(
							'type'          => 'Person',
							'id'            => $target,
							'url'           => $target,
							'name'          => 'New Profile',
							'inbox'         => 'https://example.com/new-profile/inbox',
							'also_known_as' => array(
								$origin,
							),
						)
					),
					'response' => array( 'code' => 200 ),
				);
			}
			if ( $url === $origin ) {
				return array(
					'body'     => wp_json_encode(
						array(
							'type'    => 'Person',
							'id'      => $origin,
							'url'     => $origin,
							'name'    => 'Old Profile',
							'inbox'   => 'https://example.com/old-profile/inbox',
							'movedTo' => $target,
						)
					),
					'response' => array( 'code' => 200 ),
				);
			}
			return $preempt;
		};

		// Mock the HTTP request.
		add_filter(
			'pre_http_request',
			$filter,
			10,
			3
		);

		$activity = array(
			'type'   => 'Move',
			'actor'  => $origin,
			'object' => $target,
		);

		Move::handle_move( $activity );

		// Check if the user IDs were moved correctly.
		$target_users = \get_post_meta( $target_id, '_activitypub_user_id', false );

		$this->assertContains( (string) $this->user_id, $target_users );
		$this->assertContains( (string) $this->user_id_2, $target_users );

		// Check if the origin follower was deleted.
		$this->assertNull( Followers::get_follower_by_actor( $origin, true ) );

		remove_filter( 'pre_http_request', $filter );
	}
}
