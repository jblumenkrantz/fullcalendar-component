<?php

class ReminderPrefsCtl
{
	function get ($id) {
		$rprefs = NULL;
		if (Authorize:: skipAuthentication())
			$rprefs = ReminderPrefs:: loadActive(split('/', $id));
		else
			$rprefs = ReminderPrefs:: loadActiveByUser(
				split('/', $id),
				Authorize:: sharedInstance()->userID()
			);
		echo json_encode($rprefs);
	}

	function getCalendarReminders () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$tsprops = json_decode(Request:: body());

		echo json_encode(ReminderPrefs:: loadCalendarReminders($authUserID));
	}
	function getEventReminders () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$tsprops = json_decode(Request:: body());

		echo json_encode(ReminderPrefs:: loadEventReminders($authUserID));
	}
	function getTaskReminders () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$tsprops = json_decode(Request:: body());

		echo json_encode(ReminderPrefs:: loadTaskReminders($authUserID));
	}

	function create () {
		$rprefs = json_decode(Request:: body());
		$exceptions = array();
		if (!is_array($rprefs)) $rprefs = array($rprefs);

		if (!Authorize::skipAuthentication()) {
			$authUserID = Authorize::sharedInstance()->userID();
			$rprefs = array_filter($rprefs, function ($rpref) use ($authUserID, &$exceptions) {
				$keep = FALSE;
				if (is_object($rpref))
					$keep = property_exists($rpref, 'user_id') && $rpref->user_id == $authUserID;
				else if (is_array($rpref))
					$keep = array_key_exists('user_id', $rpref) && $rpref['user_id'] == $authUserID;
				if (!$keep)
					$exceptions[] = new UserForbiddenException($authUserID, $rpref);
				return $keep;
			});
		}

		$rprefs = ReminderPrefs:: create($rprefs);
		$rprefs = array_merge($rprefs, $exceptions);
		$nrprefs = count($rprefs);
		echo '[';
			foreach ($rprefs as $rpref) {
				if (get_class ($rpref) === 'UserForbiddenException') {
					echo $rpref->json_encode();
				}
				else
					echo json_encode($rpref);
				if (--$nrprefs > 0) echo ',';
			}
		echo ']';
	}

	function update () {
		$rpprops = json_decode(Request:: body());
		echo '[';
		if (is_object($rpprops))
			$rpprops = array($rpprops);
		$nrprefs = count($rpprops);
		$authUserID = NULL;
		if (!Authorize::skipAuthentication())
			$authUserID = Authorize::sharedInstance()->userID();
		foreach ($rpprops as $rpprop) {
			try {
				$rpref = new ReminderPrefs($rpprop);
				if (!Authorize::skipAuthentication() && $rpref->user_id != $authUserID)
					throw new UserForbiddenException($authUserID, $rpref);
				$rpref->update();
				echo json_encode($rpref);
			} catch (ReminderPrefDataConflictException $e) {
				echo $e->json_encode();
			} catch (ReminderPrefDoesNotExist $e) {
				echo $e->json_encode();
			} catch (UserForbiddenException $e) {
				echo $e->json_encode();
			}
			if (--$nrprefs > 0) echo ',';
		}
		echo ']';
	}

	function delete () {
		$rpprops = json_decode(Request:: body());
		echo '[';
		if (is_object($rpprops))
			$rpprops = array($rpprops);
		$nrprefs = count($rpprops);
		if (!Authorize::skipAuthentication())
			$authUserID = Authorize::sharedInstance()->userID();
		foreach ($rpprops as $rpprop) {
			try {
				$rpref = new ReminderPrefs($rpprop);
				if (!Authorize::skipAuthentication() && $rpref->user_id != $authUserID)
					throw new UserForbiddenException($authUserID, $rpref);
				$rpref->delete();
				echo json_encode($rpref);
			} catch (ReminderPrefDataConflictException $e) {
				echo $e->json_encode();
			} catch (ReminderPrefDoesNotExist $e) {
				echo $e->json_encode();
			} catch (UserForbiddenException $e) {
				echo $e->json_encode();
			}
			if (--$nrprefs > 0) echo ',';
		}
		echo ']';
	}
}