<?php

class UserCtl
{
	/**
	*	UserCtl::get provides an interface for aquiring a User by means of
	*	the User id passed to the method.
	*
	*	@param $id  User id string.
	*	@return JSON User.
	*
	*	@todo HTTP CONDITIONAL GET
	*/
	function get ($id = null) {
		$authUserID = null;
		if (!Authorize:: skipAuthentication()) {
			$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
			if ($id !== null &&  $authUserID != $id) {
				$forbidden = new UserForbiddenException($authUserID);
				echo $forbidden->json_encode();
				exit;
			}
		} else if ($id === null) {
			echo "You have chosen to skip authentication, which is great,
			but the api cannot ascertain the userID when skipping authenication
			(see path /user/:alpha/).";
			exit;
		}
		$user = User::loadActive($id === null? $authUserID: $id);
		$user->settings = array_shift(User::loadSettings($id === null? $authUserID: $id));
		$user->settings->primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$user->settings->calendar_drawer_visible = ($user->settings->calendar_drawer_visible) ? true:false;
		$user->settings->task_drawer_visible = ($user->settings->task_drawer_visible) ? true:false;
		echo json_encode($user);
	}

	function getData () {
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		//error_log($authUserID);
		$user = User:: getData($authUserID);
		echo json_encode($user);
	}

	function loadNewUserOptions () {
		$options = User::loadNewUserOptions();
		echo json_encode($options);
	}
	function validate($prop, $string) {
		if($prop == 'username'){
			$validity = User::validateUserName($string);
		}
		elseif($prop == 'email'){
			$validity = User::validateUserEmail($string);
		}
		echo json_encode($validity);
	}
/*	function validateUserName($bypass=false) {
		$body = json_decode(Request:: body());
		error_log(print_r($body->user_handle,true));
		$validity = User::validateUserName($body->user_handle);
		echo json_encode($validity);
	}*/
	function forgotPassword () {
		$body = json_decode(Request:: body());
		$user = new User($body);
		$match = false;
		if($user->validateUserName($user->user_handle,true)){
			$match = true;
			$user = $user->loadWithHandle($user->user_handle);
		}
		if($user->ValidateUserEmail($user->email,true) && !$match){
			$match = true;
			$user = $user->loadWithEmail($user->email);
		}
		if($match){
			//error_log(print_r($user,true));
			$user->sendResetInfo();
		}else{
			$noReset = new CantFindUser();
			echo $noReset->json_encode();
			exit;
		}
		echo json_encode($user);
	}
	function resetForgottenPassword () {
		$body = json_decode(Request:: body());
		$valid = User::validateResetToken($body);
		//error_log(print_r($valid,true));
		if (!empty($valid)) {
			error_log(print_r('token is valid',true));
			// get user object
			$user = User::load($valid->user_id);
			$user = new User($user);
			// apply hash to new password and merge it into the user object
			$user->password = Authorize:: hashedPassword($body->password);
			$user->updatePassword();
			$user->deactivateResetToken($body->pw_reset_token);
			unset($user->password);
			echo json_encode($user);
		}
		else{
			error_log(print_r('token NOT valid',true));
			$rstTok = new InvalidResetTokenException();
			echo $rstTok->json_encode();
			exit;
		}
	}

	/**
	*	UserCtl::Create provides an interface for createing a new User. The
	*	properties of the new User is passed into the session via the Request
	*	Body in the form of JSON.
	*
	*	@return Returns newly created JSON User.
	*
	*	UserCtl::create's query pattern is not atomic due to an INSERT followed
	*	by a SELECT without locking. There does exist a posibility,
	*	however unlikely, after the INSERT an update occurs before the following
	*	SELECT on the newly created User, and if the event_id and
	*	last_modified where the only properties to be returned, the client's
	*	cached/local view of the User would be improperly bound to the wrong
	*	last_modified(s). As a result, the improperly bound last_modified(s) could
	*	lead to a false positives when performing future validations (CONDITIONAL GET),
	*	aka. lost of synchrony. To handle the lost of synchrony, UserCtl::create
	*	returns full User to the client on return.
	*/
	function create () {
		$user = User:: create(json_decode(Request:: body()));
		$user->settings = array_shift(User::loadSettings($user->user_id));
		$p = array(
				'point_name'=>'Primary Email',
				'point_type'=>'email',
				'address'=>$user->email,
				'emergency_only'=>'0'
		);
		Contact::createContactPoint($user->user_id,$p); // Use the registration email address as the primary contact point, and attempt to activagte it.
		echo json_encode($user);
	}


	/**
	*	UserCtl::update provides an interface for updating a pre-existing User.
	*	The properties of the User to update are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*
	*	@return Returns updated JSON User or, in the case of update conflict(s),
	*	JSON User Conflict(s) of the structure:
	*		{
	*			'errno'    :409,
	*			'message'  :...,
	*			'resource' :{<user>},
	*			'conflicts':[{<user1>}...{userU}]}.
	*		}.
	*
	*
	*	Client sends full User to UserCtl::update interface. If update conflict(s) occur,
	*	UserCtl::update will return the User in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless of local client changes made to
	*	the same User in the interim of request.
	*/
	function update ($id) {
		if (!Authorize:: skipAuthentication()) {
			if (($authUserID = Authorize:: sharedInstance()->userID()) != $id) {
				$forbidden = new UserForbiddenException($authUserID);
				echo $forbidden->json_encode();
				exit;
			}
		}
		try {
			//error_log(print_r(Request::body(),true));
			$user = new User(json_decode(Request:: body()));
			$user->update();
			$user->password = null;
			$user->settings = array_shift(User::loadSettings($id === null? $authUserID: $id));
			$user->settings->primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
			echo json_encode($user);
		} catch (UserDataConflictException $e) {
			echo $e->json_encode();
		} catch (UserDoesNotExist $e) {
			echo $e->json_encode();
		}
	}


	/**
	*	UserCtl::delete provides an interface for deleting a pre-existing User.
	*	The properties of the User to delete are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*	@return Returns '{}' or, in the case of delete conflicts(s), JSON User
	*	Conflict(s) (see above).
	*
	*	Client sends full User to UserCtl::delete interface. If delete conflict(s) occur,
	*	UserCtl::delete will return the User in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless the local delete of the Same Event.	
	*/
	function delete ($id) {
		if (!Authorize:: skipAuthentication()) {
			if (($authUserID = Authorize:: sharedInstance()->userID()) != $id) {
				$forbidden = new UserForbiddenException($authUserID);
				echo $forbidden->json_encode();
				exit;
			}
		}
		try {
			$user = new User(json_decode(Request:: body()));
			$user->delete();
			echo json_encode($user);
		} catch (UserDataConflictException $e) {
			echo $e->json_encode();
		} catch (UserDoesNotExist $e) {
			echo $e->json_encode();
		}
	}
}
