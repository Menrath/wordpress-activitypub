<?php
/**
 * Test file for Scheduler class.
 *
 * @package ActivityPub
 */

namespace Activitypub\Tests;

use Activitypub\Scheduler;
use Activitypub\Collection\Outbox;
use Activitypub\Activity\Base_Object;
use WP_UnitTestCase;

/**
 * Test class for Scheduler.
 *
 * @coversDefaultClass \Activitypub\Scheduler
 */
class Test_Scheduler extends WP_UnitTestCase {
	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Create fake data before tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Helper that creates fake data.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create(
			array(
				'role' => 'author',
			)
		);
	}

	/**
	 * Clean up after tests.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$user_id );
	}

	/**
	 * Test reprocess_outbox method.
	 *
	 * @covers ::reprocess_outbox
	 */
	public function test_reprocess_outbox() {
		// Create test activity objects.
		$activity_object = new Base_Object();
		$activity_object->set_content( 'Test Content' );
		$activity_object->set_type( 'Note' );
		$activity_object->set_id( 'https://example.com/test-id' );

		// Add multiple pending activities.
		$pending_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$pending_ids[] = Outbox::add(
				$activity_object,
				'Create',
				self::$user_id,
				ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC
			);
		}

		// Track scheduled events.
		$scheduled_events = array();
		add_filter(
			'schedule_event',
			function ( $event ) use ( &$scheduled_events ) {
				if ( 'activitypub_process_outbox' === $event->hook ) {
					$scheduled_events[] = $event->args[0];
				}
				return $event;
			}
		);

		// Run reprocess_outbox.
		Scheduler::reprocess_outbox();

		// Verify each pending activity was scheduled.
		$this->assertCount( 3, $scheduled_events, 'Should schedule 3 activities for processing' );
		foreach ( $pending_ids as $id ) {
			$this->assertContains( $id, $scheduled_events, "Activity $id should be scheduled" );
		}

		// Test with published activities (should not be scheduled).
		$published_id = Outbox::add(
			$activity_object,
			'Create',
			self::$user_id,
			ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC
		);
		wp_update_post(
			array(
				'ID'          => $published_id,
				'post_status' => 'publish',
			)
		);

		// Reset tracked events.
		$scheduled_events = array();

		// Run reprocess_outbox again.
		Scheduler::reprocess_outbox();

		// Verify published activity was not scheduled.
		$this->assertNotContains( $published_id, $scheduled_events, 'Published activity should not be scheduled' );

		// Clean up.
		foreach ( $pending_ids as $id ) {
			wp_delete_post( $id, true );
		}
		wp_delete_post( $published_id, true );
		remove_all_filters( 'schedule_event' );
	}

	/**
	 * Test reprocess_outbox with no pending activities.
	 *
	 * @covers ::reprocess_outbox
	 */
	public function test_reprocess_outbox_no_pending() {
		$scheduled_events = array();
		add_filter(
			'schedule_event',
			function ( $event ) use ( &$scheduled_events ) {
				if ( 'activitypub_process_outbox' === $event->hook ) {
					$scheduled_events[] = $event->args[0];
				}
				return $event;
			}
		);

		// Run reprocess_outbox with no pending activities.
		Scheduler::reprocess_outbox();

		// Verify no events were scheduled.
		$this->assertEmpty( $scheduled_events, 'No events should be scheduled when there are no pending activities' );

		remove_all_filters( 'schedule_event' );
	}

	/**
	 * Test reprocess_outbox scheduling behavior.
	 *
	 * @covers ::reprocess_outbox
	 */
	public function test_reprocess_outbox_scheduling() {
		// Create a test activity.
		$activity_object = new Base_Object();
		$activity_object->set_content( 'Test Content' );
		$activity_object->set_type( 'Note' );
		$activity_object->set_id( 'https://example.com/test-id-2' );

		$pending_id = Outbox::add(
			$activity_object,
			'Create',
			self::$user_id,
			ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC
		);

		// Track scheduled events and their timing.
		$scheduled_time = 0;
		add_filter(
			'schedule_event',
			function ( $event ) use ( &$scheduled_time ) {
				if ( 'activitypub_process_outbox' === $event->hook ) {
					$scheduled_time = $event->timestamp;
				}
				return $event;
			}
		);

		// Run reprocess_outbox.
		Scheduler::reprocess_outbox();

		// Verify scheduling time.
		$this->assertGreaterThan( 0, $scheduled_time, 'Event should be scheduled with a future timestamp' );
		$this->assertGreaterThanOrEqual( time() + 10, $scheduled_time, 'Event should be scheduled at least 10 seconds in the future' );

		// Clean up.
		wp_delete_post( $pending_id, true );
		remove_all_filters( 'schedule_event' );
	}
}
