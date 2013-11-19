<?php
session_start();

function routes () {
	return array (


		/* Event Api */
		'/event/:alpha/:number/?' => array(
			Route::$delete=>'EventCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/all/?' => array(
			Route::$get => 'EventCtl::getAll',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/month/:number/:number/?' => array(
			Route::$get => 'EventCtl::getMonth',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/day/:number/:number/:number/?' => array(
			Route::$get => 'EventCtl::getDay',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/week/:number/:number/:number/?' => array(
			Route::$get => 'EventCtl::getWeek',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),		
		'/event/:alpha/?' => array(
			Route::$get=>'EventCtl::get',
			Route::$put=>'EventCtl::update',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/((?:/?::alpha)+)/?' => array(
			Route::$get=>'EventCtl::get',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/event/?' => array(
			Route::$post=>'EventCtl::create',
			Route::$put=>'EventCtl::update',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Calendar Import */
		'/import/ics/?' => array(
			Route::$get => 'ImportCalendarCtl::importICS',
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Reminder Preferences */
		'/reminder_preferences/:alpha/?' => array(
			Route::$get=>'ReminderPrefsCtl::get',
			Route::$put=>'ReminderPrefsCtl::update',
			Route::$delete=>'ReminderPrefsCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/reminder/calendar/?' => array(
			Route::$get=>'ReminderPrefsCtl::getCalendarReminders',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/reminder/event/?' => array(
			Route::$get=>'ReminderPrefsCtl::getEventReminders',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/reminder/task/?' => array(
			Route::$get=>'ReminderPrefsCtl::getTaskReminders',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/reminder_preferences/((?:/?::alpha)+)/?' => array(
			Route::$get=>'ReminderPrefsCtl::get',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/reminder/?' => array(
			Route::$get=>'ReminderPrefsCtl::getByUser',
			Route::$post=>'ReminderPrefsCtl::create',
			Route::$put=>'ReminderPrefsCtl::update',
			Route::$delete=>'ReminderPrefsCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		
		/* Sharing Api */
		'/share/?' => array(
			Route::$post=>'SharingCtl::share',
			Route::$put=>'SharingCtl::handleInvitation',
			Route::$get=>'SharingCtl::getInvitationInfo',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/share/details/?' => array(
			Route::$post=>'SharingCtl::getInvitationInfo',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Messaging Api */
		'/admin/messaging/?' => array(
			Route::$post=>'MessagingCtl::sendMessage',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/admin/messaging/status/?' => array(
			Route::$post=>'MessagingCtl::getMessageStatus',
			Route::$get=>'MessagingCtl::getMessageByID',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/admin/messaging/details/?' => array(
			Route::$post=>'MessagingCtl::getMessageByID',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/admin/messaging/groups/?' => array(
			Route::$get=>'MessagingCtl::getMessagingGroups',
			Route::$post=>'MessagingCtl::createMessagingGroup',
			Route::$put=>'MessagingCtl::updateMessagingGroup',
			Route::$delete=>'MessagingCtl::deleteMessagingGroup',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Admin Api */
		'/admin/settings/?' => array(
			Route::$get=>'AdminCtl::getAdminData',
			Route::$put=>'AdminCtl::promoteCalendar',
			Route::$delete=>'AdminCtl::demoteCalendar',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/admin/org/admin/?' => array(
			Route::$put=>'AdminCtl::addAdminToOrg',
			Route::$delete=>'AdminCtl::removeAdminFromOrg',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/admin/calendar/admin/?' => array(
			Route::$put=>'AdminCtl::addAdminToCal',
			Route::$delete=>'AdminCtl::removeAdminFromCal',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* User Api */
		'/user/?' => array(
			Route::$get=>'UserCtl::get',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		
		'/user/getData' => array(
			Route::$get=>'UserCtl::getData',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		'/user/new/?' => array(
			Route::$get=>'UserCtl::loadNewUserOptions',
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/user/validate/:alpha/:any?' => array(
			Route::$get=>'UserCtl::validate',
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/user/create/?' => array(
			Route::$post=>'UserCtl::create',
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/user/contactPoints/?' => array(
			Route::$get=>'ContactCtl::getContactPoints',
			Route::$put=>'ContactCtl::updateContactPoint',
			Route::$post=>'ContactCtl::createContactPoint',
			Route::$delete=>'ContactCtl::deleteContactPoint',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/user/contactPoint/activate/?' => array(
			Route::$post=>'ContactCtl::activateContactPoint',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/user/password/?' => array(
			Route::$post=>'UserCtl::forgotPassword',
			Route::$put=>'UserCtl::resetForgottenPassword',
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		// User manipulation could have been done without passing :alpha; we
		// could of checked the passed auth token for the User id. However,
		// by passing the id we can provided a sanity check.
		// If the client is requesting a User who's URL id doesn't match
		// the auth token id, then the client did something wrong!
		'/user/:alpha/?' => array(
			Route::$get=>'UserCtl::get',
			Route::$put=>'UserCtl::update',
			Route::$delete=>'UserCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/auth/token/:any/:any/?' => array(
			Route::$get=>'AuthTokenCtl::get',
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Calendar Api */

		'/calendar/:alpha/:number' => array(
			Route::$delete=>'CalendarCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/calendar/settings/view/?' => array(
			Route::$put=>'CalendarCtl::updateVewSettings',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/calendar/unsubscribe' => array(
			Route::$put=>'CalendarCtl::unsubscribe',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		'/calendar/subscribe' => array(
			Route::$put=>'CalendarCtl::subscribe',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/calendar/?' => array(
			Route::$post=>'CalendarCtl::create',
			Route::$put=>'CalendarCtl::update',
			Route::$delete=>'CalendarCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		'/calendar/:number/events/?' => array(
			Route::$get => 'CalendarCtl::getEvents',
			Route::Authorize(),
			Route::Response("application/json")
		),

		'/calendar/events/:number/:number' => array(
			Route::$get => 'CalendarCtl::allEventsFrom',
			Route::Authorize(),
			Route::Response("application/json")
		),
		'/calendar/all/?' => array(
			Route::$get => 'CalendarCtl::getAll',
			Route::Authorize(),
			Route::Response("application/json")
		),
		'/calendar/:alpha/?' => array(
			Route::$get=>'CalendarCtl::get',
			Route::$put=>'CalendarCtl::update',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/calendar/subscribe/?' => array(
			Route::$post => 'CalendarCtl::subscribe',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/calendar/admins/:alpha/?' => array(
			Route::$get => 'CalendarCtl::getCalendarAdmins',
			Route::$post => 'CalendarCtl::addCalendarAdmin',
			Route::$delete => 'CalendarCtl::deleteCalendarAdmin',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		
		/* Hallpass API */
		'/hallpass/?' => array(
			Route::$get=>'HallpassCtl::getActivePasses',
			Route::$put=>'HallpassCtl::checkIn',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/hallpass/:alpha/?' => array(
			Route::$get=>'HallpassCtl::get',
			Route::$put=>'HallpassCtl::checkIn',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),

		/* Task Api */
		'/task/:alpha/:number' => array(
			Route::$delete=>'TaskCtl::delete',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/task/all/?' => array(
			Route::$get => 'TaskCtl::getAll',
			Route::Authorize(),
			Route::Response("application/json")
		),
		'/task/:alpha/?' => array(
			Route::$get=>'TaskCtl::get',
			Route::$put=>'TaskCtl::update',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/task/((?:/?::alpha)+)/?' => array(
			Route::$get=>'TaskCtl::get',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		),
		'/task/?' => array(
			Route::$post=>'TaskCtl::create',
			Route::$put=>'TaskCtl::update',
			Route::Authorize(),
			Route::Request("application/json"),
			Route::Response("application/json")
		)
	);
}
